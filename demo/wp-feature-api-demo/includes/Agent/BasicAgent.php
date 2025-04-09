<?php

namespace A8C\WpFeatureApiDemo\Agent;

use A8C\WpFeatureApiDemo\Agent\Messages;
use OpenAI;
use OpenAI\Client;
use OpenAI\Responses\Chat\CreateResponseMessage;
use OpenAI\Responses\Chat\CreateResponseToolCallFunction;
use WP_Error;
use A8C\WpFeatureApiDemo\Options;
use WP_Feature;

class BasicAgent {

	private Client $client;
	private bool $strict_schemas = true;
	private Messages $messages;
	private int $call_depth = 3;

	/**
	 * Stored client-provided WP Features
	 *
	 * @var array<string, array{id: string, description: string, input_schema?: array}>
	 */
	private array $client_features = [];

	/**
	 * Store action for client to execute
	 *
	 * @var array{type: string, id: string, args: array, tool_call_id: string}|null
	 */
	private ?array $pending_client_action = null;

	/**
	 * Constructor for the BasicAgent class
	 *
	 * @param array<array{id: string, description: string, input_schema?: array}> $client_features Client-provided features to be used by the agent.
	 * @return self|WP_Error Returns self on success or WP_Error on failure.
	 */
	public function __construct( array $client_features = [] ) {
		$this->messages = new Messages();
		$this->client_features = $this->save_client_features( $client_features );
		$api_key = Options::get_api_key();
		if (empty($api_key)) {
			return new WP_Error(
				'missing_api_key',
				__('OpenAI API key is not configured. Please set it in the Feature API Demo settings.', 'wp-feature-api-demo'),
				['status' => 500]
			);
		}

		$this->client = OpenAI::client($api_key);
	}

	public function get_messages() {
		return $this->messages->get();
	}

	public function user_message( string $message ): self {
		$this->messages->add_user_message( $message );
		return $this;
	}

	/**
	 * Run the agent to process messages and generate responses
	 *
	 * @return array{
	 *   messages: array,
	 *   client_action?: array{type: string, id: string, args: array, tool_call_id: string},
	 *   message_history?: array
	 * }
	 */
	public function run(): array {
		$depth = $this->call_depth;
		$this->pending_client_action = null;

		while ( ! $this->messages->assistant_has_responded() && $depth > 0 && is_null( $this->pending_client_action ) ) {
			$this->make_response_or_feature_call();
			$depth--;
		}

		$result = [
			'messages' => $this->messages->get_chat_messages(),
		];

		if ( ! is_null( $this->pending_client_action ) ) {
			$result['client_action'] = $this->pending_client_action;
			$result['message_history'] = $this->messages->get_chat_messages();
		}

		return $result;
	}

	private function encode_id($input) {
		return bin2hex($input);
	}

	private function decode_id($encoded) {
		return hex2bin($encoded);
	}

	/**
	 * Get tools from both server and client features
	 *
	 * @return array<array{type: string, function: array}>
	 */
	private function get_tools(): array {
		$server_features = wp_feature_registry()->get();
		$server_tools = $this->tools_from_server_features( $server_features );
		$client_tools = $this->tools_from_client_features( $this->client_features );

		return array_merge( $server_tools, $client_tools );
	}

	private function llm_request( string $system ): CreateResponseMessage {
		$messages = $this->messages->get_chat_messages();
		$prompt = [
			'model' => 'gpt-4o',
			'messages' => [
				['role' => 'system', 'content' => $system],
				...$messages
			],
			'tools' => $this->get_tools(),
		];

		$result = $this->client->chat()->create($prompt);
		return $result->choices[0]->message;
	}

	/**
	 * Make a response or feature call based on the current state
	 *
	 * @return void
	 */
	private function make_response_or_feature_call(): void {
		$system = 'You are a helpful WordPress assistant in the dashboard that can use the following tools to resources to help the user. If you are unsure what tool to call, just ask the user to clarify.';

		$response = $this->llm_request( $system );
		$this->messages->add_response( $response );

		$last_message = $this->messages->last_message();

		// Check if the last message contains tool calls and process the first one
		if ( ! empty( $last_message->tool_calls ) && is_array( $last_message->tool_calls ) ) {
			$this->process_tool_call( $last_message->tool_calls[0] );
		}
	}

	private function feature_from_tool_call( CreateResponseToolCallFunction $function ): ?WP_Feature {
		$feature_name = $this->decode_id($function->name);
		return wp_feature_registry()->find( $feature_name );
	}

	/**
	 * Make a feature call with the given feature and arguments
	 *
	 * @param WP_Feature $feature The feature to call.
	 * @param array|null $arguments The arguments to pass to the feature.
	 * @return mixed The result of the feature call.
	 */
	private function make_feature_call( WP_Feature $feature, ?array $arguments ) {
		return $feature->call( $arguments ?? [] );
	}

	private function make_tool_call(): CreateResponseMessage {
		$system = 'You are a helpful WordPress assistant in the dashboard that can use the following tools to resources to help the user. You\'ve been provided some data from a previous tool call. Use that data to call another tool or respond to the user.';

		return $this->llm_request( $system );
	}

	/**
	 * Processes a tool call from the LLM response.
	 *
	 * Checks if the tool call corresponds to a client or server feature,
	 * sets pending client action or executes the server feature accordingly.
	 *
	 * @todo Consider abstracting some part of the client chain here into the main WP Features API in the future
	 *
	 * @param object $tool_call The tool call object from the LLM response.
	 * @return void
	 */
	private function process_tool_call( object $tool_call ): void {
		// Validate tool call structure
		if ( ! isset( $tool_call->id ) || ! isset( $tool_call->function ) || ! $tool_call->function instanceof CreateResponseToolCallFunction ) {
			$this->messages->add_by( 'assistant', 'Received an invalid tool call structure from the AI.' );
			return;
		}

		$tool_call_id = $tool_call->id;
		$function = $tool_call->function;
		$feature_name = $this->decode_id( $function->name );
		$arguments = json_decode( $function->arguments, true );

		if ( $this->is_client_feature( $feature_name ) ) {
			$this->pending_client_action = [
				'type'         => 'execute_feature',
				'id'           => $feature_name,
				'args'         => $arguments,
				'tool_call_id' => $tool_call_id,
			];
			return;
		}

		// It's a server feature, find and execute it
		$feature = wp_feature_registry()->find( $feature_name );

		if ( ! $feature ) {
			$this->messages->add_by( 'assistant', "Sorry, I couldn't find a tool named '{$feature_name}'." );
			return;
		}

		// Execute the server feature
		$result = $this->make_feature_call( $feature, $arguments );

		// Handle the result
		if ( is_wp_error( $result ) ) {
			$this->messages->add_by( 'assistant', $result->get_error_message() );
		} else {
			// Add the feature result message
			$this->messages->add_feature_result( $result, $feature );
			// Make another LLM call to process the tool result
			$response = $this->make_tool_call();
			$this->messages->add_response( $response );
		}
	}


	/**
	 * Convert server features to tools for the LLM
	 *
	 * @param array<WP_Feature> $features The server features to convert.
	 * @return array<array{type: string, function: array}> The tools for the LLM.
	 */
	private function tools_from_server_features( array $features ): array {
		$mapped = array_map( function( $feature ) {
			if ( ! $feature instanceof WP_Feature ) {
				return null;
			}
			$compatible_name = $this->encode_id( $feature->get_id() );
			$parameters      = $feature->get_input_schema();
			$function        = [
				'name'        => $compatible_name,
				'description' => $feature->get_description(),
				'strict'      => $this->strict_schemas,
			];

			if ( is_array( $parameters ) && isset( $parameters['type'] ) && $parameters['type'] === 'object' && ! empty( $parameters['properties'] ) ) {
				$function['parameters'] = $parameters;
			} else {
				$function['parameters'] = [
					'type'                 => 'object',
					'properties'           => new \stdClass(),
					'additionalProperties' => false,
				];
			}

			return [
				'type'     => 'function',
				'function' => $function,
			];
		}, $features );

		return array_values( array_filter( $mapped ) );
	}

	/**
	 * Convert client features to tools for the LLM
	 *
	 * @param array<array{id: string, description: string, input_schema?: array}> $client_features The client features to convert.
	 * @return array<array{type: string, function: array}> The tools for the LLM.
	 */
	private function tools_from_client_features( array $client_features ): array {
		$mapped = array_map( function( $feature_data ) {
			if ( ! isset( $feature_data['id'] ) || ! isset( $feature_data['description'] ) ) {
				return null;
			}

			$compatible_name = $this->encode_id( $feature_data['id'] );
			$parameters      = $feature_data['input_schema'] ?? null;

			if ( is_array( $parameters ) ) {
				try {
					$transformer = \WP_Feature_Schema_Adapter::make( null, $parameters );
					$parameters  = $transformer->transform();
				} catch ( \Exception $e ) {
					error_log( 'Schema transformation failed for client feature ' . $feature_data['id'] . ': ' . $e->getMessage() );
					$parameters = null;
				}
			}

			$function = [
				'name'        => $compatible_name,
				'description' => $feature_data['description'],
				'strict'      => $this->strict_schemas,
			];

			if ( is_array( $parameters ) && isset( $parameters['type'] ) && $parameters['type'] === 'object' && isset( $parameters['properties'] ) ) {
				$function['parameters'] = $parameters;
			} else {
				$function['parameters'] = [
					'type'                 => 'object',
					'properties'           => new \stdClass(),
					'additionalProperties' => false,
				];
			}

			return [
				'type'     => 'function',
				'function' => $function,
			];
		}, $features );

		return array_values( array_filter( $mapped ) );
	}

	/**
	 * Check if a feature is a client feature
	 *
	 * @param string $feature_name The name of the feature to check.
	 * @return bool True if the feature is a client feature, false otherwise.
	 */
	private function is_client_feature( string $feature_name ): bool {
		return isset( $this->client_features[ $feature_name ] );
	}

	/**
	 * Saves client features for efficient repeated lookup of features using the feature's ID.
	 *
	 * @param array<array{id: string, description: string, input_schema?: array}> $client_features An array of client feature definitions. Each feature must have an 'id'.
	 * @return array<string, array{id: string, description: string, input_schema?: array}> An associative array where keys are feature IDs and values are the corresponding feature definitions.
	 */
	private function save_client_features( array $client_features ): array {
		$indexed = [];
		foreach ( $client_features as $feature ) {
			if ( isset( $feature['id'] ) && is_string( $feature['id'] ) ) {
				$indexed[ $feature['id'] ] = $feature;
			}
		}
		return $indexed;
	}

	/**
	 * Add a client tool result to the message history
	 *
	 * @param string $tool_call_id The ID of the tool call.
	 * @param string $result_content The content of the result.
	 * @return array<array> The messages to return to the client.
	 */
	public function add_client_tool_result( string $tool_call_id, string $result_content ): array {
		$tool_message = new Message(
			role: 'tool',
			content: $result_content,
			tool_calls: null,
			tool_call_id: $tool_call_id,
			feature: null
		);

		$this->messages->add( $tool_message );
		$final_response = $this->make_tool_call();
		$this->messages->add_response( $final_response );

		$final_assistant_message_object = $this->messages->last_message();
		$messages_to_return = [];
		$messages_to_return[] = $tool_message->to_array();

		if ( $final_assistant_message_object instanceof Message && $final_assistant_message_object->get_role() === 'assistant' ) {
			$messages_to_return[] = $final_assistant_message_object->to_array();
		}

		return $messages_to_return;
	}

	/**
	 * Set messages from history to restore conversation context
	 *
	 * When a client executes a client-side feature and returns the result, the server needs to
	 * restore the conversation state before processing the result. This method rebuilds the
	 * conversation context from the message history provided by the client.
	 *
	 * The flow typically works as follows:
	 * 1. AI identifies a client-side feature to execute and returns a client_action
	 * 2. Client executes the feature and collects the result
	 * 3. Client submits the result back to the server with the complete message history
	 * 4. Server creates a new BasicAgent instance and calls this method to restore context
	 * 5. Agent then processes the result and generates a new response
	 *
	 * @param array<array{role?: string, content?: string|null, tool_calls?: array|null, tool_call_id?: string|null}> $history The message history to restore.
	 * @return void
	 */
	public function set_messages_from_history( array $history ): void {
		$this->messages = new Messages();
		foreach ( $history as $msg_data ) {
			$this->messages->add( new Message(
				role: $msg_data['role'] ?? 'system',
				content: $msg_data['content'] ?? null,
				tool_calls: isset($msg_data['tool_calls']) && is_array($msg_data['tool_calls'])
					? $msg_data['tool_calls']
					: null,
				tool_call_id: $msg_data['tool_call_id'] ?? null,
				feature: null
			) );
		}
	}
}

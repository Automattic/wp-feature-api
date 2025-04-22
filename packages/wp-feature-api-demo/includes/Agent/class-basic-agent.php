<?php
/**
 * Basic agent class.
 *
 * @package WpFeatureApiDemo\Agent
 */

namespace WpFeatureApiDemo\Agent;

use WpFeatureApiDemo\Agent\Messages;
use OpenAI;
use OpenAI\Client;
use OpenAI\Responses\Chat\CreateResponseMessage;
use OpenAI\Responses\Chat\CreateResponseToolCallFunction;
use WP_Error;
use WpFeatureApiDemo\Options;

/**
 * Basic agent class.
 */
class Basic_Agent {

	/**
	 * OpenAI client.
	 *
	 * @var Client
	 */
	private $client;

	/**
	 * Strict schemas.
	 *
	 * @var bool
	 */
	private $strict_schemas = true;

	/**
	 * Messages.
	 *
	 * @var Messages
	 */
	private $messages;

	/**
	 * Call depth.
	 *
	 * @var int
	 */
	private $call_depth = 3;

	/**
	 * Stored available tools
	 *
	 * @var array<string, array{id: string, description: string, input_schema?: array, output_schema?: array, type: string}>
	 */
	private $available_tools = array();

	/**
	 * Store pending tool execution
	 *
	 * @var array{type: string, tool_id: string, args: array, tool_call_id: string}|null
	 */
	private $pending_tool_execution = null;

	/**
	 * Constructor for the BasicAgent class
	 *
	 * @param array<array{id: string, description: string, input_schema?: array, output_schema?: array, type: string}> $available_tools Tools available to the agent.
	 * @return self|WP_Error Returns self on success or WP_Error on failure.
	 */
	public function __construct( array $available_tools = array() ) {
		$this->messages = new Messages();
		$this->available_tools = $this->save_available_tools( $available_tools );
		$api_key = Options::get_api_key();
		if ( empty( $api_key ) ) {
			return new WP_Error(
				'missing_api_key',
				__( 'OpenAI API key is not configured. Please set it in the Feature API Demo settings.', 'wp-feature-api-demo' ),
				array( 'status' => 500 )
			);
		}

		$this->client = OpenAI::client( $api_key );
	}

	/**
	 * Get the messages.
	 *
	 * @return array
	 */
	public function get_messages() {
		return $this->messages->get();
	}

	/**
	 * Add a user message.
	 *
	 * @param string $message The message to add.
	 * @return self
	 */
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
		$this->pending_tool_execution = null;

		while ( ! $this->messages->assistant_has_responded() && $depth > 0 && is_null( $this->pending_tool_execution ) ) {
			$this->make_response_or_feature_call();
			--$depth;
		}

		$result = array(
			'messages' => $this->messages->get_chat_messages(),
		);

		if ( ! is_null( $this->pending_tool_execution ) ) {
			$result['client_action'] = $this->pending_tool_execution;
			$result['message_history'] = $this->messages->get_chat_messages();
		}

		return $result;
	}

	/**
	 * Encode an ID.
	 *
	 * @param string $input The input to encode.
	 * @return string The encoded ID.
	 */
	private function encode_id( $input ) {
		return bin2hex( $input );
	}

	/**
	 * Decode an ID.
	 *
	 * @param string $encoded The encoded ID.
	 * @return string The decoded ID.
	 */
	private function decode_id( $encoded ) {
		return hex2bin( $encoded );
	}

	/**
	 * Transform a schema with all_fields_required rule
	 *
	 * @param array  $schema The schema to transform.
	 * @param string $tool_id The ID of the tool for error logging.
	 * @return array|null The transformed schema or null if transformation fails.
	 */
	private function transform_schema( array $schema, string $tool_id ): array|null {
		// In strict mode, OpenAI requires all fields to be present in the object.
		// @see https://platform.openai.com/docs/guides/function-calling?api-mode=chat#strict-mode
		// @todo During the agent clean up, let's abstract this out for only OpenAI.
		try {
			$transformer = \WP_Feature_Schema_Adapter::make( null, $schema, array( 'all_fields_required' => true ) );
			return $transformer->transform();
		} catch ( \Exception $e ) {
			error_log( 'Schema transformation failed for tool ' . $tool_id . ': ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * Convert features to tools for the LLM
	 *
	 * @param array<array{id: string, description: string, input_schema?: array, output_schema?: array, type: string}> $features The features to convert.
	 * @return array<array{type: string, function: array}> The tools for the LLM.
	 */
	private function tools_from_features( array $features ): array {
		$mapped = array_map(
			function ( $feature ) {
				// Handle array-based features.
				if ( is_array( $feature ) && isset( $feature['id'] ) && isset( $feature['description'] ) ) {
					$compatible_name = $this->encode_id( $feature['id'] );
					$parameters      = $feature['input_schema'] ?? null;

					// Create a default parameters object if none provided.
					if ( ! is_array( $parameters ) || empty( $parameters ) ) {
						$parameters = array(
							'type'                 => 'object',
							'properties'           => new \stdClass(),
							'additionalProperties' => false,
						);
					} else {
						// Transform the schema if it exists.
						$parameters = $this->transform_schema( $parameters, $feature['id'] );
					}

					$function = array(
						'name'        => $compatible_name,
						'description' => $feature['description'],
						'strict'      => $this->strict_schemas,
					);

					// Only use the parameters if they're a valid object schema.
					if ( is_array( $parameters ) && isset( $parameters['type'] ) && 'object' === $parameters['type'] && isset( $parameters['properties'] ) ) {
						$function['parameters'] = $parameters;
					} else {
						// Fallback to a default object schema.
						$function['parameters'] = array(
							'type'                 => 'object',
							'properties'           => new \stdClass(),
							'additionalProperties' => false,
						);
					}

					return array(
						'type'     => 'function',
						'function' => $function,
					);
				}

				return null;
			},
			$features
		);

		return array_values( array_filter( $mapped ) );
	}

	/**
	 * Get tools from all available sources
	 *
	 * @return array<array{type: string, function: array}>
	 */
	private function get_tools(): array {
		$tools = array();

		// Add all available tools.
		$tools = array_merge( $tools, $this->tools_from_features( $this->available_tools ) );

		return $tools;
	}

	/**
	 * Make a request to the LLM.
	 *
	 * @param string $system The system prompt.
	 * @return CreateResponseMessage The response from the LLM.
	 */
	private function llm_request( string $system ): CreateResponseMessage {
		$messages = $this->messages->get_chat_messages();
		$prompt = array(
			'model' => 'gpt-4o',
			'messages' => array_merge(
				array(
					array(
						'role' => 'system',
						'content' => $system,
					),
				),
				$messages
			),
			'tools' => $this->get_tools(),
		);

		$result = $this->client->chat()->create( $prompt );
		return $result->choices[0]->message;
	}

	/**
	 * Make a response or feature call based on the current state
	 *
	 * @return void
	 */
	private function make_response_or_feature_call() {
		$system = 'You are a helpful WordPress assistant in the dashboard that can use the following tools to resources to help the user. If you are unsure what tool to call, just ask the user to clarify.';

		$response = $this->llm_request( $system );
		$this->messages->add_response( $response );

		$last_message = $this->messages->last_message();

		// Check if the last message contains tool calls and process the first one.
		if ( ! empty( $last_message->tool_calls ) && is_array( $last_message->tool_calls ) ) {
			$this->process_tool_call( $last_message->tool_calls[0] );
		}
	}

	/**
	 * Make a tool call.
	 *
	 * @return CreateResponseMessage The response from the LLM.
	 */
	private function make_tool_call(): CreateResponseMessage {
		$system = 'You are a helpful WordPress assistant in the dashboard that can use the following tools to resources to help the user. You\'ve been provided some data from a previous tool call. Use that data to call another tool or respond to the user.';

		return $this->llm_request( $system );
	}

	/**
	 * Processes a tool call from the LLM response.
	 *
	 * Determines if the tool should be executed locally or remotely,
	 * sets pending tool execution or executes the tool accordingly.
	 *
	 * @param object $tool_call The tool call object from the LLM response.
	 * @return void
	 */
	private function process_tool_call( $tool_call ) {
		if ( ! isset( $tool_call->id ) || ! isset( $tool_call->function ) || ! $tool_call->function instanceof CreateResponseToolCallFunction ) {
			$this->messages->add_by( 'assistant', 'Received an invalid tool call structure from the AI.' );
			return;
		}

		$tool_call_id = $tool_call->id;
		$function = $tool_call->function;
		$tool_name = $this->decode_id( $function->name );
		$arguments = json_decode( $function->arguments, true );

		// Check if the tool exists in our available tools.
		if ( ! isset( $this->available_tools[ $tool_name ] ) ) {
			$this->messages->add_by( 'assistant', "Sorry, I couldn't find a tool named '{$tool_name}'." );
			return;
		}

		// Set pending tool execution for all tools.
		// The client will handle execution based on the tool's location.
		$this->pending_tool_execution = array(
			'type'         => 'execute_tool',
			'toolId'       => $tool_name,
			'args'         => $arguments,
			'toolCallId'   => $tool_call_id,
		);
	}

	/**
	 * Saves tools for efficient repeated lookup using the tool's ID.
	 *
	 * @param array<array{id: string, description: string, input_schema?: array, output_schema?: array, type: string}> $tools An array of tool definitions. Each tool must have an 'id'.
	 * @return array<string, array{id: string, description: string, input_schema?: array, output_schema?: array, type: string}> An associative array where keys are tool IDs and values are the corresponding tool definitions.
	 */
	private function save_available_tools( array $tools ): array {
		$indexed = array();
		foreach ( $tools as $tool ) {
			if ( isset( $tool['id'] ) && is_string( $tool['id'] ) ) {
				$indexed[ $tool['id'] ] = $tool;
			}
		}
		return $indexed;
	}

	/**
	 * Add a tool result to the message history
	 *
	 * @param string $tool_call_id The ID of the tool call.
	 * @param string $result_content The content of the result.
	 * @return array<array> The messages to return to the client.
	 */
	public function add_tool_result( string $tool_call_id, string $result_content ): array {
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
		$messages_to_return = array();
		$messages_to_return[] = $tool_message->to_array();

		if ( $final_assistant_message_object instanceof Message && $final_assistant_message_object->get_role() === 'assistant' ) {
			$messages_to_return[] = $final_assistant_message_object->to_array();
		}

		return $messages_to_return;
	}

	/**
	 * Set messages from history to restore conversation context
	 *
	 * When a tool is executed and returns a result, the server needs to
	 * restore the conversation state before processing the result. This method rebuilds the
	 * conversation context from the message history provided.
	 *
	 * The flow typically works as follows:
	 * 1. AI identifies a tool to execute and returns a client_action
	 * 2. Client executes the tool and collects the result
	 * 3. Client submits the result back to the server with the complete message history
	 * 4. Server creates a new BasicAgent instance and calls this method to restore context
	 * 5. Agent then processes the result and generates a new response
	 *
	 * @param array<array{role?: string, content?: string|null, tool_calls?: array|null, tool_call_id?: string|null}> $history The message history to restore.
	 * @return void
	 */
	public function set_messages_from_history( array $history ) {
		$this->messages = new Messages();
		foreach ( $history as $msg_data ) {
			$this->messages->add(
				new Message(
					role: $msg_data['role'] ?? 'system',
					content: $msg_data['content'] ?? null,
					tool_calls: isset( $msg_data['tool_calls'] ) && is_array( $msg_data['tool_calls'] )
					? $msg_data['tool_calls']
					: null,
					tool_call_id: $msg_data['tool_call_id'] ?? null,
					feature: null
				)
			);
		}
	}
}

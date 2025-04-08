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

	public function __construct() {
		$this->messages = new Messages();
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

	public function run(): self {
		$depth = $this->call_depth;
		while( !$this->messages->assistant_has_responded() && $depth > 0 ) {
			$this->make_response_or_feature_call();
			$depth--;
		}

		return $this;
	}

	private function encode_id($input) {
		return bin2hex($input);
	}

	private function decode_id($encoded) {
		return hex2bin($encoded);
	}

	private function get_tools() {
		$tools = wp_feature_registry()->get();
		return $this->tools_from_features($tools);
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

	private function make_response_or_feature_call() {
		$system = 'You are a helpful WordPress assistant in the dashboard that can use the following tools to resources to help the user. If you are unsure what tool to call, just ask the user to clarify.';

		$response = $this->llm_request( $system );
		$this->messages->add_response( $response );

		$last_message = $this->messages->last_message();

		if( $last_message->has_tool_call() ) {
			$function = $last_message->get_function();
			$feature = $this->feature_from_tool_call( $function );
			$result = $this->make_feature_call( $feature, $function );

			if( is_wp_error( $result ) ) {
				$this->messages->add_by( 'assistant', $result->get_error_message() );
			} else {
				$this->messages->add_feature_result( $result, $feature );
				$response = $this->make_tool_call();
				$this->messages->add_response( $response );
			}
		}
	}

	private function feature_from_tool_call( CreateResponseToolCallFunction $function ): ?WP_Feature {
		$feature_name = $this->decode_id($function->name);
		return wp_feature_registry()->find( $feature_name );
	}

	private function make_feature_call( WP_Feature $feature, CreateResponseToolCallFunction $function ) {
		$parameters = json_decode( $function->arguments, true );
		return $feature->call( $parameters );
	}

	private function make_tool_call(): CreateResponseMessage {
		$system = 'You are a helpful WordPress assistant in the dashboard that can use the following tools to resources to help the user. You\'ve been provided some data from a previous tool call. Use that data to call another tool or respond to the user.';

		return $this->llm_request( $system );
	}

	private function tools_from_features( array $features ) {
		return array_map(function($feature) {
			$compatible_name = $this->encode_id($feature->get_id());
			$parameters = $feature->get_input_schema();
			$function = [
				'name' => $compatible_name,
				'description' => $feature->get_description(),
				'strict' => $this->strict_schemas,
			];

			// additionalProperties is always present. So 1 is considered empty.
			if ( is_array( $parameters ) && count( $parameters ) > 1 ) {
				$function['parameters'] = $parameters;
			} else {
				$function['parameters'] = [
					'type' => 'object',
					'properties' => new \stdClass(),
					'additionalProperties' => false,
				];
			}

			return [
				'type' => 'function',
				'function' => $function,
			];
		}, $features);
	}
}

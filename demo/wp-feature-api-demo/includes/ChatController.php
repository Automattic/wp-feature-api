<?php

namespace A8C\WpFeatureApiDemo;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;
use A8C\WpFeatureApiDemo\Agent\BasicAgent;

class ChatController extends WP_REST_Controller {
	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = 'wp/v2';
		$this->rest_base = 'demo-chat';
	}

	public function register_routes() {
		register_rest_route($this->namespace, $this->rest_base, [
			[
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => [$this, 'handle_chat_request'],
				'permission_callback' => [$this, 'check_permission'],
			],
		]);
	}

	public function check_permission() {
		return true;
		// return current_user_can('edit_posts');
	}

	/**
	 * Main handler for chat requests, routing to either handle a new message or a tool result.
	 *
	 * @param \WP_REST_Request $request The request object. Expected parameters vary:
	 *                                  - For new messages: ['message' => string, 'client_features' => array]
	 *                                  - For tool results: ['tool_result' => array, 'message_history' => array, 'client_features' => array]
	 * @return \WP_REST_Response|\WP_Error Response object containing messages and potentially client actions, or WP_Error on failure.
	 *                                  - Message response: ['messages' => array, 'client_action' => array|null, 'message_history' => array|null]
	 *                                  - Tool result response: ['messages' => array]
	 */
	public function handle_chat_request( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$params = $request->get_params();

		try {
			if ( isset( $params['tool_result'] ) && is_array( $params['tool_result'] ) ) {
				return $this->handle_tool_result( $params );
			}
			return $this->handle_message( $params );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'chat_request_failed',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Extract and validate client features from request parameters.
	 *
	 * Client features represent the capabilities available on the client-side specifically, and are passed down from the client to the server, so the agent can use them.
	 *
	 * @todo:
	 * - Instead of passing the client_features down with every message, maybe we can set them when the context changes (page load, navigation, etc.)?
	 *
	 * @param array $params Request parameters, expected to contain ['client_features'].
	 * @return array<int, array{id: string, description: string}> Validated client features, or an empty array if none are provided or invalid.
	 */
	private function get_client_features( array $params ): array {
		return isset( $params['client_features'] ) && is_array( $params['client_features'] )
			? $params['client_features']
			: array();
	}
	/**
	 * Handles a new user message by passing it to the agent.
	 *
	 * Initializes the agent with client features and processes the user's message.
	 * The agent might respond directly or request a client-side action to be ran (tool call).
	 *
	 * @param array $params Request parameters containing 'message' (string) and 'client_features' (array).
	 * @return \WP_REST_Response|\WP_Error Response object containing agent messages and potentially a client action request, or WP_Error on failure.
	 */
	private function handle_message( array $params ): \WP_REST_Response|\WP_Error {
		$message = isset( $params['message'] ) ? sanitize_text_field( $params['message'] ) : '';

		if ( empty( $message ) ) {
			return new WP_Error(
				'missing_message',
				__( 'Message is required.', 'wp-feature-api-demo' ),
				[ 'status' => 400 ]
			);
		}

		$agent = new BasicAgent( $this->get_client_features( $params ) );
		$result = $agent->user_message( $message )->run();

		if ( ! is_array( $result ) ) {
			return new WP_Error(
				'invalid_agent_response',
				__( 'Agent returned an invalid response.', 'wp-feature-api-demo' ),
				[ 'status' => 500 ]
			);
		}

		$response_data = [
			'messages' => isset( $result['messages'] ) && is_array( $result['messages'] )
				? $result['messages']
				: array(),
		];

		if ( isset( $result['client_action'] ) ) {
			$response_data['client_action'] = $result['client_action'];
			if ( isset( $result['message_history'] ) ) {
				$response_data['message_history'] = $result['message_history'];
			}
		}

		return rest_ensure_response( $response_data );
	}

	/**
	 * Handles a tool result submitted by the client after executing a requested feature (client_action).
	 *
	 * This method receives the output from the client-side JavaScript function execution.
	 * It uses the provided 'message_history' to restore the agent's state to the point just before the client action was requested.
	 * Then, it adds the tool result ('tool_result.content') associated with the 'tool_result.tool_call_id' to the agent's message history.
	 * Finally, it runs the agent again, allowing it to process the tool's output and generate a final response to the user.
	 *
	 * @since 0.1.0
	 *
	 * @param array $params Request parameters containing:
	 *                      'tool_result' => array{tool_call_id: string, content: string (JSON-encoded result from client JS execution)},
	 *                      'message_history' => array<int, array{role: string, ...}> (The conversation history up to the point the client action was requested),
	 *                      'client_features' => array<int, array{id: string, description: string}> (Client capabilities, needed to re-initialize agent)
	 * @return \WP_REST_Response|\WP_Error Response object containing the final agent messages (including the 'tool' role message with the result and the final 'assistant' response), or WP_Error on failure.
	 *                                     Response data structure: [
	 *                                         'messages' => array<int, array{role: string, content: string|null, tool_calls?: array|null, tool_call_id?: string|null}>
	 *                                     ]
	 */
	private function handle_tool_result( array $params ): \WP_REST_Response|\WP_Error {
		if ( ! isset( $params['tool_result'] ) || ! is_array( $params['tool_result'] ) ) {
			return new WP_Error(
				'invalid_tool_result',
				__( 'Tool result must be an array.', 'wp-feature-api-demo' ),
				[ 'status' => 400 ]
			);
		}

		$tool_result = $params['tool_result'];
		$tool_call_id = isset( $tool_result['tool_call_id'] ) ? sanitize_text_field( $tool_result['tool_call_id'] ) : null;
		$content = isset( $tool_result['content'] ) ? $tool_result['content'] : null;
		$message_history = isset( $params['message_history'] ) && is_array( $params['message_history'] )
			? $params['message_history']
			: array();

		if ( empty( $tool_call_id ) || null === $content ) {
			return new WP_Error(
				'missing_tool_result_data',
				__( 'Tool call ID and content are required for tool result.', 'wp-feature-api-demo' ),
				[ 'status' => 400 ]
			);
		}

		$agent = new BasicAgent( $this->get_client_features( $params ) );
		$agent->set_messages_from_history( $message_history );

		try {
			$new_messages = $agent->add_client_tool_result( $tool_call_id, $content );
			return rest_ensure_response( [ 'messages' => $new_messages ] );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'tool_result_processing_failed',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}
}

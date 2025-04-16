<?php
/**
 * Message class.
 *
 * @package WpFeatureApiDemo\Agent
 */

namespace WpFeatureApiDemo\Agent;

use WP_Feature;
use OpenAI\Responses\Chat\CreateResponseToolCallFunction;
use OpenAI\Responses\Chat\CreateResponseMessage;


/**
 * Message class.
 *
 * @since 0.1.0
 */
class Message {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 * @param string          $role The role of the message.
	 * @param string|null     $content The content of the message.
	 * @param array|null      $tool_calls The tool calls of the message.
	 * @param string|null     $tool_call_id The tool call ID of the message.
	 * @param WP_Feature|null $feature The feature of the message.
	 */
	public function __construct(
		public readonly string $role,
		public readonly ?string $content = null,
		public readonly ?array $tool_calls = null,
		public readonly ?string $tool_call_id = null,
		public readonly ?WP_Feature $feature = null,
	) {
	}

	/**
	 * Create a message from a response.
	 *
	 * @since 0.1.0
	 * @param CreateResponseMessage $response The response to create the message from.
	 * @return self
	 */
	public static function from_response( CreateResponseMessage $response ): self {
		return new self(
			role: $response->role,
			content: $response->content,
			tool_calls: empty( $response->toolCalls ) ? null : $response->toolCalls,
			tool_call_id: ! empty( $response->toolCalls ) ? $response->toolCalls[0]->id : null,
		);
	}

	/**
	 * Convert the message to an array.
	 *
	 * @since 0.1.0
	 * @return array
	 */
	public function to_array(): array {
		$content = $this->content ?? '';

		// The assisnt is asking us to execute a client side tool call, so there is no message.
		if ( $this->role === 'assistant' && ! empty( $this->tool_calls ) ) {
			$content = null;
		}

		return array(
			'role' => $this->role,
			'content' => $content,
			'tool_calls' => $this->tool_calls,
			'tool_call_id' => $this->tool_call_id,
			'feature' => $this->feature,
		);
	}

	/**
	 * Get the role of the message.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_role(): string {
		return $this->role;
	}

	/**
	 * Check if the message has a content.
	 *
	 * @since 0.1.0
	 * @return bool
	 */
	public function has_message(): bool {
		return ! empty( $this->content );
	}

	/**
	 * Check if the message has a tool call.
	 *
	 * @since 0.1.0
	 * @return bool
	 */
	public function has_tool_call(): bool {
		return ! empty( $this->tool_call_id );
	}

	/**
	 * Get the function of the tool call.
	 *
	 * @since 0.1.0
	 * @return CreateResponseToolCallFunction
	 */
	public function get_function(): CreateResponseToolCallFunction {
		return $this->tool_calls[0]->function;
	}
}

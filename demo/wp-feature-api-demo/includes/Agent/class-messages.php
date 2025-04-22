<?php
/**
 * Messages class.
 *
 * @package WpFeatureApiDemo\Agent
 */

namespace WpFeatureApiDemo\Agent;

use WpFeatureApiDemo\Agent\Message;
use OpenAI\Responses\Chat\CreateResponseMessage;
use WP_Feature;

/**
 * Messages class.
 */
class Messages {
	/**
	 * Messages.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $messages = array();

	/**
	 * Add a message.
	 *
	 * @since 0.1.0
	 * @param Message $message The message to add.
	 * @return self
	 */
	public function add( Message $message ): self {
		$this->messages[] = $message;
		return $this;
	}

	/**
	 * Add a response.
	 *
	 * @since 0.1.0
	 * @param CreateResponseMessage $response The response to add.
	 * @return self
	 */
	public function add_response( CreateResponseMessage $response ): self {
		$this->add( Message::from_response( $response ) );
		return $this;
	}

	/**
	 * Add a message by role and content.
	 *
	 * @since 0.1.0
	 * @param string          $role The role of the message.
	 * @param string          $content The content of the message.
	 * @param WP_Feature|null $feature The feature of the message.
	 * @return self
	 */
	public function add_by( string $role, string $content, WP_Feature $feature = null ): self {
		$this->add( new Message( $role, $content, $feature ) );
		return $this;
	}

	/**
	 * Add a user message.
	 *
	 * @since 0.1.0
	 * @param string $message The message to add.
	 * @return self
	 */
	public function add_user_message( string $message ): self {
		$this->add_by( 'user', $message );
		return $this;
	}

	/**
	 * Add an assistant message.
	 *
	 * @since 0.1.0
	 * @param string $message The message to add.
	 * @return self
	 */
	public function add_assistant_message( string $message ): self {
		$this->add_by( 'assistant', $message );
		return $this;
	}

	/**
	 * Add a feature result.
	 *
	 * @since 0.1.0
	 * @param string|array $content The content of the message.
	 * @param WP_Feature   $feature The feature of the message.
	 * @param string|null  $tool_call_id The tool call ID of the message.
	 * @return self
	 */
	public function add_feature_result( string|array $content, WP_Feature $feature, string $tool_call_id = null ): self {
		$this->add(
			new Message(
				role: 'tool',
				content: is_array( $content ) ? json_encode( $content ) : $content,
				tool_call_id: $tool_call_id ?? $this->last_message()->tool_call_id,
				feature: $feature
			)
		);
		return $this;
	}

	/**
	 * Get the last message.
	 *
	 * @since 0.1.0
	 * @return Message
	 */
	public function last_message(): Message {
		return $this->messages[ count( $this->messages ) - 1 ];
	}

	/**
	 * Check if the assistant has responded.
	 *
	 * @since 0.1.0
	 * @return bool
	 */
	public function assistant_has_responded(): bool {
		$msg = $this->last_message();
		return $msg->get_role() === 'assistant' && $msg->has_message();
	}

	/**
	 * Get all messages.
	 *
	 * @since 0.1.0
	 * @return array
	 */
	public function get(): array {
		return $this->messages;
	}

	/**
	 * Get all messages as an array of arrays.
	 *
	 * @since 0.1.0
	 * @return array
	 */
	public function get_chat_messages(): array {
		return array_map( fn( Message $msg ) => $msg->to_array(), $this->messages );
	}
}

<?php

namespace A8C\WpFeatureApiDemo\Agent;

use WP_Feature;
use OpenAI\Responses\Chat\CreateResponseToolCallFunction;
use OpenAI\Responses\Chat\CreateResponseMessage;

class Message {
	public function __construct(
		public readonly string $role,
		public readonly ?string $content = null,
		public readonly ?array $tool_calls = null,
		public readonly ?string $tool_call_id = null,
		public readonly ?WP_Feature $feature = null,
	) {
	}

	public static function from_response( CreateResponseMessage $response ): self {
		return new self(
			role: $response->role,
			content: $response->content,
			tool_calls: empty( $response->toolCalls ) ? null : $response->toolCalls,
			tool_call_id: ! empty( $response->toolCalls ) ? $response->toolCalls[0]->id : null,
		);
	}

	public function to_array(): array {
		$content = $this->content ?? '';

		// The assisnt is asking us to execute a client side tool call, so there is no message.
		if ( $this->role === 'assistant' && ! empty( $this->tool_calls ) ) {
			$content = null;
		}

		return [
			'role' => $this->role,
			'content' => $content,
			'tool_calls' => $this->tool_calls,
			'tool_call_id' => $this->tool_call_id,
			'feature' => $this->feature,
		];
	}

	public function get_role(): string {
		return $this->role;
	}

	public function has_message(): bool {
		return ! empty( $this->content );
	}

	public function has_tool_call(): bool {
		return ! empty( $this->tool_call_id );
	}

	public function get_function(): CreateResponseToolCallFunction {
		return $this->tool_calls[0]->function;
	}
}

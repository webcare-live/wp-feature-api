<?php

namespace A8C\WpFeatureApiDemo\Agent;

use A8C\WpFeatureApiDemo\Agent\Message;
use OpenAI\Responses\Chat\CreateResponseMessage;
use WP_Feature;

class Messages {
	private array $messages = [];

	public function add( Message $message ): self {
		$this->messages[] = $message;
		return $this;
	}

	public function add_response( CreateResponseMessage $response ): self {
		$this->add( Message::from_response( $response ) );
		return $this;
	}

	public function add_by( string $role, string $content, WP_Feature $feature = null ): self {
		$this->add( new Message( $role, $content, $feature ) );
		return $this;
	}


	public function add_user_message( string $message ): self {
		$this->add_by( 'user', $message );
		return $this;
	}

	public function add_assistant_message( string $message ): self {
		$this->add_by( 'assistant', $message );
		return $this;
	}

	public function add_feature_result( string|array $content, WP_Feature $feature, ?string $tool_call_id = null): self {
		$this->add( new Message(
			role: 'tool',
			content: is_array( $content ) ? json_encode( $content ) : $content,
			tool_call_id: $tool_call_id ?? $this->last_message()->tool_call_id,
			feature: $feature,
		) );
		return $this;
	}

	public function last_message(): Message {
		return $this->messages[ count( $this->messages ) - 1 ];
	}

	public function assistant_has_responded(): bool {
		$msg = $this->last_message();
		return $msg->get_role() === 'assistant' && $msg->has_message();
	}

	public function get(): array {
		return $this->messages;
	}

	public function get_chat_messages(): array {
		return array_map( fn( Message $msg ) => $msg->to_array(), $this->messages );
	}
}

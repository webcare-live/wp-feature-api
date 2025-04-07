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

	public function handle_chat_request($request) {
		$params = $request->get_params();
		$message = isset($params['message']) ? sanitize_text_field($params['message']) : '';

		if (empty($message)) {
			return new WP_Error(
				'missing_message',
				__('Message is required.', 'wp-feature-api-demo'),
				['status' => 400]
			);
		}

		$agent = new BasicAgent();
		$agent->user_message( $message )->run();

		return rest_ensure_response([
			'messages' => $agent->get_messages(),
		]);
	}
}

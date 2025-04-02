<?php

namespace A8C\WpFeatureApiDemo;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;
use OpenAI;
use WP_Feature_Query;
class ChatController extends WP_REST_Controller {

	private string $api_key;

	private bool $strict_schemas = false;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = 'wp/v2';
		$this->rest_base = 'demo-chat';

		$this->api_key = Options::get_api_key();

		if (empty($this->api_key)) {
			return new WP_Error(
				'missing_api_key',
				__('OpenAI API key is not configured. Please set it in the Feature API Demo settings.', 'wp-feature-api-demo'),
				['status' => 500]
			);
		}
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

		return rest_ensure_response([
			'response' => $this->get_chat_response($message),
		]);
	}

	private function get_chat_response( string $message ) {
		$client = OpenAI::client($this->api_key);
		$tools = \wp_feature_registry()->get();

		$prompt = [
			'model' => 'gpt-4o-mini',
			'messages' => [
				['role' => 'system', 'content' => 'You are a helpful WordPress assistant in the dashboard that can use the following tools to resources to help the user. If you are unsure what tool to call, just ask the user to clarify.'],
				['role' => 'user', 'content' => $message],
			],
			'tools' => $this->tools_from_features($tools),
		];

		// echo wp_json_encode($prompt); die();
		$result = $client->chat()->create($prompt);
		return $result;
		return $result->choices[0]->message->content;
	}

	private function tools_from_features( array $features ) {
		return array_map(function($feature) {
			$compatible_name = substr(str_replace('/', '_', $feature->get_id()), -64);
			$parameters = $feature->get_input_schema();
			$function = [
				'name' => $compatible_name,
				'description' => $feature->get_description(),
				'strict' => $this->strict_schemas,
			];

			// additionalProperties is always present. So 1 is considered empty.
			if ( count( $parameters ) > 1 ) {
				$function['parameters'] = $parameters;
			}

			return [
				'type' => 'function',
				'function' => $function,
			];
		}, $features);
	}
}

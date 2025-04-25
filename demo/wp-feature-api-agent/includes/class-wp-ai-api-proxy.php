<?php
/**
 * Main class for the AI API Proxy REST endpoints.
 *
 * @package WordPress\Feature_API_Agent
 */

namespace A8C\WpFeatureApiAgent;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Registers and handles REST API endpoints for proxying AI service requests.
 */
class WP_AI_API_Proxy {

	/**
	 * Supported AI API service providers.
	 */
	private const SUPPORTED_AI_API_SERVICES = [ 'openai', 'anthropic', 'google' ];

	/**
	 * Base URL for the OpenAI API.
	 */
	private const OPENAI_API_ROOT = 'https://api.openai.com/v1/';

	/**
	 * Base URL for the Anthropic API.
	 */
	private const ANTHROPIC_API_ROOT = 'https://api.anthropic.com/v1/';

	/**
	 * Base URL for the Google Generative Language API.
	 */
	private const GOOGLE_API_ROOT = 'https://generativelanguage.googleapis.com/v1beta/openai/';

	/**
	 * Cache namespace for AI proxy data.
	 */
	private const AI_API_PROXY_CACHE_NAMESPACE = 'ai_api_proxy';

	/**
	 * Cache key prefix for provider models.
	 */
	private const AI_API_PROXY_MODELS_CACHE_KEY_PREFIX = 'models';

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wp/v2';

	/**
	 * REST API base route for the proxy.
	 *
	 * @var string
	 */
	protected $rest_base = 'ai-api-proxy/v1';

	/**
	 * Registers WordPress hooks.
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Registers the REST API routes.
	 */
	public function register_rest_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/healthcheck',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'ai_api_healthcheck' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/models',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_available_models' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<api_path>.*)',
			array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => array( $this, 'ai_api_proxy' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'api_path' => array(
						'description' => __( 'The path to proxy to the AI service API.', 'wp-feature-api-agent' ),
						'type'        => 'string',
						'required'    => true,
					),
				),
			)
		);
	}

	/**
	 * Checks if the current user has permissions to access protected endpoints.
	 *
	 * @return bool|WP_Error True if the user has permission, WP_Error otherwise.
	 */
	public function check_permissions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to access this endpoint.', 'wp-feature-api-agent' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Healthcheck endpoint callback.
	 * Checks if required API key constants are defined.
	 *
	 * @param WP_REST_Request $request Incoming request data.
	 * @return WP_REST_Response Response object.
	 */
	public function ai_api_healthcheck( WP_REST_Request $request ) {
		$openai_key = WP_AI_API_Options::get_openai_api_key();
		$anthropic_key = WP_AI_API_Options::get_anthropic_api_key();
		$google_key = WP_AI_API_Options::get_google_api_key();

		$all_defined = ! empty( $openai_key ) || ! empty( $anthropic_key ) || ! empty( $google_key );

		$status = $all_defined ? 'OK' : 'Configuration Error';
		$code   = $all_defined ? 200 : 500;

		return new WP_REST_Response( [ 'status' => $status ], $code );
	}

	/**
	 * Lists all the models available from the configured providers (OpenAI, Anthropic, Google).
	 *
	 * @param WP_REST_Request $request Incoming request data.
	 * @return WP_Error|WP_REST_Response Model list data or error.
	 */
	public function list_available_models( WP_REST_Request $request ) {
		$openai_models    = $this->get_provider_model_list( 'openai' );
		$anthropic_models = $this->get_provider_model_list( 'anthropic' );
		$google_models    = $this->get_provider_model_list( 'google' );

		$all_models = [];

		if ( is_array( $openai_models ) ) {
			foreach ( $openai_models as $model ) {
				if ( is_object( $model ) ) {
					$model->owned_by = 'openai';
					$all_models[]    = $model;
				}
			}
		}

		if ( is_array( $google_models ) ) {
			foreach ( $google_models as $model ) {
				if ( is_object( $model ) && isset( $model->name ) ) {
					$model_obj           = new stdClass();
					$model_obj->id       = preg_replace( '/^models\//', '', $model->name );
					$model_obj->object   = 'model';
					$model_obj->owned_by = 'google';
					$model_obj->created  = time();
					if ( isset( $model->displayName ) ) {
						$model_obj->name = $model->displayName;
					}
					$all_models[] = $model_obj;
				}
			}
		}

		if ( is_array( $anthropic_models ) ) {
			foreach ( $anthropic_models as $model ) {
				if ( is_object( $model ) && isset( $model->id ) ) {
					$model_obj           = new stdClass();
					$model_obj->id       = $model->id;
					$model_obj->object   = 'model';
					$model_obj->owned_by = 'anthropic';
					$model_obj->created  = isset( $model->created_at ) ? strtotime( $model->created_at ) : time();
					if ( ! $model_obj->created ) {
						$model_obj->created = time();
					}
					$all_models[] = $model_obj;
				}
			}
		}

		if ( empty( $all_models ) ) {
			return new WP_Error(
				'model_list_failed',
				__( 'Unable to retrieve model lists from any provider.', 'wp-feature-api-agent' ),
				[ 'status' => 500 ]
			);
		}

		$response_data = (object) [
			'object' => 'list',
			'data'   => $all_models,
		];

		return new WP_REST_Response( $response_data );
	}


	/**
	 * Proxies the request to the appropriate AI service (OpenAI, Anthropic, Google).
	 *
	 * @param WP_REST_Request $request Incoming request data.
	 * @return WP_Error|WP_REST_Response Vendor data or error.
	 */
	public function ai_api_proxy( WP_REST_Request $request ) {
		$api_path = $request->get_param( 'api_path' );
		$method   = $request->get_method();
		$body     = $request->get_body();
		$headers  = $request->get_headers();

		// Determine the target service based on the 'model' parameter in the request body
		$target_service = 'openai';
		$target_url     = self::OPENAI_API_ROOT . $api_path;
		$auth_header    = sprintf( 'Bearer %s', WP_AI_API_Options::get_openai_api_key() );

		$json_params = $request->get_json_params();
		$model       = $json_params['model'] ?? null;

		if ( ! empty( $model ) ) {
			if ( str_starts_with( $model, 'claude' ) ) {
				$target_service = 'anthropic';
				$target_url     = self::ANTHROPIC_API_ROOT . $api_path;
				$auth_header    = WP_AI_API_Options::get_anthropic_api_key();
			} elseif ( str_starts_with( $model, 'gemini' ) || str_starts_with( $model, 'gemma' ) ) {
				$target_service = 'google';
				$target_url  = self::GOOGLE_API_ROOT . $api_path;
				$auth_header = sprintf( 'Bearer %s', WP_AI_API_Options::get_google_api_key() );
			}
		}

		$outgoing_headers = array(
			'Content-Type' => $headers['content_type'][0] ?? ( ! empty( $body ) ? 'application/json' : null ),
			'User-Agent'   => 'WordPress AI API Proxy/' . WP_AI_API_PROXY_VERSION,
		);

		if ( $target_service === 'anthropic' ) {
			$outgoing_headers['X-API-Key'] = $auth_header;
			// @see https://docs.anthropic.com/en/api/versioning
			$outgoing_headers['anthropic-version'] = '2023-06-01';
		} else {
			$outgoing_headers['Authorization'] = $auth_header;
		}

		$outgoing_headers = array_filter( $outgoing_headers );

		$query_params = $request->get_query_params();
		if ( ! empty( $query_params ) ) {
			unset( $query_params['_envelope'] );
			unset( $query_params['_locale'] );
			$target_url = add_query_arg( $query_params, $target_url );
		}

		$response = wp_remote_request(
			$target_url,
			array(
				'method'  => $method,
				'headers' => $outgoing_headers,
				'body'    => $body,
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'proxy_request_failed',
				__( 'Failed to connect to the AI service.', 'wp-feature-api-agent' ),
				array( 'status' => 502 )
			);
		}

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_headers = wp_remote_retrieve_headers( $response );
		$response_body    = wp_remote_retrieve_body( $response );

		$client_headers = [];
		if ( isset( $response_headers['content-type'] ) ) {
			$client_headers['Content-Type'] = $response_headers['content-type'];
		}

		if ( isset( $response_headers['x-request-id'] ) ) {
			$client_headers['X-Request-ID'] = $response_headers['x-request-id'];
		}

		if ( isset( $response_headers['anthropic-ratelimit-requests-limit'] ) ) {
			$client_headers['X-Anthropic-Ratelimit-Requests-Limit'] = $response_headers['anthropic-ratelimit-requests-limit'];
			$client_headers['X-Anthropic-Ratelimit-Requests-Remaining'] = $response_headers['anthropic-ratelimit-requests-remaining'] ?? '';
			$client_headers['X-Anthropic-Ratelimit-Requests-Reset'] = $response_headers['anthropic-ratelimit-requests-reset'] ?? '';
		}

		$wp_response = new WP_REST_Response( $response_body, $response_code );

		foreach ( $client_headers as $key => $value ) {
			$wp_response->header( $key, $value );
		}

		// Process JSON responses
		if ( isset( $client_headers['Content-Type'] ) && str_contains( strtolower( $client_headers['Content-Type'] ), 'application/json' ) ) {
			$decoded_body = json_decode( $response_body );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				$wp_response->set_data( $decoded_body );
			}
		} else {
			$wp_response->set_data( $response_body );
		}

		return $wp_response;
	}


	/**
	 * Returns the list of available models for a specific provider.
	 * Uses caching.
	 *
	 * @param string $provider The provider key ('openai', 'anthropic', 'google').
	 * @return array List of models (structure depends on provider) or empty array on error/cache miss failure.
	 */
	private function get_provider_model_list( string $provider ): array {
		if ( ! in_array( $provider, self::SUPPORTED_AI_API_SERVICES, true ) ) {
			return [];
		}

		$api_key = '';
		switch ( $provider ) {
			case 'openai':
				$api_key = WP_AI_API_Options::get_openai_api_key();
				break;
			case 'anthropic':
				$api_key = WP_AI_API_Options::get_anthropic_api_key();
				break;
			case 'google':
				$api_key = WP_AI_API_Options::get_google_api_key();
				break;
		}
		if ( empty( $api_key ) ) {
			return [];
		}

		$cache_key = sprintf( '%s-%s', self::AI_API_PROXY_MODELS_CACHE_KEY_PREFIX, $provider );
		$found     = false;

		$cached_models = wp_cache_get( $cache_key, self::AI_API_PROXY_CACHE_NAMESPACE, false, $found );
		if ( $found ) {
			return is_array( $cached_models ) ? $cached_models : [];
		}

		$headers  = [];
		$api_path = '';

		switch ( $provider ) {
			case 'anthropic':
				// Anthropic doesn't have a standard /v1/models endpoint
				return [];
			case 'google':
				$headers = [
					'Authorization' => sprintf( 'Bearer %s', WP_AI_API_Options::get_google_api_key() ),
					'User-Agent'    => 'WordPress AI API Proxy/' . WP_AI_API_PROXY_VERSION,
				];
				$api_path = self::GOOGLE_API_ROOT . 'models';
				break;
			case 'openai':
				$headers = [
					'Authorization' => sprintf( 'Bearer %s', WP_AI_API_Options::get_openai_api_key() ),
					'User-Agent'    => 'WordPress AI API Proxy/' . WP_AI_API_PROXY_VERSION,
				];
				$api_path = self::OPENAI_API_ROOT . 'models';
				break;
		}

		if ( empty( $api_path ) ) {
			return [];
		}

		$response = wp_remote_get(
			$api_path,
			array(
				'headers' => $headers,
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! $body ) {
			return [];
		}

		$json_data = json_decode( $body );
		if ( ! $json_data || ! is_object( $json_data ) ) {
			return [];
		}

		$models_data = [];
		if ( $provider === 'openai' && isset( $json_data->data ) && is_array( $json_data->data ) ) {
			$models_data = $json_data->data;
		} elseif ( $provider === 'google' && isset( $json_data->models ) && is_array( $json_data->models ) ) {
			$models_data = $json_data->models;
		} elseif ( $provider === 'anthropic' ) {
			if ( isset( $json_data->data ) && is_array( $json_data->data ) ) {
				$models_data = $json_data->data;
			}
		} else {
			return [];
		}

		if ( is_array( $models_data ) ) {
			wp_cache_set( $cache_key, $models_data, self::AI_API_PROXY_CACHE_NAMESPACE, 30 * MINUTE_IN_SECONDS );
			return $models_data;
		} else {
			return [];
		}
	}
}

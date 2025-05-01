<?php
/**
 * WP_Feature class file.
 *
 * @package WordPress\Features_API
 */
/**
 * Class WP_Feature
 *
 * Represents a feature in the WordPress Feature API.
 *
 * @since 0.1.0
 */
class WP_Feature implements \JsonSerializable {
	/**
	 * Resource feature type constant.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	const TYPE_RESOURCE = 'resource';

	/**
	 * Tool feature type constant.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	const TYPE_TOOL = 'tool';

	/**
	 * Array of all valid feature types.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	const TYPES = array(
		self::TYPE_RESOURCE,
		self::TYPE_TOOL,
	);

	/**
	 * Default feature type constant.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	const TYPE_DEFAULT = self::TYPE_RESOURCE;

	/**
	 * Server location constant.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	const LOCATION_SERVER = 'server';

	/**
	 * Client location constant.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	const LOCATION_CLIENT = 'client';

	/**
	 * Array of all valid feature locations.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	const LOCATIONS = array(
		self::LOCATION_SERVER,
		self::LOCATION_CLIENT,
	);

	const ID_PATTERN = '[a-z0-9\-\/]+';

	/**
	 * The feature ID, unique identifier.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $id;

	/**
	 * The feature name, human readable.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $name;

	/**
	 * Whether the feature has a REST alias.
	 *
	 * @since 0.1.0
	 * @var bool
	 */
	private $rest_alias = false;

	/**
	 * The feature description.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $description;

	/**
	 * The feature type.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $type;

	/**
	 * The feature metadata.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $meta;

	/**
	 * The feature categories.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $categories;

	/**
	 * The feature input schema.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $input_schema;

	/**
	 * The feature output schema.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $output_schema;

	/**
	 * The feature callback.
	 *
	 * @since 0.1.0
	 * @var callable
	 */
	private $callback;

	/**
	 * The feature permission callback.
	 *
	 * @since 0.1.0
	 * @var callable
	 */
	private $permission_callback;

	/**
	 * The feature is_eligible.
	 *
	 * @since 0.1.0
	 * @var callable
	 */
	private $is_eligible;

	/**
	 * The feature location.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $location;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 * @param string $id   The feature ID.
	 * @param array  $args The feature arguments.
	 */
	public function __construct( $id, $args = array() ) {
		require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/class-wp-feature-schema-adapter.php';

		if ( empty( $id ) || ! is_string( $id ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: Feature ID */
					esc_html__( 'Feature ID must be a non-empty string. Received: %s', 'wp-feature-api' ),
					esc_html( $id )
				),
				'0.1.0'
			);
			return;
		}

		$validation_result = $this->validate_id( $id );
		if ( is_wp_error( $validation_result ) ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html( $validation_result->get_error_message() ),
				'0.1.0'
			);
			return;
		}

		$this->id = $id;
		$result = $this->set_props( $args );

		if ( is_wp_error( $result ) ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html( $result->get_error_message() ),
				'0.1.0'
			);
		}
	}

	/**
	 * Makes a feature from various input types.
	 *
	 * @since 0.1.0
	 * @param WP_Feature|array|string $feature The feature to make.
	 * @return WP_Feature|false The feature if successful, false otherwise.
	 */
	public static function make( $feature ) {
		if ( is_array( $feature ) ) {
			if ( ! isset( $feature['id'] ) ) {
				return false;
			}

			$feature = new WP_Feature( $feature['id'], $feature );
		}

		if ( is_string( $feature ) ) {
			$feature = new WP_Feature( $feature );
		}

		if ( $feature instanceof WP_Feature ) {
			$feature->register_rest_alias();
			return $feature;
		}

		return false;
	}

	/**
	 * Gets the feature type from a request method.
	 *
	 * @since 0.1.0
	 * @param string $method The request method.
	 * @return string The feature type.
	 */
	public static function type_from_request_method( $method ) {
		if ( 'POST' === strtoupper( $method ) ) {
			return self::TYPE_TOOL;
		}

		return self::TYPE_RESOURCE;
	}

	/**
	 * Gets the feature ID.
	 *
	 * @since 0.1.0
	 * @return string The feature ID.
	 */
	public function get_id() {
		return $this->type . '-' . $this->id;
	}

	/**
	 * Gets the feature name.
	 *
	 * @since 0.1.0
	 * @return string The feature name.
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Gets the feature description.
	 *
	 * @since 0.1.0
	 * @return string The feature description.
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Gets the feature type.
	 *
	 * @since 0.1.0
	 * @return string The feature type.
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Gets the feature metadata.
	 *
	 * @since 0.1.0
	 * @return array The feature metadata.
	 */
	public function get_meta() {
		return $this->meta;
	}

	/**
	 * Gets the feature categories.
	 *
	 * @since 0.1.0
	 * @return array The feature categories.
	 */
	public function get_categories() {
		return $this->categories;
	}

	/**
	 * Gets the feature input schema.
	 *
	 * @since 0.1.0
	 * @return array The feature input schema.
	 */
	public function get_input_schema() {
		$transformer_class = apply_filters( 'wp_feature_input_schema_adapter', null, $this );
		$transformer = WP_Feature_Schema_Adapter::make( $transformer_class, $this->input_schema );
		return $transformer->transform();
	}

	/**
	 * Gets the feature output schema.
	 *
	 * @since 0.1.0
	 * @return array The feature output schema.
	 */
	public function get_output_schema() {
		$transformer_class = apply_filters( 'wp_feature_output_schema_adapter', null, $this );
		$transformer = WP_Feature_Schema_Adapter::make( $transformer_class, $this->output_schema );
		return $transformer->transform();
	}

	/**
	 * Gets the feature callback.
	 *
	 * @since 0.1.0
	 * @return callable|null The feature callback.
	 */
	public function get_callback() {
		return $this->callback;
	}

	/**
	 * Gets the feature permission callback.
	 *
	 * @since 0.1.0
	 * @return callable The feature permission callback.
	 */
	public function get_permission_callback() {
		if ( is_callable( $this->permission_callback ) ) {
			return $this->permission_callback;
		}

		return function () {
			return false;
		};
	}

	/**
	 * Determines if the feature is eligible to run.
	 *
	 * @since 0.1.0
	 * @return bool True if the feature is eligible, false otherwise.
	 */
	public function is_eligible() {
		if ( ! is_callable( $this->is_eligible ) ) {
			return true;
		}

		return call_user_func( $this->is_eligible );
	}

	/**
	 * Gets the feature location.
	 *
	 * @since 0.1.0
	 * @return array The feature location.
	 */
	public function get_location() {
		return self::LOCATION_SERVER;
	}

	/**
	 * Whether the feature has a REST alias.
	 *
	 * @since 0.1.0
	 * @return bool Whether the feature has a REST alias.
	 */
	public function has_rest_alias() {
		return $this->rest_alias;
	}

	/**
	 * Gets the REST method for the feature based on the feature type.
	 *
	 * @since 0.1.0
	 * @return string The REST method.
	 */
	public function get_rest_method() {
		if ( self::TYPE_TOOL === $this->type ) {
			return WP_REST_Server::CREATABLE;
		}

		return WP_REST_Server::READABLE;
	}

	/**
	 * Calls the feature.
	 *
	 * @since 0.1.0
	 * @param array|WP_REST_Request $rest_request The input to run the feature with. Can be an array of parameters or a WP_REST_Request object.
	 * @return mixed The result of running the feature.
	 */
	public function call( $rest_request ) {
		/**
		 * Filters the context before running a feature.
		 *
		 * @since 0.1.0
		 * @param array      $context The context to run the feature with.
		 * @param WP_Feature $feature The feature object.
		 */
		$context = $rest_request->get_params();
		$context = apply_filters( 'wp_feature_pre_run_context', $context, $this );
		$context = apply_filters( $this->get_filter_id() . '_pre_run_context', $context, $this );

		// Remove the _locale parameter, which is added by @wordpress/api-fetch.
		unset( $context['_locale'] );

		// If no callback is set, return the context as is.
		if ( ! is_callable( $this->callback ) ) {
			return $context;
		}

		// Validate the input against the schema if available.
		if ( ! empty( $this->input_schema ) ) {
			$valid = $this->validate_input( $context );
			if ( is_wp_error( $valid ) ) {
				/**
				 * Fires when input validation fails for a feature.
				 *
				 * @since 0.1.0
				 * @param WP_Error   $valid   The validation error.
				 * @param array      $context The context that was validated.
				 * @param WP_Feature $feature The feature object.
				 */
				do_action( 'wp_feature_input_validation_failed', $valid, $context, $this );
				do_action( $this->get_filter_id() . '_input_validation_failed', $valid, $context, $this );
				return $valid;
			}
		}

		/**
		 * Fires before a feature is run.
		 *
		 * @since 0.1.0
		 * @param array      $context The context to run the feature with.
		 * @param WP_Feature $feature The feature object.
		 */
		do_action( 'wp_feature_before_run', $context, $this );
		do_action( $this->get_filter_id() . '_before_run', $context, $this );
		$result = $context;
		if ( $this->is_rest_alias() ) {
			$rest_request->set_route( $this->get_rest_alias_route( $result ) );
			$response = rest_get_server()->dispatch( $rest_request );
			$result = $response->get_data();
		} elseif ( is_callable( $this->callback ) ) {
			$result = call_user_func( $this->callback, $context );
		}

		/**
		 * Filters the result after running a feature.
		 *
		 * @since 0.1.0
		 * @param mixed      $result  The result of running the feature.
		 * @param array      $context The context the feature was run with.
		 * @param WP_Feature $feature The feature object.
		 */
		$result = apply_filters( 'wp_feature_run_result', $result, $context, $this );
		$result = apply_filters( $this->get_filter_id() . '_run_result', $result, $context, $this );

		// Validate the output against the schema if available.
		if ( ! empty( $this->output_schema ) ) {
			$valid = $this->validate_output( $result );
			if ( is_wp_error( $valid ) ) {
				/**
				 * Fires when output validation fails for a feature.
				 *
				 * @since 0.1.0
				 * @param WP_Error   $valid   The validation error.
				 * @param mixed      $result  The result that was validated.
				 * @param WP_Feature $feature The feature object.
				 */
				do_action( 'wp_feature_output_validation_failed', $valid, $result, $this );
				do_action( $this->get_filter_id() . '_output_validation_failed', $valid, $result, $this );
				return $valid;
			}
		}

		/**
		 * Fires after a feature is run.
		 *
		 * @since 0.1.0
		 * @param mixed      $result  The result of running the feature.
		 * @param array      $context The context the feature was run with.
		 * @param WP_Feature $feature The feature object.
		 */
		do_action( 'wp_feature_after_run', $result, $context, $this );
		do_action( $this->get_filter_id() . '_after_run', $result, $context, $this );

		return $result;
	}

	/**
	 * Sets the properties of the feature.
	 *
	 * @since 0.1.0
	 * @param array $args The feature arguments.
	 * @return WP_Error|true WP_Error if validation fails, true otherwise.
	 */
	private function set_props( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'type'         => self::TYPE_DEFAULT,
				'name'         => '',
				'description'  => '',
				'meta'         => array(),
				'categories'   => array(),
				'input_schema' => array(),
				'output_schema' => array(),
				'callback'     => null,
				'permission_callback' => null,
				'is_eligible'       => null,
				'rest_alias'   => false,
			)
		);

		if ( empty( $args['name'] ) ) {
			return new WP_Error( 'missing_name', __( 'Feature name is required.', 'wp-feature-api' ) );
		}

		if ( empty( $args['description'] ) ) {
			return new WP_Error( 'missing_description', __( 'Feature description is required.', 'wp-feature-api' ) );
		}

		if ( ! in_array( $args['type'], self::TYPES, true ) ) {
			return new WP_Error(
				'invalid_type',
				sprintf(
					/* translators: %s: Feature type */
					__( 'Feature type must be one of: %s', 'wp-feature-api' ),
					implode( ', ', self::TYPES )
				)
			);
		}

		// Sanitize text fields.
		$this->name        = sanitize_text_field( $args['name'] );
		$this->description = sanitize_text_field( $args['description'] );
		$this->type        = $args['type'];

		// Meta should be an array.
		$this->meta = is_array( $args['meta'] ) ? $args['meta'] : array();

		// Categories should be an array.
		$this->categories = is_array( $args['categories'] ) ? $args['categories'] : array();

		// Schema values should be arrays.
		$this->input_schema  = is_array( $args['input_schema'] ) ? $args['input_schema'] : array();
		$this->output_schema = is_array( $args['output_schema'] ) ? $args['output_schema'] : array();

		// Callback must be callable or null.
		$this->callback = is_callable( $args['callback'] ) || null === $args['callback'] ? $args['callback'] : null;

		// Permissions can be string, array, or callable.
		$this->permission_callback = $args['permission_callback'];

		// Filter must be callable or null.
		$this->is_eligible = is_callable( $args['is_eligible'] ) || null === $args['is_eligible'] ? $args['is_eligible'] : null;

		// Rest alias must be false or a string.
		$this->rest_alias = false === $args['rest_alias'] || is_string( $args['rest_alias'] ) ? $args['rest_alias'] : false;

		return true;
	}


	/**
	 * Validates the input against the schema.
	 *
	 * @since 0.1.0
	 * @param array $input The input to validate.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	private function validate_input( $input ) {
		if ( ! is_array( $input ) ) {
			return new WP_Error(
				'invalid_input_type',
				__( 'Input must be an array.', 'wp-feature-api' )
			);
		}

		/**
		 * Filters the input schema before validation.
		 *
		 * @since 0.1.0
		 * @param array      $input_schema The input schema.
		 * @param array      $input        The input to validate.
		 * @param WP_Feature $feature      The feature object.
		 */
		$schema = apply_filters( 'wp_feature_input_schema_validate', $this->get_input_schema(), $input, $this );
		$schema = apply_filters( $this->get_filter_id() . '_input_schema_validate', $schema, $input, $this );

		return rest_validate_value_from_schema( $input, $schema );
	}

	/**
	 * Validates the output against the schema.
	 *
	 * @since 0.1.0
	 * @param mixed $output The output to validate.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	private function validate_output( $output ) {
		/**
		 * Filters the output schema before validation.
		 *
		 * @since 0.1.0
		 * @param array      $output_schema The output schema.
		 * @param mixed      $output        The output to validate.
		 * @param WP_Feature $feature       The feature object.
		 */
		$schema = apply_filters( 'wp_feature_output_schema_validate', $this->output_schema, $output, $this );
		$schema = apply_filters( $this->get_filter_id() . '_output_schema_validate', $schema, $output, $this );

		return rest_validate_value_from_schema( $output, $schema );
	}

	/**
	 * Converts the feature to an array.
	 *
	 * @since 0.1.0
	 * @return array The feature as an array.
	 */
	public function to_array() {
		$feature_data = array(
			'id'            => $this->get_id(),
			'name'          => $this->name,
			'description'   => $this->description,
			'type'          => $this->type,
			'meta'          => $this->meta,
			'categories'    => $this->categories,
			'input_schema'  => $this->get_input_schema(),
			'output_schema' => $this->get_output_schema(),
			'location'      => $this->get_location(),
		);

		/**
		 * Filters the feature data when converting to an array.
		 *
		 * @since 0.1.0
		 * @param array      $feature_data The feature data.
		 * @param WP_Feature $feature      The feature object.
		 */
		$feature_data = apply_filters( 'wp_feature_to_array', $feature_data, $this );
		$feature_data = apply_filters( $this->get_filter_id() . '_to_array', $feature_data, $this );

		return $feature_data;
	}

	/**
	 * Converts the feature to a JSON serializable array.
	 *
	 * @since 0.1.0
	 * @return array The feature as a JSON serializable array.
	 */
	public function jsonSerialize(): mixed {
		return $this->to_array();
	}

	/**
	 * Checks if the feature is a REST alias.
	 *
	 * @since 0.1.0
	 * @return bool Whether the feature is a REST alias.
	 */
	public function is_rest_alias() {
		return false !== $this->rest_alias;
	}

	/**
	 * Gets the alternate types for the feature.
	 *
	 * @since 0.1.0
	 * @return array The alternate types.
	 */
	public function get_alternate_types() {
		$alternate_types = array_diff( self::TYPES, array( $this->type ) );
		$alternate_features = array();
		foreach ( $alternate_types as $type ) {
			$feature = wp_feature_registry()->find( $this->id, $type );
			if ( $feature instanceof WP_Feature ) {
				$alternate_features[] = $feature;
			}
		}

		return $alternate_features;
	}

	/**
	 * Sets the feature from a REST alias.
	 *
	 * @since 0.1.0
	 * @return WP_Feature The feature object or WP_Error if the REST alias is not found.
	 */
	public function set_from_rest_alias() {
		$rest_alias = $this->get_rest_alias();

		if ( is_wp_error( $rest_alias ) ) {
			return $rest_alias;
		}

		if ( isset( $rest_alias['args'] ) ) {
			$properties = ! isset( $this->input_schema['properties'] ) ? array() : $this->input_schema['properties'];
			if ( ! empty( $rest_alias['args'] ) ) {
				$this->input_schema = array(
					'type' => 'object',
					'properties' => array_merge( $rest_alias['args'], $properties ),
				);
			}
		}

		if ( isset( $rest_alias['schema'] ) ) {
			$this->output_schema = $rest_alias['schema'];
		}

		if ( isset( $rest_alias['permission_callback'] ) ) {
			$this->permission_callback = $rest_alias['permission_callback'];
		}

		if ( isset( $rest_alias['callback'] ) && is_callable( $rest_alias['callback'] ) ) {
			$this->callback = $rest_alias['callback'];
		}

		return $this;
	}

	/**
	 * Checks if a feature has a REST alias.
	 *
	 * @since 0.1.0
	 * @return array|WP_Error The REST alias if found, WP_Error otherwise.
	 */
	public function get_rest_alias() {
		$routes = rest_get_server()->get_routes();
		$feature_route = $this->rest_alias;

		if ( ! isset( $routes[ $feature_route ] ) ) {
			return new WP_Error(
				'rest_alias_not_found',
				sprintf(
					/* translators: %1$s: Feature name, %2$s: REST route path */
					__( 'REST API alias of feature (%1$s) not found. Check if the REST route %2$s exists.', 'wp-feature-api' ),
					$this->id,
					$feature_route
				),
				array( 'status' => 404 )
			);
		}

		$method = $this->get_rest_method();

		foreach ( $routes[ $feature_route ] as $route_handler ) {
			$methods = array_keys( $route_handler['methods'] );
			if ( in_array( $method, $methods, true ) ) {
				return $route_handler;
			}
		}

		return new WP_Error(
			'rest_alias_method_not_supported',
			sprintf(
				/* translators: %1$s: Feature name, %2$s: HTTP method */
				__( 'REST API alias of feature (%1$s) does not support the method %2$s.', 'wp-feature-api' ),
				$this->get_id(),
				$method
			),
			array( 'status' => 405 )
		);
	}

	/**
	 * Gets the REST alias route by using available context
	 * to hydrate the route.
	 *
	 * @since 0.1.0
	 * @param array $data The data to use for hydrating the route.
	 * @return string The hydrated REST alias route.
	 */
	private function get_rest_alias_route( $data ) {
		$route = $this->rest_alias;

		// Find all named capture groups in the route pattern.
		if ( preg_match_all( '/\(\?P<([^>]+)>[^)]+\)/', $route, $matches ) ) {
			$param_names = $matches[1];

			// Replace each parameter with its value from data if available.
			foreach ( $param_names as $param_name ) {
				if ( isset( $data[ $param_name ] ) ) {
					$pattern = '/\(\?P<' . $param_name . '>[^)]+\)/';
					$route = preg_replace( $pattern, $data[ $param_name ], $route );
				}
			}
		}

		return $route;
	}

	/**
	 * Gets the filter ID for use in actions and filters following the WordPress filter naming convention.
	 *
	 * @since 0.1.0
	 * @return string The filter ID.
	 */
	private function get_filter_id() {
		return 'wp_feature_' . sanitize_key( str_replace( '/', '_', $this->id ) );
	}

	/**
	 * Validates the feature ID.
	 *
	 * Ensures the ID follows the required pattern: lowercase alphanumeric characters,
	 * hyphens, and slashes for namespacing. The ID cannot start or end with a slash,
	 * and cannot contain consecutive slashes.
	 *
	 * Examples of valid IDs:
	 * - 'example'
	 * - 'demo/site-info'
	 * - 'namespace/feature-name/sub-feature'
	 *
	 * Examples of invalid IDs:
	 * - 'UPPERCASE'      (contains uppercase)
	 * - '/leading-slash' (starts with slash)
	 * - 'trailing-slash/' (ends with slash)
	 * - 'double//slash'  (contains consecutive slashes)
	 * - 'special@chars'  (contains special characters)
	 *
	 * @since 0.1.0
	 * @param string $id The feature ID to validate.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	private function validate_id( $id ) {
		if ( ! preg_match( '/^' . self::ID_PATTERN . '$/', $id ) ) {
			return new WP_Error(
				'invalid_feature_id',
				sprintf(
					/* translators: %s: Feature ID */
					__( 'Feature ID must contain only lowercase alphanumeric characters, hyphens, and slashes. Received: %s', 'wp-feature-api' ),
					$id
				)
			);
		}

		if ( substr( $id, 0, 1 ) === '/' || substr( $id, -1 ) === '/' || strpos( $id, '//' ) !== false ) {
			return new WP_Error(
				'invalid_feature_id_format',
				sprintf(
					/* translators: %s: Feature ID */
					__( 'Feature ID cannot start or end with a slash, or contain consecutive slashes. Received: %s', 'wp-feature-api' ),
					$id
				)
			);
		}

		return true;
	}

	/**
	 * Registers data from the REST alias if it exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function register_rest_alias() {
		if ( $this->is_rest_alias() ) {
			$this->set_from_rest_alias();
		}
	}
}

<?php
/**
 * WP_Feature class file.
 *
 * @package WordPress\Features_API
 */

/**
 * Class WP_Feature
 *
 * Represents a feature in the WordPress Features API.
 *
 * @since 0.1.0
 */
class WP_Feature {
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
	 * The feature permissions.
	 *
	 * @since 0.1.0
	 * @var string|array|callable
	 */
	private $permissions;

	/**
	 * The feature filter.
	 *
	 * @since 0.1.0
	 * @var callable
	 */
	private $filter;

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

		$this->id = sanitize_key( $id );
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
				'type'         => 'resource',
				'name'         => '',
				'description'  => '',
				'meta'         => array(),
				'categories'   => array(),
				'input_schema' => array(),
				'output_schema' => array(),
				'callback'     => null,
				'permissions'  => '',
				'filter'       => null,
			)
		);

		if ( empty( $args['name'] ) ) {
			return new WP_Error( 'missing_name', __( 'Feature name is required.', 'wp-feature-api' ) );
		}

		if ( empty( $args['description'] ) ) {
			return new WP_Error( 'missing_description', __( 'Feature description is required.', 'wp-feature-api' ) );
		}

		if ( ! in_array( $args['type'], self::TYPES, true ) ) {
			return new WP_Error( 'invalid_type', __( 'Feature type must be either "resource" or "tool".', 'wp-feature-api' ) );
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
		$this->permissions = $args['permissions'];

		// Filter must be callable or null.
		$this->filter = is_callable( $args['filter'] ) || null === $args['filter'] ? $args['filter'] : null;

		return true;
	}

	/**
	 * Gets the feature ID.
	 *
	 * @since 0.1.0
	 * @return string The feature ID.
	 */
	public function get_id() {
		return $this->id;
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
		return $this->input_schema;
	}

	/**
	 * Gets the feature output schema.
	 *
	 * @since 0.1.0
	 * @return array The feature output schema.
	 */
	public function get_output_schema() {
		return $this->output_schema;
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
	 * Gets the feature permissions.
	 *
	 * @since 0.1.0
	 * @return string|array|callable The feature permissions.
	 */
	public function get_permissions() {
		/**
		 * Filters the feature permissions.
		 *
		 * @since 0.1.0
		 * @param string|array|callable $permissions The feature permissions.
		 * @param WP_Feature           $feature     The feature object.
		 */
		$permissions = apply_filters( 'wp_feature_permissions', $this->permissions, $this );
		$permissions = apply_filters( $this->get_filter_id() . '_permissions', $permissions, $this );

		return $permissions;
	}

	/**
	 * Gets the feature filter.
	 *
	 * @since 0.1.0
	 * @return callable|null The feature filter.
	 */
	public function get_filter() {
		return $this->filter;
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
	 * Runs the feature.
	 *
	 * @since 0.1.0
	 * @param array $context The context to run the feature with.
	 * @return mixed The result of running the feature.
	 */
	public function run( $context = array() ) {
		/**
		 * Filters the context before running a feature.
		 *
		 * @since 0.1.0
		 * @param array      $context The context to run the feature with.
		 * @param WP_Feature $feature The feature object.
		 */
		$context = apply_filters( 'wp_feature_pre_run_context', $context, $this );
		$context = apply_filters( $this->get_filter_id() . '_pre_run_context', $context, $this );

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
		// Run the feature callback.
		$result = call_user_func( $this->callback, $context );

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
		$schema = apply_filters( 'wp_feature_input_schema_validate', $this->input_schema, $input, $this );
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
			'id'            => $this->id,
			'name'          => $this->name,
			'description'   => $this->description,
			'type'          => $this->type,
			'meta'          => $this->meta,
			'categories'    => $this->categories,
			'input_schema'  => $this->input_schema,
			'output_schema' => $this->output_schema,
			'permissions'   => $this->permissions,
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
	 * Gets the filter ID for use in actions and filters following the WordPress filter naming convention.
	 *
	 * @since 0.1.0
	 * @return string The filter ID.
	 */
	private function get_filter_id() {
		return 'wp_feature_' . sanitize_key( str_replace( '/', '_', $this->id ) );
	}
}

<?php
/**
 * WP_Feature_Query class file.
 *
 * @package WordPress\Features_API
 */

/**
 * Class WP_Feature_Query
 *
 * A query class for WordPress features.
 *
 * @since 0.1.0
 */
class WP_Feature_Query {
	/**
	 * The query arguments.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $args = array();

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 * @param array $args Query arguments.
	 */
	public function __construct( $args = array() ) {
		$this->args = wp_parse_args(
			$args,
			array(
				'type'       => array(),
				'categories' => array(),
				'location'   => array(),
				'input_schema' => array(),
				'output_schema' => array(),
				'search'     => '',
			)
		);
	}

	/**
	 * Get the schema for the query.
	 *
	 * @since 0.1.0
	 * @return array The schema.
	 */
	public static function schema() {
		return array(
			'type' => array(
				'description' => __( 'Filter features by their type.', 'features-api' ),
				'type' => 'array',
				'items' => array(
					'type' => 'string',
					'enum' => WP_Feature::TYPES,
				),
			),
			'categories' => array(
				'description' => __( 'Filter features by their categories.', 'features-api' ),
				'type' => 'array',
				'items' => array(
					'type' => 'string',
				),
			),
			'location' => array(
				'description' => __( 'Filter features by their location (client/server).', 'features-api' ),
				'type' => 'array',
				'items' => array(
					'type' => 'string',
					'enum' => WP_Feature::LOCATIONS,
				),
			),
			'input_schema' => array(
				'description' => __( 'Filter features by their input schema.', 'features-api' ),
				'type' => 'object',
				'properties' => array(
					'match' => array(
						'type' => 'string',
						'enum' => array( 'all', 'any' ),
						'default' => 'any',
					),
					'fields' => array(
						'type' => 'array',
						'items' => array(
							'type' => 'string',
						),
					),
					'required' => array(
						'fields',
					),
				),
			),
			'output_schema' => array(
				'description' => __( 'Filter features by their output schema.', 'features-api' ),
				'type' => 'object',
				'properties' => array(
					'match' => array(
						'type' => 'string',
						'enum' => array( 'all', 'any' ),
						'default' => 'any',
					),
					'fields' => array(
						'type' => 'array',
						'items' => array(
							'type' => 'string',
						),
					),
					'required' => array(
						'fields',
					),
				),
			),
			'search' => array(
				'description' => __( 'Search features by name, description, or ID.', 'features-api' ),
				'type' => 'string',
			),
		);
	}

	/**
	 * Gets the query arguments.
	 *
	 * @since 0.1.0
	 * @return array The query arguments.
	 */
	public function get_args() {
		return $this->args;
	}

	/**
	 * Checks if a feature matches the query.
	 *
	 * @since 0.1.0
	 * @param WP_Feature $feature The feature to check.
	 * @return bool True if the feature matches the query, false otherwise.
	 */
	public function matches( $feature ) {
		// Check type.
		if ( ! empty( $this->args['type'] ) && ! in_array( $feature->get_type(), $this->args['type'], true ) ) {
			return false;
		}

		// Check location.
		if ( ! empty( $this->args['location'] ) && ! in_array( $feature->get_location(), $this->args['location'], true ) ) {
			return false;
		}

		// Check categories.
		if ( ! empty( $this->args['categories'] ) ) {
			$feature_categories = $feature->get_categories();
			$matches_category   = true;
			foreach ( $this->args['categories'] as $category ) {
				if ( ! in_array( $category, $feature_categories, true ) ) {
					return false;
				}
			}
		}

		// Check input schema.
		if ( ! empty( $this->args['input_schema'] ) ) {
			if ( 'any' === $this->args['input_schema']['match'] ) {
				return $this->matches_any_fields( $feature->get_input_schema(), $this->args['input_schema']['fields'] );
			}

			return $this->matches_all_fields( $feature->get_input_schema(), $this->args['input_schema']['fields'] );
		}

		// Check output schema.
		if ( ! empty( $this->args['output_schema'] ) ) {
			if ( 'any' === $this->args['output_schema']['match'] ) {
				return $this->matches_any_fields( $feature->get_output_schema(), $this->args['output_schema']['fields'] );
			}

			return $this->matches_all_fields( $feature->get_output_schema(), $this->args['output_schema']['fields'] );
		}

		// Check search.
		if ( ! empty( $this->args['search'] ) ) {
			return $this->search( $feature, $this->args['search'] );
		}

		return true;
	}

	/**
	 * Checks if the schema matches any of the provided fields.
	 *
	 * @since 0.1.0
	 * @param array $schema The schema to check.
	 * @param array $fields The fields to check for.
	 * @return bool True if the schema matches any of the fields, false otherwise.
	 */
	private function matches_any_fields( $schema, $fields ) {
		$schema_properties = array_keys( $schema );
		if ( empty( $fields ) || empty( $schema_properties ) ) {
			return false;
		}

		foreach ( $fields as $field ) {
			if ( in_array( $field, $schema_properties, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if the schema matches all of the provided fields.
	 *
	 * @since 0.1.0
	 * @param array $schema The schema to check.
	 * @param array $fields The fields that must be present.
	 * @return bool True if the schema matches all of the fields, false otherwise.
	 */
	private function matches_all_fields( $schema, $fields ) {
		$schema_properties = array_keys( $schema );
		if ( empty( $fields ) || empty( $schema_properties ) ) {
			return false;
		}

		foreach ( $fields as $field ) {
			if ( ! in_array( $field, $schema_properties, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks if the feature matches the search query.
	 *
	 * @since 0.1.0
	 * @param WP_Feature $feature The feature to check.
	 * @param string     $search The search query.
	 * @return bool True if the feature matches the search query, false otherwise.
	 */
	private function search( $feature, $search ) {
		$search = $this->normalize_search_term( $search );
		$name   = $this->normalize_search_term( $feature->get_name() );
		$desc   = $this->normalize_search_term( $feature->get_description() );
		$id     = $this->normalize_search_term( $feature->get_id() );

		$search_field_results = array(
			'name'   => false !== strpos( $name, $search ),
			'desc'   => false !== strpos( $desc, $search ),
			'id'     => false !== strpos( $id, $search ),
		);

		return ! empty( array_filter( $search_field_results ) );
	}

	/**
	 * Normalizes a search term.
	 * Removes all non-alphanumeric characters, spaces, and converts to lowercase.
	 *
	 * @since 0.1.0
	 * @param string $term The term to normalize.
	 * @return string The normalized term.
	 */
	private function normalize_search_term( $term ) {
		return strtolower( preg_replace( '/[^a-zA-Z0-9\s]/', '', $term ) );
	}
}

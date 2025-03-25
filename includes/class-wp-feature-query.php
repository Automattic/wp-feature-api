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
				'search'     => '',
				'id'         => array(),
			)
		);

		// Normalize array arguments.
		foreach ( array( 'type', 'categories', 'location', 'id' ) as $key ) {
			if ( ! is_array( $this->args[ $key ] ) ) {
				$this->args[ $key ] = array( $this->args[ $key ] );
			}

			// Remove empty values.
			$this->args[ $key ] = array_filter( $this->args[ $key ] );
		}
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

		// Check ID.
		if ( ! empty( $this->args['id'] ) && ! in_array( $feature->get_id(), $this->args['id'], true ) ) {
			return false;
		}

		// Check location.
		if ( ! empty( $this->args['location'] ) && ! in_array( $feature->get_location(), $this->args['location'], true ) ) {
			return false;
		}

		// Check categories.
		if ( ! empty( $this->args['categories'] ) ) {
			$feature_categories = $feature->get_categories();
			$matches_category   = false;

			foreach ( $this->args['categories'] as $category ) {
				if ( in_array( $category, $feature_categories, true ) ) {
					$matches_category = true;
					break;
				}
			}

			if ( ! $matches_category ) {
				return false;
			}
		}

		// Check search.
		if ( ! empty( $this->args['search'] ) ) {
			$search = strtolower( $this->args['search'] );
			$name   = strtolower( $feature->get_name() );
			$desc   = strtolower( $feature->get_description() );
			$id     = strtolower( $feature->get_id() );

			if ( false === strpos( $name, $search ) && false === strpos( $desc, $search ) && false === strpos( $id, $search ) ) {
				return false;
			}
		}

		// Apply custom filter if set.
		if ( isset( $this->args['filter'] ) && is_callable( $this->args['filter'] ) ) {
			return call_user_func( $this->args['filter'], $feature );
		}

		return true;
	}
}

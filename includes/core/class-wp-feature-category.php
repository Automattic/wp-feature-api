<?php
/**
 * WP_Feature_Category class file.
 *
 * @package WordPress\Features_API
 */

/**
 * Class WP_Feature_Category
 *
 * Represents a feature category in the WordPress Feature API.
 *
 * @since 0.1.0
 */
class WP_Feature_Category {
	/**
	 * Category slug.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $slug;

	/**
	 * Category name.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $name;

	/**
	 * Category description.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $description;

	/**
	 * Number of features in this category.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private $feature_count = 0;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 * @param string|array $category Category data.
	 */
	public function __construct( $category ) {
		if ( is_string( $category ) ) {
			$this->from_string( $category );
		} elseif ( is_array( $category ) ) {
			$this->from_array( $category );
		}
	}

	/**
	 * Gets the schema for the category.
	 *
	 * @since 0.1.0
	 * @return array
	 */
	public static function get_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'feature-category',
			'type'       => 'object',
			'properties' => array(
				'slug'          => array(
					'description' => __( 'Unique identifier for the category.', 'wp-feature-api' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'name'          => array(
					'description' => __( 'Display name for the category.', 'wp-feature-api' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'description'   => array(
					'description' => __( 'Description of the category.', 'wp-feature-api' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'feature_count' => array(
					'description' => __( 'Number of features in this category.', 'wp-feature-api' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Gets the category slug.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Gets the category name.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Sets the category name.
	 *
	 * @since 0.1.0
	 * @param string $name Category name.
	 * @return void
	 */
	public function set_name( $name ) {
		$this->name = sanitize_text_field( $name );
	}

	/**
	 * Gets the category description.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Sets the category description.
	 *
	 * @since 0.1.0
	 * @param string $description Category description.
	 * @return void
	 */
	public function set_description( $description ) {
		$this->description = wp_kses_post( $description );
	}

	/**
	 * Gets the feature count.
	 *
	 * @since 0.1.0
	 * @return int
	 */
	public function get_feature_count() {
		return $this->feature_count;
	}

	/**
	 * Increments the feature count.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function increment_count() {
		++$this->feature_count;
	}

	/**
	 * Decrements the feature count.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function decrement_count() {
		if ( $this->feature_count > 0 ) {
			--$this->feature_count;
		}
	}

	/**
	 * Converts the category to an array.
	 *
	 * @since 0.1.0
	 * @return array
	 */
	public function to_array() {
		return array(
			'slug'          => $this->slug,
			'name'          => $this->name,
			'description'   => $this->description,
			'feature_count' => $this->feature_count,
		);
	}

	/**
	 * Creates a new category instance.
	 *
	 * @since 0.1.0
	 * @param string|array|WP_Feature_Category $category Category data.
	 * @return WP_Feature_Category|null Category instance or null on failure.
	 */
	public static function make( $category ) {
		if ( $category instanceof WP_Feature_Category ) {
			return $category;
		}

		if ( is_string( $category ) || is_array( $category ) ) {
			return new self( $category );
		}

		return null;
	}

	/**
	 * Creates a category instance from string.
	 *
	 * @since 0.1.0
	 * @param string $category Category slug.
	 */
	private function from_string( $category ) {
		$this->slug = sanitize_key( $category );
		$this->name = ucwords( str_replace( array( '-', '_' ), ' ', $this->slug ) );
		$this->description = '';
	}

	/**
	 * Creates a category instance from array.
	 *
	 * @since 0.1.0
	 * @param array $category Category data.
	 */
	private function from_array( $category ) {
		if ( ! isset( $category['slug'] ) ) {
			return;
		}

		$this->slug = sanitize_key( $category['slug'] );
		$this->name = isset( $category['name'] )
			? sanitize_text_field( $category['name'] )
			: ucwords( str_replace( array( '-', '_' ), ' ', $this->slug ) );
		$this->description = isset( $category['description'] )
			? wp_kses_post( $category['description'] )
			: '';
	}
}

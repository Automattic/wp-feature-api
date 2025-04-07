<?php
/**
 * WP_Feature_Categories class file.
 *
 * @package WordPress\Features_API
 */

/**
 * Class WP_Feature_Categories
 *
 * Manages a collection of feature categories.
 *
 * @since 0.1.0
 */
final class WP_Feature_Categories {

	/**
	 * Array of category objects.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $categories = array();

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->register_default_categories();
	}

	/**
	 * Gets all categories.
	 *
	 * @since 0.1.0
	 * @return array Array of WP_Feature_Category objects.
	 */
	public function get_all() {
		return $this->categories;
	}

	/**
	 * Gets a category by slug.
	 *
	 * @since 0.1.0
	 * @param string $slug Category slug.
	 * @return WP_Feature_Category|null Category object or null if not found.
	 */
	public function get( $slug ) {
		return isset( $this->categories[ $slug ] ) ? $this->categories[ $slug ] : null;
	}

	/**
	 * Adds or updates a category.
	 *
	 * @since 0.1.0
	 *
	 * @param string|array|WP_Feature_Category $category Category data.
	 * @return WP_Feature_Category|WP_Error Category object on success, WP_Error on failure.
	 */
	public function add( $category ) {
		$cat = WP_Feature_Category::make( $category );

		if ( ! $cat ) {
			return new WP_Error(
				'invalid_category',
				__( 'Invalid category data provided.', 'wp-feature-api' )
			);
		}

		$slug = $cat->get_slug();

		if ( isset( $this->categories[ $slug ] ) ) {
			$this->update_existing_category( $this->categories[ $slug ], $cat );
			$cat = $this->categories[ $slug ];
		} else {
			$this->categories[ $slug ] = $cat;
		}

		/**
		 * Fires after a category is added or updated.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_Feature_Category $cat     The category object.
		 * @param bool               $is_update True if this was an update, false if a new category.
		 */
		do_action( 'wp_feature_category_added', $cat, isset( $this->categories[ $slug ] ) );

		return $cat;
	}

	/**
	 * Updates an existing category with new data.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Feature_Category $existing Existing category object.
	 * @param WP_Feature_Category $updating New category object with updated data.
	 */
	private function update_existing_category( $existing, $updating ) {
		if ( ! empty( $updating->get_name() ) ) {
			$existing->set_name( $updating->get_name() );
		}
		if ( ! empty( $updating->get_description() ) ) {
			$existing->set_description( $updating->get_description() );
		}
	}

	/**
	 * Removes a category.
	 *
	 * @since 0.1.0
	 *
	 * @param string|array|WP_Feature_Category $category Category to remove.
	 * @return bool True if category was removed, false if category had features
	 */
	public function remove( $category ) {
		$category_obj = WP_Feature_Category::make( $category );

		if ( ! $category_obj ) {
			return new WP_Error(
				'invalid_category',
				__( 'Invalid category data provided.', 'wp-feature-api' )
			);
		}

		$slug = $category_obj->get_slug();

		if ( ! isset( $this->categories[ $slug ] ) ) {
			return false;
		}

		$existing = $this->categories[ $slug ];
		$feature_count = $existing->get_feature_count();

		if ( $feature_count > 0 ) {
			return false;
		}

		unset( $this->categories[ $slug ] );

		/**
		 * Fires after a category is removed.
		 *
		 * @since 0.1.0
		 *
		 * @param string $slug          The category slug that was removed.
		 * @param int    $feature_count The number of features that were associated with this category.
		 */
		do_action( 'wp_feature_category_removed', $slug, $feature_count );

		return true;
	}

	/**
	 * Updates categories for a feature.
	 *
	 * @since 0.1.0
	 * @param WP_Feature $feature Feature object.
	 * @return void
	 */
	public function update_for_feature( $feature ) {
		if ( ! $feature instanceof WP_Feature ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: WP_Feature */
					__( 'The feature must be an instance of %s.', 'wp-feature-api' ),
					'WP_Feature'
				),
				'0.1.0'
			);
		}

		$feature_categories = $feature->get_categories();

		if ( empty( $feature_categories ) ) {
			return;
		}

		foreach ( $feature_categories as $category ) {
			$category_obj = $this->add( $category );
			if ( $category_obj ) {
				$category_obj->increment_count();
			}
		}
	}

	/**
	 * Removes categories for a feature.
	 *
	 * @since 0.1.0
	 * @param WP_Feature $feature Feature object.
	 * @return void
	 */
	public function remove_for_feature( $feature ) {
		if ( ! $feature instanceof WP_Feature ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: WP_Feature */
					__( 'The feature must be an instance of %s.', 'wp-feature-api' ),
					'WP_Feature'
				),
				'0.1.0'
			);
		}

		$feature_categories = $feature->get_categories();

		if ( empty( $feature_categories ) ) {
			return;
		}

		foreach ( $feature_categories as $category ) {
			$result = $this->remove( $category );
			if ( $result ) {
				$category->decrement_count();
			}
		}
	}

	/**
	 * Registers default categories.
	 *
	 * @since 0.1.0
	 */
	private function register_default_categories() {
		/**
		 * Filters the default categories to be registered.
		 *
		 * Expected format:
		 * array(
		 *     'category-slug' => array(
		 *         'name'        => 'Category Name',
		 *         'description' => 'Category Description',
		 *     ),
		 * )
		 *
		 * @since 0.1.0
		 * @param array $default_categories Array of default categories.
		 */
		$default_categories = apply_filters( 'wp_feature_default_categories', array() );

		foreach ( $default_categories as $slug => $category ) {
			$category_data = array_merge(
				array( 'slug' => $slug ),
				$category
			);
			$this->add( $category_data );
		}
	}
}

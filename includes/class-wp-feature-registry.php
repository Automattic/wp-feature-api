<?php
/**
 * WP_Feature_Registry class file.
 *
 * @package WordPress\Features_API
 */

/**
 * Class WP_Feature_Registry
 *
 * A singleton registry for WordPress features.
 *
 * @since 0.1.0
 */
class WP_Feature_Registry {

	/**
	 * The singleton instance of the registry.
	 *
	 * @since 0.1.0
	 * @var WP_Feature_Registry
	 */
	private static $instance = null;

	/**
	 * The feature repository.
	 *
	 * @since 0.1.0
	 * @var WP_Feature_Repository_Interface
	 */
	private $repository = null;

	/**
	 * In-memory cache of feature IDs.
	 * Separated by type.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $features = array();

	/**
	 * @todo: keep track of categories
	 * this will be important for use in inference using an LLM to narrow down the features by category.
	 * should categories contain descriptions?
	 */
	private $categories = array();

	/**
	 * Private constructor to prevent direct instantiation.
	 * Sets the repository to use for the registry.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/interface-wp-feature-repository.php';
		require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/class-wp-feature-repository-memory.php';

		$default_repository = new WP_Feature_Repository_Memory();
		$repository = apply_filters( 'wp_feature_repository', $default_repository );

		if ( ! $repository instanceof WP_Feature_Repository_Interface ) {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					/* translators: %s: WP_Feature_Repository_Interface */
					__( 'The repository must implement %s. Falling back to default repository.', 'wp-feature-api' ),
					'WP_Feature_Repository_Interface'
				),
				'0.1.0'
			);
			$repository = $default_repository;
		}

		$this->repository = $repository;
		$this->cache_clear();
	}

	/**
	 * Gets the singleton instance of the registry.
	 *
	 * @since 0.1.0
	 * @return WP_Feature_Registry The singleton instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers a feature.
	 *
	 * @since 0.1.0
	 * @param WP_Feature|array $feature The feature to register.
	 * @return bool True if the feature was registered, false otherwise.
	 */
	public function register( $feature ) {
		$feature = WP_Feature::make( $feature );

		if ( ! $feature ) {
			return false;
		}

		if ( $this->repository->find( $feature ) ) {
			return false;
		}

		$saved = $this->repository->save( $feature );

		if ( ! $saved ) {
			return false;
		}

		if ( ! $this->cache_has( $feature ) ) {
			$this->cache_put( $feature );
		}

		/**
		 * Fires after a feature is registered.
		 *
		 * @since 0.1.0
		 * @param WP_Feature $feature The registered feature.
		 */
		do_action( 'wp_feature_registered', $feature );

		return true;
	}

	/**
	 * Unregisters a feature.
	 *
	 * @since 0.1.0
	 * @param string|WP_Feature $feature The feature ID or feature object to unregister.
	 * @return bool True if the feature was unregistered, false otherwise.
	 */
	public function unregister( $feature ) {
		$feature = WP_Feature::make( $feature );

		if ( ! $feature ) {
			return false;
		}

		$feature_id = $feature->get_id();
		$feature_obj = $this->repository->find( $feature_id );

		if ( ! $feature_obj ) {
			return false;
		}

		$removed = $this->remove( $feature_id );

		if ( ! $removed ) {
			return false;
		}

		/**
		 * Fires after a feature is unregistered.
		 *
		 * @since 0.1.0
		 * @param string    $feature_id The feature ID.
		 * @param WP_Feature $feature    The unregistered feature.
		 */
		do_action( 'wp_feature_unregistered', $feature_id, $feature_obj );

		return true;
	}

	/**
	 * Finds a feature by its ID.
	 *
	 * @since 0.1.0
	 * @param string      $feature_id The feature ID to find.
	 * @param string|null $type The type of feature to find.
	 * @return WP_Feature|null The feature if found, null otherwise.
	 */
	public function find( $feature_id, $type = null ) {
		$feature_id = self::generate_id( $feature_id, $type );

		return $this->repository->find( $feature_id );
	}

	/**
	 * Generates a feature ID.
	 *
	 * @since 0.1.0
	 * @param string $id The feature ID.
	 * @param string $type The type of feature.
	 * @return string The generated feature ID.
	 */
	public static function generate_id( $id, $type ) {
		if ( strpos( $id, $type . '-' ) !== false ) {
			return $id;
		}

		return $type . '-' . $id;
	}

	/**
	 * Gets features based on a query.
	 *
	 * @since 0.1.0
	 * @param WP_Feature_Query|array|null $query The query to filter features by, or null to get all features.
	 * @return array The matching features.
	 */
	public function get( $query = null ) {
		if ( null === $query ) {
			return $this->repository->get_all();
		}

		if ( is_array( $query ) ) {
			$query = new WP_Feature_Query( $query );
		}

		if ( ! $query instanceof WP_Feature_Query ) {
			return array();
		}

		return $this->repository->query( $query );
	}

	/**
	 * Removes a feature from the repository and cache.
	 *
	 * @since 0.1.0
	 * @param string $feature_id The feature ID to remove.
	 * @return bool True if the feature was removed, false otherwise.
	 */
	private function remove( $feature_id ) {
		$deleted = $this->repository->delete( $feature_id );

		if ( ! $deleted ) {
			return false;
		}

		$this->cache_delete( $feature_id );

		return true;
	}

	/**
	 * Clears the feature ID cache.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function cache_clear() {
		$this->features = array();
	}

	/**
	 * Removes a feature ID from the cache.
	 *
	 * @since 0.1.0
	 * @param WP_Feature $feature The feature to remove.
	 * @return void
	 */
	private function cache_delete( $feature ) {
		unset( $this->features[ $feature->get_id() ] );
	}

	/**
	 * Checks if a feature is cached.
	 *
	 * @since 0.1.0
	 * @param WP_Feature $feature The feature to check.
	 * @return bool True if the feature is cached, false otherwise.
	 */
	private function cache_has( $feature ) {
		return isset( $this->features[ $feature->get_id() ] );
	}

	/**
	 * Caches a feature.
	 *
	 * @since 0.1.0
	 * @param WP_Feature $feature The feature to cache.
	 */
	private function cache_put( $feature ) {
		$this->features[] = $feature->get_id();
		sort( $this->features );
	}
}

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
	 * Features are fetched from the repository when needed.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $feature_ids = array();

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
		if ( is_array( $feature ) ) {
			if ( ! isset( $feature['id'] ) ) {
				return false;
			}

			$feature = new WP_Feature( $feature['id'], $feature );
		}

		if ( ! $feature instanceof WP_Feature ) {
			return false;
		}

		$feature_id = $feature->get_id();

		if ( $this->repository->find( $feature_id ) ) {
			return false;
		}

		$saved = $this->repository->save( $feature );

		if ( ! $saved ) {
			return false;
		}

		if ( ! in_array( $feature_id, $this->feature_ids, true ) ) {
			$this->feature_ids[] = $feature_id;
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
		$feature_id = $feature instanceof WP_Feature ? $feature->get_id() : $feature;

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
	 * @param string $feature_id The feature ID to find.
	 * @return WP_Feature|null The feature if found, null otherwise.
	 */
	public function find( $feature_id ) {
		return $this->repository->find( $feature_id );
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
	 * Clears the feature ID cache.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function clear_cache() {
		$this->feature_ids = array();
	}

	/**
	 * Gets all registered feature IDs.
	 *
	 * @since 0.1.0
	 * @return array List of registered feature IDs.
	 */
	public function get_registered_ids() {
		return $this->feature_ids;
	}

	/**
	 * Removes a feature ID from the cache.
	 *
	 * @since 0.1.0
	 * @param string $feature_id The feature ID to remove.
	 * @return void
	 */
	private function remove_from_cache( $feature_id ) {
		$index = array_search( $feature_id, $this->feature_ids, true );
		if ( false !== $index ) {
			unset( $this->feature_ids[ $index ] );
			$this->feature_ids = array_values( $this->feature_ids );
		}
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

		$this->remove_from_cache( $feature_id );

		return true;
	}
}

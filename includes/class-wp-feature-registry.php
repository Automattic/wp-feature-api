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
	 * The registered features.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $features = array();

	/**
	 * The feature repository.
	 *
	 * @since 0.1.0
	 * @var WP_Feature_Repository_Interface
	 */
	private $repository = null;

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		// Initialize the registry.
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
		// Convert array to WP_Feature if necessary.
		if ( is_array( $feature ) ) {
			if ( ! isset( $feature['id'] ) ) {
				return false;
			}

			$feature = new WP_Feature( $feature );
		}

		// Ensure the feature is a WP_Feature instance.
		if ( ! $feature instanceof WP_Feature ) {
			return false;
		}

		// Get the feature ID.
		$feature_id = $feature->get_id();

		// Check if the feature is already registered.
		if ( isset( $this->features[ $feature_id ] ) ) {
			return false;
		}

		// Register the feature.
		$this->features[ $feature_id ] = $feature;

		// Persist the feature to the repository if available.
		if ( $this->repository ) {
			$this->repository->save( $feature );
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
		// Get the feature ID.
		$feature_id = $feature instanceof WP_Feature ? $feature->get_id() : $feature;

		// Check if the feature is registered.
		if ( ! isset( $this->features[ $feature_id ] ) ) {
			return false;
		}

		// Get the feature object.
		$feature_obj = $this->features[ $feature_id ];

		// Unregister the feature.
		unset( $this->features[ $feature_id ] );

		// Remove the feature from the repository if available.
		if ( $this->repository ) {
			$this->repository->delete( $feature_id );
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
		// Check if the feature is registered in memory.
		if ( isset( $this->features[ $feature_id ] ) ) {
			return $this->features[ $feature_id ];
		}

		// Check if the feature is in the repository.
		if ( $this->repository ) {
			$feature = $this->repository->find( $feature_id );

			if ( $feature ) {
				// Cache the feature in memory.
				$this->features[ $feature_id ] = $feature;
				return $feature;
			}
		}

		return null;
	}

	/**
	 * Gets features based on a query.
	 *
	 * @since 0.1.0
	 * @param WP_Feature_Query|array|null $query The query to filter features by, or null to get all features.
	 * @return array The matching features.
	 */
	public function get( $query = null ) {
		// If no query is provided, return all features.
		if ( null === $query ) {
			return array_values( $this->features );
		}

		// Convert array to WP_Feature_Query if necessary.
		if ( is_array( $query ) ) {
			$query = new WP_Feature_Query( $query );
		}

		// Ensure the query is a WP_Feature_Query instance.
		if ( ! $query instanceof WP_Feature_Query ) {
			return array();
		}

		// If we have a repository, query it first.
		if ( $this->repository ) {
			$features = $this->repository->query( $query );

			// Cache the features in memory.
			foreach ( $features as $feature ) {
				$this->features[ $feature->get_id() ] = $feature;
			}
		} else {
			// Otherwise, filter the in-memory features.
			$features = array_filter(
				$this->features,
				function ( $feature ) use ( $query ) {
					return $query->matches( $feature );
				}
			);

			$features = array_values( $features );
		}

		return $features;
	}

	/**
	 * Sets the repository to use for the registry.
	 *
	 * @since 0.1.0
	 * @param WP_Feature_Repository_Interface $repository The repository to use.
	 * @return void
	 */
	public function use_repository( $repository ) {
		$this->repository = $repository;
	}
}

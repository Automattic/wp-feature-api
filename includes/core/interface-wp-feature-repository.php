<?php
/**
 * WP_Feature_Repository_Interface interface file.
 *
 * @package WordPress\Features_API
 */

/**
 * Interface WP_Feature_Repository_Interface
 *
 * Defines the contract for WordPress feature repositories.
 *
 * @since 0.1.0
 */
interface WP_Feature_Repository_Interface {

	/**
	 * Saves a feature to the repository.
	 *
	 * @since 0.1.0
	 * @param WP_Feature $feature The feature to save.
	 * @return bool True if the feature was saved successfully, false otherwise.
	 */
	public function save( $feature );

	/**
	 * Deletes a feature from the repository.
	 *
	 * @since 0.1.0
	 * @param string|WP_Feature $feature The feature ID or feature object to delete.
	 * @return bool True if the feature was deleted successfully, false otherwise.
	 */
	public function delete( $feature );

	/**
	 * Finds a feature by its ID.
	 *
	 * @since 0.1.0
	 * @param string $feature_id The feature ID to find.
	 * @return WP_Feature|null The feature if found, null otherwise.
	 */
	public function find( $feature_id );

	/**
	 * Queries features based on a query.
	 *
	 * @since 0.1.0
	 * @param WP_Feature_Query $query The query to filter features by.
	 * @return array The matching features.
	 */
	public function query( $query );

	/**
	 * Optional method to check if the repository supports native querying.
	 *
	 * @since 0.1.0
	 * @param WP_Feature_Query $query The query to check.
	 * @return bool Whether the repository can handle this query natively.
	 */
	public function supports_native_query( $query );

	/**
	 * Gets all features in the repository.
	 *
	 * @since 0.1.0
	 * @return array The features.
	 */
	public function get_all();

	/**
	 * Clears all features from the repository.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function clear();
}

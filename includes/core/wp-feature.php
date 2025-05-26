<?php
/**
 * WordPress Feature API
 *
 * @package WordPress\Features_API
 */

/**
 * Gets the feature registry.
 *
 * @since 0.1.0
 * @return WP_Feature_Registry The feature registry.
 */
function wp_feature_registry() {
	return WP_Feature_Registry::get_instance();
}

/**
 * Registers a feature.
 *
 * @since 0.1.0
 * @param WP_Feature|array $feature The feature to register.
 * @return bool True if the feature was registered, false otherwise.
 */
function wp_register_feature( $feature ) {
	return wp_feature_registry()->register( $feature );
}

/**
 * Unregisters a feature.
 *
 * @since 0.1.0
 * @param string|WP_Feature $feature The feature ID or feature object to unregister.
 * @return bool True if the feature was unregistered, false otherwise.
 */
function wp_unregister_feature( $feature ) {
	return wp_feature_registry()->unregister( $feature );
}

/**
 * Finds a feature by its ID.
 *
 * @since 0.1.0
 * @param string $feature_id The feature ID to find.
 * @return WP_Feature|null The feature if found, null otherwise.
 */
function wp_find_feature( $feature_id ) {
	return wp_feature_registry()->find( $feature_id );
}

/**
 * Gets features based on a query.
 *
 * @since 0.1.0
 * @param WP_Feature_Query|array|null $query The query to filter features by, or null to get all features.
 * @return array The matching features.
 */
function wp_get_features( $query = null ) {
	return wp_feature_registry()->get( $query );
}

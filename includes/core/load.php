<?php
/**
 * WordPress Feature API Loading
 *
 * @package WordPress\Features_API
 */

// Include the WP_Feature_Registry class.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/core/class-wp-feature-registry.php';
// Include the WP_Feature class.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/core/class-wp-feature.php';
// Include the WP_Feature_Query class.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/core/class-wp-feature-query.php';
// Include global functions.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/core/wp-feature.php';

if ( wp_feature_api_has_component( 'rest-api' ) ) {
	// Initialize the REST API endpoints.
	require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/rest-api/class-wp-rest-feature-controller.php';
}

if ( wp_feature_api_has_component( 'default-features' ) ) {
	// Include core features.
	require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/default-wp-features.php';
}

// Include initialization class.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/core/class-wp-feature-api-init.php';

<?php
/**
 * WordPress Feature API Loading
 *
 * @package WordPress\Features_API
 */

// Include the WP_Feature_Registry class.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/class-wp-feature-registry.php';
// Include the WP_Feature class.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/class-wp-feature.php';
// Include global functions.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/functions.php';
// Initialize the REST API endpoints.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/rest-api/class-wp-rest-feature-controller.php';

<?php
/**
 * Plugin Name: WordPress Feature API
 * Plugin URI: https://wordpress.org/plugins/wp-features-api/
 * Description: A system for exposing server and client-side functionality in WordPress for use in LLMs and agentic systems.
 * Version: 0.1.0
 * Author: WordPress Contributors
 * Author URI: https://wordpress.org/
 * Text Domain: wp-feature-api
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package WordPress\Feature_API
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'WP_FEATURE_API_VERSION', '0.1.0' );
define( 'WP_FEATURE_API_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_FEATURE_API_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initializes the WordPress Features API.
 *
 * @since 0.1.0
 * @return void
 */
function wp_feature_api_init() {
	// Include the WP_Feature_Registry class.
	require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/class-wp-feature-registry.php';

	// Include the WP_Feature class.
	require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/class-wp-feature.php';

	// Include global functions.
	require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/functions.php';

	// Initialize the REST API endpoints.
	require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/rest-api/class-wp-rest-feature-controller.php';

	// Register REST routes on rest_api_init.
	add_action( 'rest_api_init', 'wp_feature_api_register_rest_routes' );

	// enqueue admin scripts.
	add_action( 'admin_enqueue_scripts', 'wp_feature_api_enqueue_admin_scripts' );
}

function wp_feature_api_enqueue_admin_scripts() {
	if ( ! is_admin() ) {
		return;
	}
	wp_enqueue_script( 'wp_features_api_script', WP_FEATURE_API_PLUGIN_URL . 'build/index.js', array(), '1.0' );
}

/**
 * Registers the REST API routes for the Features API.
 *
 * @since 0.1.0
 * @return void
 */
function wp_feature_api_register_rest_routes() {
	$controller = new WP_REST_Features_Controller();
	$controller->register_routes();
}

// Initialize the plugin.
add_action( 'plugins_loaded', 'wp_feature_api_init' );

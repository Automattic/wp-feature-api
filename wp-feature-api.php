<?php
/**
 * Plugin Name: WordPress Feature API
 * Plugin URI: https://github.com/Automattic/wp-feature-api
 * Description: A system for exposing server and client-side functionality in WordPress for use in LLMs and agentic systems.
 * Version: 0.1.2
 * Author: Automattic AI
 * Author URI: https://automattic.ai/
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

$wp_feature_api_version = '0.1.2';
$wp_feature_api_plugin_dir = plugin_dir_path( __FILE__ );
$wp_feature_api_plugin_url = plugin_dir_url( __FILE__ );

/**
 * Define this constant as true in wp-config.php to load the demo plugin.
 * Example: define( 'WP_FEATURE_API_LOAD_DEMO', true );
 */
if ( ! defined( 'WP_FEATURE_API_LOAD_DEMO' ) ) {
	define( 'WP_FEATURE_API_LOAD_DEMO', true );
}

// Version registry.
global $wp_feature_api_versions;
if ( ! isset( $wp_feature_api_versions ) ) {
	$wp_feature_api_versions = array();
}

if ( ! function_exists( 'wp_feature_api_register_version' ) ) {
	/**
	 * Registers a version of the WP Feature API.
	 * Plugins should call this function to register their bundled version.
	 *
	 * @since 0.1.2
	 * @param string $version The version to register.
	 * @param string $file The main file path of this version.
	 * @return void
	 */
	function wp_feature_api_register_version( $version, $file ) {
		global $wp_feature_api_versions;
		$wp_feature_api_versions[ $version ] = $file;
	}
}

wp_feature_api_register_version( $wp_feature_api_version, __FILE__ );

if ( ! function_exists( 'wp_feature_api_get_version' ) ) {
	/**
	 * Returns the active version of the WP Feature API.
	 *
	 * @since 0.1.2
	 * @return string|null The active version or null if not yet loaded.
	 */
	function wp_feature_api_get_version() {
		return defined( 'WP_FEATURE_API_ACTIVE_VERSION' ) ? WP_FEATURE_API_ACTIVE_VERSION : null;
	}
}

// Version resolver function.
if ( ! function_exists( 'wp_feature_api_version_resolver' ) ) {
	/**
	 * Resolves and loads the highest version of WP Feature API.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	function wp_feature_api_version_resolver() {
		global $wp_feature_api_versions;

		if ( empty( $wp_feature_api_versions ) ) {
			return;
		}

		// Don't run twice.
		if ( defined( 'WP_FEATURE_API_ACTIVE_VERSION' ) ) {
			return;
		}

		// Find highest version.
		$versions = array_keys( $wp_feature_api_versions );
		$highest_version = $versions[0];
		foreach ( $versions as $version ) {
			if ( version_compare( $version, $highest_version, '>' ) ) {
				$highest_version = $version;
			}
		}

		define( 'WP_FEATURE_API_VERSION', $highest_version );
		define( 'WP_FEATURE_API_ACTIVE_VERSION', $highest_version );

		// Load the highest version.
		$file_to_load = $wp_feature_api_versions[ $highest_version ];
		$dir = dirname( $file_to_load );

		define( 'WP_FEATURE_API_PLUGIN_DIR', trailingslashit( $dir ) );
		define( 'WP_FEATURE_API_PLUGIN_URL', plugins_url( '/', $file_to_load ) );

		// Now load the API from the highest version.
		require_once $dir . '/includes/load.php';
	}
}

// Add a late hook to resolve and load the highest version.
// Make sure we only add this action once.
if ( ! has_action( 'plugins_loaded', 'wp_feature_api_version_resolver' ) ) {
	add_action( 'plugins_loaded', 'wp_feature_api_version_resolver', 999 );
}

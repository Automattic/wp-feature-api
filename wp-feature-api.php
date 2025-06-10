<?php
/**
 * Plugin Name: WordPress Feature API
 * Plugin URI: https://github.com/Automattic/wp-feature-api
 * Description: A system for exposing server and client-side functionality in WordPress for use in LLMs and agentic systems.
 * Version: 0.1.6
 * Author: Automattic AI
 * Author URI: https://automattic.ai/
 * Text Domain: wp-feature-api
 * License: GPL-2.0-or-later
 * License URI: https://spdx.org/licenses/GPL-2.0-or-later.html
 *
 * @package WordPress\Feature_API
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wp_feature_api_version = '0.1.6';
$wp_feature_api_plugin_dir = plugin_dir_path( __FILE__ );
$wp_feature_api_plugin_url = plugin_dir_url( __FILE__ );

/**
 * Define this constant as true in wp-config.php to load the demo plugin.
 * The plugin is bundled with the development version, or released separately on GitHub.
 * The Composer version does not include the demo plugin.
 * Example: define( 'WP_FEATURE_API_LOAD_DEMO', true );
 */
if ( ! defined( 'WP_FEATURE_API_LOAD_DEMO' ) ) {
	define( 'WP_FEATURE_API_LOAD_DEMO', false );
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
		// Generate a unique key using version + path hash to prevent overwriting
		// installations of the same version in different directories. This is so we can
		// provide a preference for the plugin/developer version over a vendor version.
		$unique_key = $version . '|' . md5($file);
		$wp_feature_api_versions[$unique_key] = array(
			'version' => $version,
			'file' => $file,
		);
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

		$plugin_dir_version = null;
		$plugin_dir_file = null;
		$vendor_highest_version = null;
		$vendor_highest_file = null;

		// Always prioritize direct plugin installation over vendor installation
		// Separate versions by location and find highest for each
		foreach ( $wp_feature_api_versions as $unique_key => $data ) {
			$version = $data['version'];
			$file_path = $data['file'];

			if ( false === strpos( $file_path, '/vendor/' ) ) {
				// Direct plugin installation
				if ( null === $plugin_dir_version || version_compare( $version, $plugin_dir_version, '>' ) ) {
					$plugin_dir_version = $version;
					$plugin_dir_file = $file_path;
				}
			} else {
				// Vendor installation
				if ( null === $vendor_highest_version || version_compare( $version, $vendor_highest_version, '>' ) ) {
					$vendor_highest_version = $version;
					$vendor_highest_file = $file_path;
				}
			}
		}

		// Choose the overall highest version, with a preference for plugin directory at equal versions
		if ( null !== $plugin_dir_version && null !== $vendor_highest_version ) {
			$version_compare = version_compare( $plugin_dir_version, $vendor_highest_version );

			if ( $version_compare > 0 ) {
				$highest_version = $plugin_dir_version;
				$file_to_load = $plugin_dir_file;
			} elseif ( $version_compare < 0 ) {
				$highest_version = $vendor_highest_version;
				$file_to_load = $vendor_highest_file;
			} else {
				// Versions are equal, prioritize the plugin directory version
				$highest_version = $plugin_dir_version;
				$file_to_load = $plugin_dir_file;
			}
		} elseif ( null !== $plugin_dir_version ) {
			$highest_version = $plugin_dir_version;
			$file_to_load = $plugin_dir_file;
		} else {
			$highest_version = $vendor_highest_version;
			$file_to_load = $vendor_highest_file;
		}

		define( 'WP_FEATURE_API_VERSION', $highest_version );
		define( 'WP_FEATURE_API_ACTIVE_VERSION', $highest_version );

		// Load the highest version.
		$dir = dirname( $file_to_load );

		define( 'WP_FEATURE_API_PLUGIN_DIR', trailingslashit( $dir ) );

		// When loaded via Composer, the file might be in the vendor directory, not plugins
		if ( false !== strpos( $file_to_load, '/vendor/' ) ) {
			// We're in a Composer installation, get the URL directly from WP_CONTENT_URL
			$vendor_rel_path = str_replace( WP_CONTENT_DIR, '', dirname( $file_to_load ) );
			define( 'WP_FEATURE_API_PLUGIN_URL', trailingslashit( content_url( $vendor_rel_path ) ) );
		} else {
			// Standard plugin installation
			define( 'WP_FEATURE_API_PLUGIN_URL', plugins_url( '/', $file_to_load ) );
		}

		// Now load the API from the highest version.
		require_once $dir . '/includes/load.php';
	}
}

// Add a late hook to resolve and load the highest version.
// Make sure we only add this action once.
if ( ! has_action( 'plugins_loaded', 'wp_feature_api_version_resolver' ) ) {
	add_action( 'plugins_loaded', 'wp_feature_api_version_resolver', 999 );
}

<?php
/**
 * Plugin Name: WordPress Feature API
 * Plugin URI: https://github.com/Automattic/wp-feature-api
 * Description: A system for exposing server and client-side functionality in WordPress for use in LLMs and agentic systems.
 * Version: 0.1.1
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

define( 'WP_FEATURE_API_VERSION', '0.1.1' );
define( 'WP_FEATURE_API_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_FEATURE_API_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

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

// Safe wrapper for registering versions.
if ( ! function_exists( 'wp_feature_api_register_version' ) ) {
	/**
	 * Registers a version of the WP Feature API.
	 * Plugins should call this function to register their bundled version.
	 *
	 * @since 0.1.1
	 * @param string $version The version to register.
	 * @param string $file The main file path of this version.
	 * @return void
	 */
	function wp_feature_api_register_version( $version, $file ) {
		global $wp_feature_api_versions;
		$wp_feature_api_versions[ $version ] = $file;
	}
}

// Register our own version.
wp_feature_api_register_version( WP_FEATURE_API_VERSION, __FILE__ );

// Version getter function.
if ( ! function_exists( 'wp_feature_api_get_version' ) ) {
	/**
	 * Returns the active version of the WP Feature API.
	 *
	 * @since 0.1.1
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
	 * @since 0.1.1
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

		// Store the active version for reference.
		define( 'WP_FEATURE_API_ACTIVE_VERSION', $highest_version );

		// Load the highest version.
		$file_to_load = $wp_feature_api_versions[ $highest_version ];
		$dir = dirname( $file_to_load );
		require_once $dir . '/includes/load.php';

		wp_feature_api_initialize();

		do_action( 'wp_feature_api_init' );
	}
}

// Add a late hook to resolve and load the highest version.
// Make sure we only add this action once.
if ( ! has_action( 'plugins_loaded', 'wp_feature_api_version_resolver' ) ) {
	add_action( 'plugins_loaded', 'wp_feature_api_version_resolver', 999 );
}

// Initialize function.
if ( ! function_exists( 'wp_feature_api_initialize' ) ) {
	/**
	 * Initializes the WordPress Feature API core components.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	function wp_feature_api_initialize() {
		// Register REST routes on init. Late execution to ensure features are registered by plugins first.
		add_action( 'init', 'wp_feature_api_register_rest_routes', 9999 );

		// enqueue admin scripts.
		add_action( 'admin_enqueue_scripts', 'wp_feature_api_enqueue_admin_scripts' );

		// Load demo plugin if enabled.
		if ( WP_FEATURE_API_LOAD_DEMO ) {
			wp_feature_api_load_agent_demo();
		}
	}
}

// Admin script function.
if ( ! function_exists( 'wp_feature_api_enqueue_admin_scripts' ) ) {
	/**
	 * Enqueues admin scripts.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	function wp_feature_api_enqueue_admin_scripts() {
		if ( ! is_admin() ) {
			return;
		}
		$assets = require WP_FEATURE_API_PLUGIN_DIR . 'build/index.asset.php';
		wp_enqueue_script( 'wp-features', WP_FEATURE_API_PLUGIN_URL . 'build/index.js', $assets['dependencies'], $assets['version'], true );
	}
}

// REST routes function.
if ( ! function_exists( 'wp_feature_api_register_rest_routes' ) ) {
	/**
	 * Registers the REST API routes for the Feature API.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	function wp_feature_api_register_rest_routes() {
		$controller = new WP_REST_Feature_Controller();
		$controller->register_routes();
	}
}

// Demo plugin loader function.
if ( ! function_exists( 'wp_feature_api_load_agent_demo' ) ) {
	/**
	 * Loads the WP Feature API Demo plugin.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	function wp_feature_api_load_agent_demo() {
		$demo_plugin_file = WP_FEATURE_API_PLUGIN_DIR . 'demo/wp-feature-api-agent/wp-feature-api-agent.php';

		if ( file_exists( $demo_plugin_file ) ) {
			require_once $demo_plugin_file;

			// Notify admin that demo plugin is loaded if in admin area.
			if ( is_admin() ) {
				add_action( 'admin_notices', 'wp_feature_api_demo_loaded_notice' );
			}
		}
	}
}

// Demo plugin notice function.
if ( ! function_exists( 'wp_feature_api_demo_loaded_notice' ) ) {
	/**
	 * Displays an admin notice when the demo plugin is loaded.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	function wp_feature_api_demo_loaded_notice() {
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %s: WP_FEATURE_API_LOAD_DEMO constant */
					esc_html__( 'WordPress Feature API Demo plugin is loaded. To disable it, set %s to false in your wp-config.php file.', 'wp-feature-api' ),
					'<code>WP_FEATURE_API_LOAD_DEMO</code>'
				);
				?>
			</p>
		</div>
		<?php
	}
}

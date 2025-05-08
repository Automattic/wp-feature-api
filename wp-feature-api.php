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

/**
 * Initializes the WordPress Feature API.
 *
 * @since 0.1.0
 * @return void
 */
function wp_feature_api_init() {
	require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/load.php';

	// Register REST routes on init. Late execution to ensure features are registered by plugins first.
	add_action( 'init', 'wp_feature_api_register_rest_routes', 9999 );

	// enqueue admin scripts.
	add_action( 'admin_enqueue_scripts', 'wp_feature_api_enqueue_admin_scripts' );

	// Load demo plugin if enabled.
	if ( WP_FEATURE_API_LOAD_DEMO ) {
		wp_feature_api_load_agent_demo();
	}
}

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

// Initialize the plugin.
add_action( 'plugins_loaded', 'wp_feature_api_init' );

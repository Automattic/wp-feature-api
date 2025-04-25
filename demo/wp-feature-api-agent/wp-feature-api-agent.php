<?php
/**
 * Plugin Name: WP Feature API - AI Agent Proxy
 * Plugin URI: https://github.com/Automattic/wp-feature-api
 * Description: Provides a REST API proxy for interacting with external AI services.
 * Version: 0.1.0
 * Author: WordPress Contributors
 * Author URI: https://wordpress.org/
 * Text Domain: wp-feature-api-agent
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package WordPress\Feature_API_Agent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'WP_AI_API_PROXY_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_AI_API_PROXY_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_AI_API_PROXY_VERSION', '0.1.0' );

// Include the main proxy class.
require_once WP_AI_API_PROXY_PATH . 'includes/class-wp-ai-api-proxy.php';

// Include the options class.
require_once WP_AI_API_PROXY_PATH . 'includes/class-wp-ai-api-options.php';

// Include the feature registration class.
require_once WP_AI_API_PROXY_PATH . 'includes/class-wp-feature-register.php';

/**
 * Initializes the plugin.
 *
 * Loads the plugin's main class and registers hooks.
 */
function wp_ai_api_proxy_init() {
	// Check if the required class exists before instantiating.
	if ( class_exists( 'A8C\\WpFeatureApiAgent\\WP_AI_API_Proxy' ) ) {
		$proxy_instance = new A8C\WpFeatureApiAgent\WP_AI_API_Proxy();
		$proxy_instance->register_hooks();
	} else {
		// Optional: Add admin notice or log an error if the class is missing.
		add_action(
			'admin_notices',
			function() {
				echo '<div class="notice notice-error"><p>';
				esc_html_e( 'WP Feature API - AI Agent Proxy requires its main class file but it is missing.', 'wp-feature-api-agent' );
				echo '</p></div>';
			}
		);
	}

	// Initialize the options page.
	if ( class_exists( 'A8C\\WpFeatureApiAgent\\WP_AI_API_Options' ) ) {
		$options_instance = new A8C\WpFeatureApiAgent\WP_AI_API_Options();
		$options_instance->init();
	}

	// Initialize the feature registration.
	if ( class_exists( 'A8C\\WpFeatureApiAgent\\WP_Feature_Register' ) ) {
		$feature_register_instance = new A8C\WpFeatureApiAgent\WP_Feature_Register();
		$feature_register_instance->init();
	}
}

// Use a priority lower than default (10) to ensure REST API is potentially ready,
// but higher than very late hooks. 20 seems reasonable.
add_action( 'plugins_loaded', 'wp_ai_api_proxy_init', 20 );

// Removed admin menu page functions as the agent will load globally.

/**
 * Enqueues scripts and styles for the admin area.
 *
 */
function wp_feature_api_agent_enqueue_assets() {
 // No hook suffix check needed - load on all admin pages.

 $script_asset_path = WP_AI_API_PROXY_PATH . 'build/index.asset.php';
 if ( ! file_exists( $script_asset_path ) ) {
		// Optional: Add admin notice or log error if build files are missing.
		return;
	}
	$script_asset = require $script_asset_path;

	// Enqueue the main script.
	wp_enqueue_script(
		'wp-feature-api-agent-script',
		WP_AI_API_PROXY_URL . 'build/index.js',
		$script_asset['dependencies'],
		$script_asset['version'],
		true // Load in footer.
	);

	// Enqueue the main style.
	// Note: wp-scripts names the CSS file based on the importing JS/TS entry point (index.tsx -> style-index.css)
	// Only enqueue wp-components CSS if it's not already loaded by core.
	if ( ! wp_style_is( 'wp-components', 'enqueued' ) ) {
		wp_enqueue_style(
			'wp-components', // Use the correct handle 'wp-components'
			includes_url( 'css/dist/components/style.min.css' ),
			array(),
			$script_asset['version'] // Use script version for consistency
		);
	}

	wp_enqueue_style(
		'wp-feature-api-agent-style',
		WP_AI_API_PROXY_URL . 'build/style-index.css',
		array( 'wp-components' ), // Add 'wp-components' as a dependency
		$script_asset['version']
	);

	// Pass data to the script.
	wp_localize_script(
		'wp-feature-api-agent-script',
		'wpFeatureApiAgentData', // Global JS object name.
		array(
			'defaultModel' => A8C\WpFeatureApiAgent\WP_AI_API_Options::get_default_model(),
			// Add other data like nonce or API base if needed later.
		)
	);
}
add_action( 'admin_enqueue_scripts', 'wp_feature_api_agent_enqueue_assets' );

/**
	* Adds the root container div to the admin footer.
	*/
function wp_feature_api_agent_add_root_container() {
	?>
	<div id="wp-feature-api-agent-chat"></div>
	<?php
}
add_action( 'admin_footer', 'wp_feature_api_agent_add_root_container' );

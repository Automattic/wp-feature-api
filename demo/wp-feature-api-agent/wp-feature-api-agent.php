<?php
/**
 * Plugin Name: WP Feature API - AI Agent Proxy
 * Plugin URI: https://github.com/Automattic/wp-feature-api
 * Description: Provides a REST API proxy for interacting with external AI services.
 * Version: 0.1.0
 * Author: Automattic AI
 * Author URI: https://automattic.ai/
 * Text Domain: wp-feature-api-agent
 * License: GPL-2.0-or-later
 * License URI: https://spdx.org/licenses/GPL-2.0-or-later.html
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

$proxy_instance = new A8C\WpFeatureApiAgent\WP_AI_API_Proxy();
$proxy_instance->register_hooks();

$options_instance = new A8C\WpFeatureApiAgent\WP_AI_API_Options();
$options_instance->init();

// Register additional demo features.
$feature_register_instance = new A8C\WpFeatureApiAgent\WP_Feature_Register();
$feature_register_instance->init();

/**
 * Enqueues scripts and styles for the admin area.
 *
 */
function wp_feature_api_agent_enqueue_assets() {
 $script_asset_path = WP_AI_API_PROXY_PATH . 'build/index.asset.php';
 if ( ! file_exists( $script_asset_path ) ) {
		return;
	}
	$script_asset = require $script_asset_path;

	// Enqueue the main script.
	wp_enqueue_script(
		'wp-feature-api-agent-script',
		WP_AI_API_PROXY_URL . 'build/index.js',
		array_merge( $script_asset['dependencies'], array( 'wp-features' ) ),
		$script_asset['version'],
		true // Load in footer.
	);

	// Note: wp-scripts names the CSS file based on the importing JS/TS entry point (index.tsx -> style-index.css)
	// Only enqueue wp-components CSS if it's not already loaded by core.
	if ( ! wp_style_is( 'wp-components', 'enqueued' ) ) {
		wp_enqueue_style(
			'wp-components',
			includes_url( 'css/dist/components/style.min.css' ),
			array(),
			$script_asset['version']
		);
	}

	wp_enqueue_style(
		'wp-feature-api-agent-style',
		WP_AI_API_PROXY_URL . 'build/style-index.css',
		array( 'wp-components' ),
		$script_asset['version']
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

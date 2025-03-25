<?php
/**
 * Plugin Name: WordPress Feature API Demo
 * Plugin URI: https://github.com/Automattic/wp-feature-api
 * Description: Demo plugin showcasing the WordPress Features API.
 * Version: 0.1.0
 * Author: WordPress Contributors
 * Author URI: https://wordpress.org/
 * Text Domain: wp-feature-api-demo
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.0
 * Requires PHP: 7.2
 *
 * @package WordPress\Feature_API_Demo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize the WordPress Feature API Demo plugin.
 *
 * @since 0.1.0
 * @return void
 */
function wp_feature_api_demo_init() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/demo-features.php';

	if ( ! function_exists( 'wp_register_feature' ) ) {
		add_action( 'admin_notices', 'wp_feature_api_demo_missing_notice' );
		return;
	}
}

add_action( 'plugins_loaded', 'wp_feature_api_demo_init', 20 );


/**
 * Display an admin notice if the WordPress Feature API plugin is not active.
 *
 * @since 0.1.0
 * @return void
 */
function wp_feature_api_demo_missing_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: %s: URL of the WordPress Feature API plugin */
				esc_html__( 'The WordPress Feature API Demo plugin requires the WordPress Feature API plugin to be installed and activated. Please install it from %s.', 'wp-feature-api-demo' ),
				'<a href="https://github.com/WordPress/wp-feature-api">GitHub</a>'
			);
			?>
		</p>
	</div>
	<?php
}

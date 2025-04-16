<?php
/**
 * Main class.
 *
 * @package WpFeatureApiDemo
 */

namespace WpFeatureApiDemo;

use WpFeatureApiDemo\RegisterFeatures;
use WpFeatureApiDemo\BootstrapAssets;
use WpFeatureApiDemo\ChatController;
use WpFeatureApiDemo\Options;

/**
 * Main class.
 */
class Main {

	/**
	 * Initialize the Main class.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function init() {
		if ( ! function_exists( 'wp_register_feature' ) ) {
			add_action( 'admin_notices', array( $this, 'missing_notice' ) );
		}

		load_plugin_textdomain( 'wp-feature-api-demo', false, dirname( plugin_basename( WP_FEATURE_API_DEMO_PATH ) ) . '/languages' );

		( new Register_Features() )->init();
		( new Options() )->init();
		$api_key = Options::get_api_key();

		if ( $api_key ) {
			( new Bootstrap_Assets() )->init();
			$chat_controller = new Chat_Controller();
			add_action( 'rest_api_init', array( $chat_controller, 'register_routes' ) );
		}
	}

	/**
	 * Display an admin notice if the WordPress Feature API plugin is not active.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function missing_notice() {
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
}

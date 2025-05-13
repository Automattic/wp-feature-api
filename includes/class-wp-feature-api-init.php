<?php
/**
 * WordPress Feature API Initialization
 *
 * @package WordPress\Feature_API
 */

/**
 * Handles the initialization of WordPress Feature API components.
 */
class WP_Feature_API_Init {

	/**
	 * Initializes the WordPress Feature API core components.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function initialize() {
		// Register REST routes on init. Late execution to ensure features are registered by plugins first.
		add_action( 'init', array( __CLASS__, 'register_rest_routes' ), 9999 );

		// enqueue admin scripts.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );

		// Load demo plugin if enabled.
		if ( defined( 'WP_FEATURE_API_LOAD_DEMO' ) && WP_FEATURE_API_LOAD_DEMO ) {
			self::load_agent_demo();
		}

		do_action( 'wp_feature_api_init' );
	}

	/**
	 * Enqueues admin scripts.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function enqueue_admin_scripts() {
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
	public static function register_rest_routes() {
		$controller = new WP_REST_Feature_Controller();
		$controller->register_routes();
	}

	/**
	 * Loads the WP Feature API Demo plugin.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function load_agent_demo() {
		$demo_plugin_file = WP_FEATURE_API_PLUGIN_DIR . 'demo/wp-feature-api-agent/wp-feature-api-agent.php';

		if ( file_exists( $demo_plugin_file ) ) {
			require_once $demo_plugin_file;

			// Notify admin that demo plugin is loaded if in admin area.
			if ( is_admin() ) {
				add_action( 'admin_notices', array( __CLASS__, 'demo_loaded_notice' ) );
			}
		}
	}

	/**
	 * Displays an admin notice when the demo plugin is loaded.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function demo_loaded_notice() {
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

WP_Feature_API_Init::initialize();

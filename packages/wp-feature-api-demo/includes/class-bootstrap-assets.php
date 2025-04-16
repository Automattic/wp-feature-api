<?php
/**
 * Bootstrap assets class.
 *
 * @package WpFeatureApiDemo
 */

namespace WpFeatureApiDemo;

/**
 * Bootstrap assets class.
 */
class Bootstrap_Assets {
	/**
	 * Root container ID.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	public static $root_container_id = 'wp-feature-api-demo-root';

	/**
	 * Initialize the Bootstrap_Assets class.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_footer', array( $this, 'create_root_container' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function enqueue_scripts() {
		$asset_file = include WP_FEATURE_API_DEMO_PATH . 'build/index.asset.php';

		wp_enqueue_script(
			'wp-feature-api-demo',
			WP_FEATURE_API_DEMO_URL . 'build/index.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		// Only enqueue wp-components CSS if it's not already loaded by core (e.g., on editor screens), otherwise things will not render correctly.
		if ( ! wp_style_is( 'wp-components', 'enqueued' ) ) {
			wp_enqueue_style(
				'wp-components-css',
				includes_url( 'css/dist/components/style.min.css' ),
				array(),
				$asset_file['version']
			);
		}

		wp_enqueue_style(
			'wp-feature-api-demo',
			WP_FEATURE_API_DEMO_URL . 'build/style-index.css',
			array(),
			$asset_file['version']
		);
	}

	/**
	 * Create the root container.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function create_root_container() {
		?>
		<div id="<?php echo esc_attr( self::$root_container_id ); ?>"></div>
		<?php
	}
}

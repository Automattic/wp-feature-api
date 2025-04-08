<?php

namespace A8C\WpFeatureApiDemo;

class BootstrapAssets {
	public static $root_container_id = 'wp-feature-api-demo-root';

	public function init() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_footer', [ $this, 'create_root_container' ] );
	}

    public function enqueue_scripts() {
        $asset_file = include(WP_FEATURE_API_DEMO_PATH . 'build/index.asset.php');

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
				includes_url('css/dist/components/style.min.css'),
				array(),
				false
			);
		}

        wp_enqueue_style(
            'wp-feature-api-demo',
            WP_FEATURE_API_DEMO_URL . 'build/style-index.css',
            [],
            $asset_file['version']
        );
    }

	public function create_root_container() {
		?>
		<div id="<?php echo esc_attr( self::$root_container_id ); ?>"></div>
		<?php
	}
}

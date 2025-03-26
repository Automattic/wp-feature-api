<?php

namespace A8C\WpFeatureApiDemo;

use A8C\WpFeatureApiDemo\RegisterFeatures;

class Main {

	public function __construct() {
		add_action( 'init', [ $this, 'init' ], 20 );
	}

	public function init() {
		if ( ! function_exists( 'wp_register_feature' ) ) {
			add_action( 'admin_notices', [ $this, 'missing_notice' ] );
		}

		$features = new RegisterFeatures();
		add_action( 'init', [ $features, 'register_features' ] );
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

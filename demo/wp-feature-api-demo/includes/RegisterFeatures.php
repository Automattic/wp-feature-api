<?php

namespace A8C\WpFeatureApiDemo;

class RegisterFeatures {

	private $features = array(
		Features\Resources\SiteInfo::class,
		Features\Resources\WooCommerceInfo::class,
		Features\Tools\SEO::class,
	);

	public function init() {
		add_action( 'init', [ $this, 'register_features' ] );
	}

	/**
	 * Register demo features.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_features() {
		foreach ( $this->features as $feature ) {
			$feature_class = new $feature();
			$feature_class();
		}
	}
}

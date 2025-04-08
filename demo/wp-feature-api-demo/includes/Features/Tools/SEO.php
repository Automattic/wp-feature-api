<?php

namespace A8C\WpFeatureApiDemo\Features\Tools;

use WP_Feature;

class SEO {
	public function __invoke() {
		wp_register_feature(
			array(
				'id'          => 'demo/seo-json-ld',
				'name'        => __( 'SEO JSON-LD', 'wp-feature-api-demo' ),
				'description' => __( 'Generates JSON-LD from a page or post for use in SEO tools.', 'wp-feature-api-demo' ),
				'type'        => WP_Feature::TYPE_TOOL,
				'categories'  => array( 'demo', 'seo', 'json-ld' ),
				'callback'    => function( $input ) {
					return array(
						'json_ld' => [],
					);
				},
				'is_eligible'    => function() {
					if (! function_exists('is_plugin_active')) {
						include_once(ABSPATH . 'wp-admin/includes/plugin.php');
					}

					return ! empty(array_filter([
						'yoast_seo' => is_plugin_active('wordpress-seo/wp-seo.php'),
						'rank_math' => is_plugin_active('seo-by-rank-math/rank-math.php')
					]));
				},
			)
		);
	}
}

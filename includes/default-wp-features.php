<?php
/**
 * Core WordPress Features
 *
 * @package WordPress\Features_API
 */

/**
 * Register core WordPress features.
 *
 * @since 0.1.0
 * @return void
 */
function wp_feature_api_register_core_features() {
	add_filter(
		'wp_feature_default_categories',
		function ( $categories ) {
			return array_merge(
				$categories,
				array(
					'core' => array(
						'name'        => 'Core',
						'description' => 'Core features of WordPress available everywhere in the admin.',
					),
					'post' => array(
						'name'        => 'Posts',
						'description' => 'Features related to posts.',
					),
					'user' => array(
						'name'        => 'Users',
						'description' => 'Features related to users.',
					),
					'rest' => array(
						'name'        => 'REST API',
						'description' => 'Features related to the REST API.',
					),
				)
			);
		}
	);
}

// Register core features on init.
add_action( 'init', 'wp_feature_api_register_core_features' );

<?php

namespace A8C\WpFeatureApiDemo\Features\Resources;

use WP_Feature;

class SiteInfo {
	public function __invoke() {
		wp_register_feature(
			array(
				'id'          => 'demo/site-info',
				'name'        => __( 'Site Information', 'wp-feature-api-demo' ),
				'description' => __( 'Get basic information about the WordPress site. This includes the name, description, URL, version, language, timezone, date format, time format, active plugins, and active theme.', 'wp-feature-api-demo' ),
				'type'        => WP_Feature::TYPE_RESOURCE,
				'categories'  => array( 'demo', 'site', 'information' ),
				'callback'    => function( $input ) {
					return array(
						'name'        => get_bloginfo( 'name' ),
						'description' => get_bloginfo( 'description' ),
						'url'         => home_url(),
						'version'     => get_bloginfo( 'version' ),
						'language'    => get_bloginfo( 'language' ),
						'timezone'    => wp_timezone_string(),
						'date_format' => get_option( 'date_format' ),
						'time_format' => get_option( 'time_format' ),
						'active_plugins' => get_option( 'active_plugins' ),
						'active_theme' => get_option( 'stylesheet' ),
						'is_multisite'          => is_multisite(),
						'max_upload_size'       => size_format( wp_max_upload_size() ),
						'memory_limit'          => WP_MEMORY_LIMIT,
						'php_version'           => phpversion(),
						'php_post_max_size'     => ini_get( 'post_max_size' ),
						'php_time_limit'        => ini_get( 'max_execution_time' ),
						'php_max_input_vars'    => ini_get( 'max_input_vars' ),
						'curl_version'          => function_exists( 'curl_version' ) ? curl_version()['version'] : 'N/A',
					);
				},
			)
		);
	}
}

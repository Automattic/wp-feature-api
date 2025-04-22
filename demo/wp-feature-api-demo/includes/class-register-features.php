<?php
/**
 * Register demo features.
 *
 * @package WpFeatureApiDemo
 */

namespace WpFeatureApiDemo;

use WP_Feature;

/**
 * Register demo features.
 */
class Register_Features {
	/**
	 * Initialize the RegisterFeatures class.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_features' ) );
	}

	/**
	 * Register demo features.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_features() {
		/** Global Features */
		wp_register_feature(
			array(
				'id'          => 'demo/site-info',
				'name'        => __( 'Site Information', 'wp-feature-api-demo' ),
				'description' => __( 'Get basic information about the WordPress site. This includes the name, description, URL, version, language, timezone, date format, time format, active plugins, and active theme.', 'wp-feature-api-demo' ),
				'type'        => WP_Feature::TYPE_RESOURCE,
				'categories'  => array( 'demo', 'site', 'information' ),
				'callback'    => array( $this, 'site_info_callback' ),
			)
		);
		wp_register_feature(
			array(
				'id'          => 'demo/woocommerce-info',
				'name'        => __( 'WooCommerce Information', 'wp-feature-api-demo' ),
				'description' => __( 'Get basic information about the configuration of WooCommerce. This includes the currency, country, language, timezone, date format, and time format.', 'wp-feature-api-demo' ),
				'type'        => WP_Feature::TYPE_RESOURCE,
				'categories'  => array( 'demo', 'woocommerce', 'information' ),
				'callback'    => function () {
					return array(
						'version' => WC()->version,
						'currency' => get_woocommerce_currency(),
					);
				},
				'is_eligible' => function () {
					return function_exists( 'WC' );
				},
			)
		);

		/**
		 * SEO Page Features
		 */
		wp_register_feature(
			array(
				'id'          => 'demo/post-info',
				'name'        => __( 'Post Information', 'wp-feature-api-demo' ),
				'description' => __( 'Get information about the current post.', 'wp-feature-api-demo' ),
			)
		);
	}

	/**
	 * Callback for the site info feature.
	 *
	 * @since 0.1.0
	 * @return array The output for the feature.
	 */
	public function site_info_callback() {
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
		);
	}
}

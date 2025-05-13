<?php
/**
 * Class for registering features for the AI Agent.
 *
 * @package WordPress\Feature_API_Agent
 */

namespace A8C\WpFeatureApiAgent;

use WP_Feature;

/**
 * Registers features for the AI Agent.
 */
class WP_Feature_Register {

	/**
	 * Registers WordPress hooks.
	 */
	public function init() {
		// Register features immediately - we're already in the wp_feature_api_init action
		$this->register_features();
	}

	/**
	 * Register features for the AI Agent.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_features() {
		/** Global Features */
		wp_register_feature(
			array(
				'id'          => 'demo/site-info',
				'name'        => __( 'Site Information', 'wp-feature-api-agent' ),
				'description' => __( 'Get basic information about the WordPress site. This includes the name, description, URL, version, language, timezone, date format, time format, active plugins, and active theme.', 'wp-feature-api-agent' ),
				'type'        => WP_Feature::TYPE_RESOURCE,
				'categories'  => array( 'demo', 'site', 'information' ),
				'callback'    => array( $this, 'site_info_callback' ),
			)
		);
	}

	/**
	 * Callback for the site info feature.
	 *
	 * @param array $input Input parameters for the feature.
	 * @return array Site information.
	 */
	public function site_info_callback( $input ) {
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

<?php
/**
 * Register site features
 *
 * @package wp-feature-api
 */

declare(strict_types=1);


/**
 * Site features
 *
 * @package wp-feature-api
 */
class Site_Features {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_feature_api_init', array( $this, 'register_site_features' ) );
	}

	/**
	 * Register routes.
	 */
	public function register_site_features() {
		wp_register_feature(
			array(
				'id'                  => 'get-site-info',
				'name'                => 'get_site_info',
				'description'         => __( 'Get basic information about the WordPress site. This includes the name, description, URL, version, language, timezone, date format, time format, active plugins, and active theme.', 'wp-feature-api-demo' ),
				'categories'          => array( 'core', 'site', 'rest' ),
				'type'                => WP_Feature::TYPE_RESOURCE,
				'callback'            => array( $this, 'get_site_info_callback' ),
				'permission_callback' => array( $this, 'get_site_info_access_callback' ),
			)
		);
	}

	/**
	 * Get site info callback.
	 */
	public function get_site_info_callback() {
		return array(
			'name'           => get_bloginfo( 'name' ),
			'description'    => get_bloginfo( 'description' ),
			'url'            => home_url(),
			'version'        => get_bloginfo( 'version' ),
			'language'       => get_bloginfo( 'language' ),
			'timezone'       => wp_timezone_string(),
			'date_format'    => get_option( 'date_format' ),
			'time_format'    => get_option( 'time_format' ),
			'active_plugins' => get_option( 'active_plugins' ),
			'active_theme'   => get_option( 'stylesheet' ),
		);
	}

	/**
	 * Get site info access callback.
	 */
	public function get_site_info_access_callback() {
		return current_user_can( 'edit_posts' );
	}
}

new Site_Features();

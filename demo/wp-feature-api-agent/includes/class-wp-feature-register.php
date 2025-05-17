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
                               'id'                => 'demo/site-info',
                               'name'              => __( 'Site Information', 'wp-feature-api-agent' ),
                               'description'       => __( 'Get basic information about the WordPress site. This includes the name, description, URL, version, language, timezone, date format, time format, active plugins, and active theme.', 'wp-feature-api-agent' ),
                               'type'              => WP_Feature::TYPE_RESOURCE,
                               'categories'        => array( 'demo', 'site', 'information' ),
                               'version'           => '2.0.0',
                               'since_version'     => '1.0.0',
                               'deprecated_version'=> '3.0.0',
                               'alternatives'      => array(),
                               'deprecated_message'=> __( 'Use the latest version of this feature for more details.', 'wp-feature-api-agent' ),
                               'versions'          => array(
                                       '1.0.0' => array(
                                               'callback' => array( $this, 'site_info_callback' ),
                                       ),
                                       '2.0.0' => array(
                                               'callback' => array( $this, 'site_info_v2_callback' ),
                                       ),
                               ),
                               'callback'          => array( $this, 'site_info_v2_callback' ),
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

       /**
        * Callback for version 2 of the site info feature.
        *
        * @param array $input Input parameters for the feature.
        * @return array Extended site information.
        */
       public function site_info_v2_callback( $input ) {
               $info = $this->site_info_callback( $input );
               $info['charset'] = get_option( 'blog_charset' );
               $info['posts_count'] = (int) wp_count_posts()->publish;
               return $info;
       }
}

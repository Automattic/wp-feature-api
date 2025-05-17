<?php
/**
 * WordPress Feature API Loading
 *
 * @package WordPress\Features_API
 */

// Include the WP_Feature_Registry class.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/class-wp-feature-registry.php';
// Include the WP_Feature class.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/class-wp-feature.php';
// Include the WP_Feature_Query class.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/class-wp-feature-query.php';
// Include global functions.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/wp-feature.php';
// Initialize the REST API endpoints.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/rest-api/class-wp-rest-feature-controller.php';
// Initialize the REST API endpoints.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/rest-api/class-wp-rest-feature-controller.php';
// Include core features.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/default-wp-features.php';
// Include initialization class.
require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/class-wp-feature-api-init.php';

// Load additional features defined in features.json.
$features_file = WP_FEATURE_API_PLUGIN_DIR . 'features.json';
if ( file_exists( $features_file ) ) {
    $features_data = file_get_contents( $features_file );
    if ( false !== $features_data ) {
        $features = json_decode( $features_data, true );
        if ( is_array( $features ) ) {
            foreach ( $features as $feature ) {
                if ( isset( $feature['server_callback'] ) ) {
                    $callback_file = WP_FEATURE_API_PLUGIN_DIR . ltrim( $feature['server_callback'], '/' );
                    if ( file_exists( $callback_file ) ) {
                        $feature['callback'] = require $callback_file;
                    }
                    unset( $feature['server_callback'] );
                }

                if ( isset( $feature['client_callback'] ) ) {
                    if ( ! isset( $feature['meta'] ) || ! is_array( $feature['meta'] ) ) {
                        $feature['meta'] = array();
                    }
                    $feature['meta']['client_callback'] = $feature['client_callback'];
                    unset( $feature['client_callback'] );
                }

                wp_register_feature( $feature );
            }
        }
    }
}

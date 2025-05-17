<?php
return function( $input ) {
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
};

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

	$core_features = array(
		/**
		 * Posts
		 */
		array(
			'id'          => 'posts',
			'name'        => __( 'Query posts', 'wp-feature-api' ),
			'description' => __( 'Get a list of posts that match the query parameters.', 'wp-feature-api' ),
			'rest_alias'  => '/wp/v2/posts',
			'categories'  => array( 'core', 'post', 'rest' ),
			'type'        => 'resource',
		),
		array(
			'id'          => 'posts',
			'name'        => __( 'Create post', 'wp-feature-api' ),
			'description' => __( 'Create a new post.', 'wp-feature-api' ),
			'rest_alias'  => '/wp/v2/posts',
			'categories'  => array( 'core', 'post', 'rest' ),
			'type'        => 'tool',
		),
		array(
			'id'          => 'post',
			'name'        => __( 'View a post', 'wp-feature-api' ),
			'description' => __( 'Get a post by its ID.', 'wp-feature-api' ),
			'rest_alias'  => '/wp/v2/posts/(?P<id>[\d]+)',
			'categories'  => array( 'core', 'post', 'rest' ),
			'type'        => 'resource',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'id' => array(
						'type' => 'integer',
						'description' => __( 'The ID of the post to view.', 'wp-feature-api' ),
						'required' => true,
					),
				),
			),
		),
		/**
		 * Users
		 */
		array(
			'id'          => 'users',
			'name'        => __( 'Query users', 'wp-feature-api' ),
			'description' => __( 'Get a list of users that match the query parameters.', 'wp-feature-api' ),
			'rest_alias'  => '/wp/v2/users',
			'categories'  => array( 'core', 'user', 'rest' ),
			'type'        => 'resource',
		),
		array(
			'id'          => 'users',
			'name'        => __( 'Create user', 'wp-feature-api' ),
			'description' => __( 'Create a new user.', 'wp-feature-api' ),
			'rest_alias'  => '/wp/v2/users',
			'categories'  => array( 'core', 'user', 'rest' ),
			'type'        => 'tool',
		),
		array(
			'id'          => 'user',
			'name'        => __( 'View a user', 'wp-feature-api' ),
			'description' => __( 'Get a user by their ID.', 'wp-feature-api' ),
			'rest_alias'  => '/wp/v2/users/(?P<id>[\d]+)',
			'categories'  => array( 'core', 'user', 'rest' ),
			'type'        => 'resource',
			'input_schema' => array(
				'type' => 'object',
				'properties' => array(
					'id' => array(
						'type' => 'integer',
						'description' => __( 'The ID of the user to view.', 'wp-feature-api' ),
						'required' => true,
					),
				),
			),
		),
		array(
			'id'          => 'users/me',
			'name'        => __( 'Get current user', 'wp-feature-api' ),
			'description' => __( 'Get the current user.', 'wp-feature-api' ),
			'rest_alias'  => '/wp/v2/users/me',
			'categories'  => array( 'core', 'user', 'rest' ),
			'type'        => 'resource',
		),
	);
	$core_features = apply_filters( 'wp_feature_api_core_features', $core_features );

	foreach ( $core_features as $feature ) {
		wp_register_feature( $feature );
	}
}

// Register core features on init.
add_action( 'wp_feature_api_init', 'wp_feature_api_register_core_features' );

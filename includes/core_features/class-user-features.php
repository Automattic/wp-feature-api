<?php
/**
 * User features
 *
 * @package wp-feature-api
 */

declare(strict_types=1);


/**
 * User features
 *
 * @package wp-feature-api
 */
class User_Features {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_feature_api_init', array( $this, 'register_user_features' ) );
	}

	/**
	 * Register routes.
	 */
	public function register_user_features() {
		wp_register_feature(
			array(
				'id'          => 'search-users',
				'name'        => 'search_users',
				'description' => 'Search for users.',
				'rest_alias'  => '/wp/v2/users',
				'categories'  => array( 'core', 'user', 'rest' ),
				'type'        => WP_Feature::TYPE_RESOURCE,
			)
		);

		wp_register_feature(
			array(
				'id'          => 'get-current-user',
				'name'        => 'get_current_user',
				'description' => 'Get the current user. This is the user that is currently logged in.',
				'rest_alias'  => '/wp/v2/users/me',
				'categories'  => array( 'core', 'user', 'rest' ),
				'type'        => WP_Feature::TYPE_RESOURCE,
			)
		);

		wp_register_feature(
			array(
				'id'           => 'get-user-by-id',
				'name'         => 'get_user_by_id',
				'description'  => 'Get a user by their ID.',
				'rest_alias'   => '/wp/v2/users/(?P<id>[\d]+)',
				'categories'   => array( 'core', 'user', 'rest' ),
				'type'         => WP_Feature::TYPE_RESOURCE,
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array(
							'type'        => 'integer',
							'description' => __( 'The ID of the user to get.', 'wp-feature-api' ),
							'required'    => true,
						),
					),
				),
			)
		);
	}
}

new User_Features();

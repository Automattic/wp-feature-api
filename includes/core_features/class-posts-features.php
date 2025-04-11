<?php
/**
 * Register posts features
 *
 * @package wp-feature-api
 */

declare(strict_types=1);


/**
 * Posts features
 *
 * @package wp-feature-api
 */
class Posts_Features {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_feature_api_init', array( $this, 'register_post_features' ) );
	}

	/**
	 * Register routes.
	 */
	public function register_post_features() {
		wp_register_feature(
			array(
				'id'          => 'query-posts-and-pages',
				'name'        => 'query_posts_and_pages',
				'description' => 'Get a list of posts or pages that match the query parameters.',
				'rest_alias'  => '/wp/v2/posts',
				'categories'  => array( 'core', 'post', 'rest' ),
				'type'        => WP_Feature::TYPE_RESOURCE,
			)
		);

		wp_register_feature(
			array(
				'id'          => 'create-post',
				'name'        => 'create_post',
				'description' => 'Create a new post.',
				'rest_alias'  => '/wp/v2/posts',
				'categories'  => array( 'core', 'post', 'rest' ),
				'type'        => WP_Feature::TYPE_TOOL,
			)
		);
		wp_register_feature(
			array(
				'id'           => 'get-post-by-id',
				'name'         => 'get_post_by_id',
				'description'  => 'Get a post by its ID.',
				'rest_alias'   => '/wp/v2/posts/(?P<id>[\d]+)',
				'categories'   => array( 'core', 'post', 'rest' ),
				'type'         => WP_Feature::TYPE_RESOURCE,
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array(
							'type'        => 'integer',
							'description' => __( 'The ID of the post to get.', 'wp-feature-api' ),
							'required'    => true,
						),
					),
				),
			)
		);

		wp_register_feature(
			array(
				'id'          => 'update-post',
				'name'        => 'update_post',
				'description' => 'Update a post.',
				'rest_alias'  => '/wp/v2/posts/(?P<id>[\d]+)',
				'categories'  => array( 'core', 'post', 'rest' ),
				'type'        => WP_Feature::TYPE_TOOL,
			)
		);
	}
}

new Posts_Features();

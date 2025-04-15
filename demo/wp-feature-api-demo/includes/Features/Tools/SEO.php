<?php

namespace A8C\WpFeatureApiDemo\Features\Tools;

use WP_Feature;
use A8C\WpFeatureApiDemo\Factories\OpenAIClientFactory;
use A8C\WpFeatureApiDemo\Schemas\Schema;
class SEO {
	public function __invoke() {
		wp_register_feature(
			array(
				'id'          => 'demo/seo-json-ld',
				'name'        => __( 'SEO JSON-LD', 'wp-feature-api-demo' ),
				'description' => __( 'Generates JSON-LD from a page or post for use in SEO tools.', 'wp-feature-api-demo' ),
				'type'        => WP_Feature::TYPE_TOOL,
				'categories'  => array( 'demo', 'seo', 'json-ld' ),
				'input_schema' => [
					'type'       => 'object',
					'properties' => [
						'schema_name'  => [
							'type' => 'enum',
							'enum' => [
								'article',
								// 'bread-crumb-list',
								// 'faq-page',
								// 'local-business',
								// 'organization',
								// 'product',
								// 'recipe',
								// 'video-object',
								// 'website',
							],
						],
						'site'         => [
							'type'       => ['object', 'null'],
							'properties' => [
								'tagline'     => [ 'type' => 'string' ],
								'description' => [ 'type' => 'string' ],
							],
						],
						'post_title'   => [ 'type' => 'string' ],
						'post_content' => [ 'type' => 'string' ],
					],
					'required'   => [ 'schema_name', 'post_title', 'post_content', 'site' ],
					'additionalProperties' => false,
				],
				'callback'    => function( $input ) {
					$client = OpenAIClientFactory::create();
					$messages = [
							[
								'role'    => 'system',
								'content' => 'Generate valid JSON-LD for the given schema based off the post title and content and site data.',
							],
							[
								'role'    => 'user',
								'content' => wp_json_encode(
									[
										'schema_name'  => $input['schema_name'],
										'post_title'   => $input['post_title'],
										'post_content' => $input['post_content'],
										'site'         => $input['site'],
										'site_url'     => home_url(),
									]
								),
							]
					];
					$output = Schema::get($input['schema_name']);
					$response = $client->chat()->create([
						'model' => 'gpt-4o-mini',
						'messages' => $messages,
						'response_format' => [
							'type' => 'json_schema',
							'json_schema' => [
								'name' => 'json-ld',
								'strict' => false,
								'schema' => $output,
							],
						],
					]);

					return json_decode($response->choices[0]->message->content, true);
				},
				'is_eligible'    => function() {
					if (! function_exists('is_plugin_active')) {
						include_once(ABSPATH . 'wp-admin/includes/plugin.php');
					}

					return ! empty(array_filter([
						'yoast_seo' => is_plugin_active('wordpress-seo/wp-seo.php'),
						'rank_math' => is_plugin_active('seo-by-rank-math/rank-math.php')
					]));
				},
			)
		);
	}
}

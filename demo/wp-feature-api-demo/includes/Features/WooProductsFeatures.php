<?php
/**
 * Woo Products Features
 *
 * @package wp-feature-api
 */

declare( strict_types=1 );

namespace A8C\WpFeatureApiDemo\Features;

use WP_Feature;

/**
 * Woo Products Features
 */
class WooProductsFeatures {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_feature_api_init', array( $this, 'register_woo_products_features' ) );
	}

	/**
	 * Register Woo Products Features
	 */
	public function register_woo_products_features() {
		wp_register_feature(
			array(
				'id'          => 'woo-products-search',
				'name'        => 'woo_products_search',
				'description' => 'Search for WooCommerce products',
				'rest_alias'  => '/wp/v2/product',
				'categories'  => array( 'woocommerce', 'products' ),
				'type'        => WP_Feature::TYPE_RESOURCE,
				'is_eligible' => function () {
					return function_exists( 'WC' );
				},
			)
		);

		wp_register_feature(
			array(
				'id'           => 'woo-product-by-id',
				'name'         => 'woo_product_get',
				'description'  => 'Get a WooCommerce product by its ID',
				'rest_alias'   => '/wp/v2/product/(?P<id>[\d]+)',
				'categories'   => array( 'woocommerce', 'products' ),
				'type'         => WP_Feature::TYPE_RESOURCE,
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array( 'type' => 'integer' ),
					),
				),
				'is_eligible'  => function () {
					return function_exists( 'WC' );
				},
			)
		);

		wp_register_feature(
			array(
				'id'           => 'woo-product-modify',
				'name'         => 'woo_product_update',
				'description'  => 'Update a WooCommerce product',
				'rest_alias'   => '/wp/v2/product/(?P<id>[\d]+)',
				'categories'   => array( 'woocommerce', 'products' ),
				'type'         => WP_Feature::TYPE_TOOL,
				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array( 'type' => 'integer' ),
					),
				),
				'is_eligible'  => function () {
					return function_exists( 'WC' );
				},
			)
		);
	}
}

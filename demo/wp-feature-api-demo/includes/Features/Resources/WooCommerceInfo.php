<?php

namespace A8C\WpFeatureApiDemo\Features\Resources;

use WP_Feature;

class WooCommerceInfo {
	public function __invoke() {
		wp_register_feature(
			array(
				'id'          => 'demo/woocommerce-info',
				'name'        => __( 'WooCommerce Information', 'wp-feature-api-demo' ),
				'description' => __( 'Get basic information about the configuration of WooCommerce. This includes the currency, country, language, timezone, date format, and time format.', 'wp-feature-api-demo' ),
				'type'        => WP_Feature::TYPE_RESOURCE,
				'categories'  => array( 'demo', 'woocommerce', 'information' ),
				'callback'    => function() {
					return array(
						'version'               => WC()->version,
						// 'is_store_api_active'   => wc_is_rest_api_available(),
						'base_url'              => get_permalink( wc_get_page_id( 'shop' ) ),
						'cart_url'              => wc_get_cart_url(),
						'checkout_url'          => wc_get_checkout_url(),
						'account_url'           => wc_get_account_endpoint_url( 'dashboard' ),

						// Store settings
						'currency'              => get_woocommerce_currency(),
						'currency_symbol'       => get_woocommerce_currency_symbol(),
						'decimal_separator'     => wc_get_price_decimal_separator(),
						'thousand_separator'    => wc_get_price_thousand_separator(),
						'price_decimal_places'  => wc_get_price_decimals(),
						'tax_display_shop'      => get_option( 'woocommerce_tax_display_shop' ),
						'tax_display_cart'      => get_option( 'woocommerce_tax_display_cart' ),
						'prices_include_tax'    => wc_prices_include_tax(),
						'tax_enabled'           => wc_tax_enabled(),
						'shipping_enabled'      => wc_shipping_enabled(),
						'weight_unit'           => get_option( 'woocommerce_weight_unit' ),
						'dimension_unit'        => get_option( 'woocommerce_dimension_unit' ),

						// Store counts
						'product_count'         => wp_count_posts( 'product' )->publish,
						'order_count'           => wp_count_posts( 'shop_order' )->publish,
						'customer_count'        => count( get_users( array( 'role' => 'customer' ) ) ),
						'category_count'        => wp_count_terms( 'product_cat' ),

						// // Payment gateways
						// 'available_gateways'    => array_keys( WC()->payment_gateways->get_available_payment_gateways() ),
						// 'enabled_gateways'      => array_map(
						// 	function( $gateway ) {
						// 		return $gateway->id;
						// 	},
						// 	array_filter( WC()->payment_gateways->payment_gateways(), function( $gateway ) {
						// 		return $gateway->enabled === 'yes';
						// 	})
						// ),


						// // Store address
						// 'store_address'         => array(
						// 	'address_1'  => WC()->countries->get_base_address(),
						// 	'address_2'  => WC()->countries->get_base_address_2(),
						// 	'city'       => WC()->countries->get_base_city(),
						// 	'postcode'   => WC()->countries->get_base_postcode(),
						// 	'country'    => WC()->countries->get_base_country(),
						// 	'state'      => WC()->countries->get_base_state(),
						// ),

						// // Shipping
						// 'shipping_zones'        => array_map(
						// 	function( $zone ) {
						// 		return array(
						// 			'id'    => $zone->get_id(),
						// 			'name'  => $zone->get_zone_name(),
						// 		);
						// 	},
						// 	WC_Shipping_Zones::get_zones()
						// ),
						// // Pages
						// 'wc_pages'              => array(
						// 	'shop'      => wc_get_page_id( 'shop' ),
						// 	'cart'      => wc_get_page_id( 'cart' ),
						// 	'checkout'  => wc_get_page_id( 'checkout' ),
						// 	'myaccount' => wc_get_page_id( 'myaccount' ),
						// 	'terms'     => wc_get_page_id( 'terms' ),
						// ),
						// // Cart information
						// 'cart'                  => array(
						// 	'items_count'           => WC()->cart ? WC()->cart->get_cart_contents_count() : 0,
						// 	'total'                 => WC()->cart ? WC()->cart->get_cart_total() : 0,
						// 	'subtotal'              => WC()->cart ? WC()->cart->get_subtotal() : 0,
						// 	'tax_total'             => WC()->cart ? WC()->cart->get_total_tax() : 0,
						// 	'needs_shipping'        => WC()->cart ? WC()->cart->needs_shipping() : false,
						// 	'shipping_total'        => WC()->cart ? WC()->cart->get_shipping_total() : 0,
						// 	'shipping_tax'          => WC()->cart ? WC()->cart->get_shipping_tax() : 0,
						// 	'coupons'               => WC()->cart ? WC()->cart->get_applied_coupons() : array(),
						// ),
					);
				},
				'is_eligible' => function () {
					return function_exists( 'WC' );
				},
			)
		);
	}
}

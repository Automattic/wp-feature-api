<?php
/**
 * PHPUnit bootstrap file
 *
 * @package WordPress\Features_API
 */

// Composer autoloader must be loaded before WP_Mock.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load WP_Mock.
WP_Mock::bootstrap();

// Load the base test case class.
require_once __DIR__ . '/class-wp-feature-api-testcase.php';

/**
 * Set up the test environment.
 *
 * @return void
 */
function _wp_feature_api_setup_test_environment() {
	require_once dirname( __DIR__ ) . '/server/includes/class-wp-feature.php';
}

/**
 * Tear down the test environment and cleanup after tests.
 *
 * @return void
 */
function _wp_feature_api_tear_down_test_environment() {
	WP_Mock::tearDown();
}

<?php
/**
 * Base test case class for WP Feature API tests.
 *
 * @package WP_Feature_API
 */

use Mockery;
use WP_Mock\Tools\TestCase;

/**
 * Base test case class for WP Feature API.
 *
 * This class extends the WP_Mock test case and provides common functionality
 * for all WP Feature API tests.
 */
class WP_Feature_API_TestCase extends TestCase {

	/**
	 * Set up the test case.
	 *
	 * This method is run before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		_wp_feature_api_setup_test_environment();
	}

	/**
	 * Tear down the test case.
	 *
	 * This method is run after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		_wp_feature_api_tear_down_test_environment();
		parent::tearDown();
	}

	/**
	 * Helper function to set up common WordPress function mocks.
	 */
	protected function set_up_common_wp_mocks() {
		// Create a proper mock for WP_Error with required methods.
		$wp_error_mock = Mockery::mock( 'WP_Error' );
		$wp_error_mock->shouldReceive( 'get_error_message' )
			->andReturnUsing(
				function ( $code = '' ) {
					return 'Mocked error message for code: ' . $code;
				}
			);
		$wp_error_mock->shouldReceive( 'get_error_code' )->andReturn( 'error_code' );
		$wp_error_mock->shouldReceive( 'get_error_data' )->andReturn( array() );
		$wp_error_mock->shouldReceive( 'has_errors' )->andReturn( true );
		$wp_error_mock->shouldReceive( 'add' )->andReturn( true );
		$wp_error_mock->shouldReceive( 'add_data' )->andReturn( true );

		// Set up WP_Error constructor mock.
		WP_Mock::userFunction( 'WP_Error' )->andReturnUsing(
			function ( $code = '', $message = '', $data = '' ) use ( $wp_error_mock ) {
				// Return the mocked WP_Error object we created above.
				return $wp_error_mock;
			}
		);

		// Mock common WordPress functions as needed.
		WP_Mock::userFunction( 'esc_html' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'esc_attr' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'esc_url' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_kses_post' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );
		WP_Mock::userFunction( '_doing_it_wrong' )->andReturnNull();
		WP_Mock::userFunction( 'do_action' )->andReturnNull();
		WP_Mock::userFunction( 'apply_filters' )->andReturnUsing(
			function ( $hook, $value ) {
				// Return the first argument after the hook name.
				return $value;
			}
		);
		WP_Mock::userFunction( 'sanitize_key' )->andReturnUsing(
			function ( $key ) {
				return strtolower( $key );
			}
		);
		WP_Mock::userFunction( 'wp_parse_args' )->andReturnUsing(
			function ( $args, $defaults ) {
				return array_merge( $defaults, $args );
			}
		);

		WP_Mock::userFunction( 'is_wp_error' )->andReturnUsing(
			function ( $thing ) {
				return $thing instanceof WP_Error;
			}
		);
		// Mock translation functions.
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( '_e' )->andReturnArg( 0 );
		WP_Mock::userFunction( '_x' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'esc_html__' )->andReturnArg( 0 );
	}
}

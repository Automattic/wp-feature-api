<?php
/**
 * Tests for the WP_Feature class.
 *
 * @package WordPress\Features_API
 */

/**
 * Test class for WP_Feature.
 *
 * @coversDefaultClass WP_Feature
 */
class Test_WP_Feature extends WP_Feature_API_TestCase {

	/**
	 * Set up the test case.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->set_up_common_wp_mocks();
	}

	/**
	 * Test feature with valid arguments.
	 *
	 * @covers ::__construct
	 * @covers ::set_props
	 */
	public function test_construct_with_valid_args() {
		$feature_id   = 'test-feature';
		$feature_name = 'Test Feature';
		$feature_desc = 'A test feature for unit tests.';

		$feature = new WP_Feature(
			$feature_id,
			array(
				'name'        => $feature_name,
				'description' => $feature_desc,
				'type'        => WP_Feature::TYPE_RESOURCE,
			)
		);

		$this->assertInstanceOf( WP_Feature::class, $feature );
		$this->assertEquals( $feature_id, $feature->get_id() );
		$this->assertEquals( $feature_name, $feature->get_name() );
		$this->assertEquals( $feature_desc, $feature->get_description() );
		$this->assertEquals( WP_Feature::TYPE_RESOURCE, $feature->get_type() );
	}

	/**
	 * Test feature with invalid arguments.
	 *
	 * @covers ::__construct
	 * @covers ::set_props
	 */
	public function test_construct_with_invalid_args() {
		// Test with empty ID.
		$feature = new WP_Feature( '' );
		$this->assertNull( $feature->get_id() );

		// Test with non-string ID.
		$feature = new WP_Feature( 123 );
		$this->assertNull( $feature->get_id() );

		// The following tests will trigger _doing_it_wrong but still create
		// a partial object. We'll just test that the ID is set correctly.

		// Test with missing name.
		$feature = new WP_Feature( 'test-feature', array() );
		$this->assertEquals( 'test-feature', $feature->get_id() );

		// Test with missing description.
		$feature = new WP_Feature( 'test-feature', array( 'name' => 'Test Feature' ) );
		$this->assertEquals( 'test-feature', $feature->get_id() );

		// Test with invalid type.
		$feature = new WP_Feature(
			'test-feature',
			array(
				'name' => 'Test Feature',
				'description' => 'Test Description',
				'type' => 'invalid-type',
			)
		);
		$this->assertEquals( 'test-feature', $feature->get_id() );
	}

	/**
	 * Test feature getters and setters.
	 *
	 * @covers ::get_id
	 * @covers ::get_name
	 * @covers ::get_description
	 * @covers ::get_type
	 * @covers ::get_meta
	 * @covers ::get_categories
	 * @covers ::get_input_schema
	 * @covers ::get_output_schema
	 * @covers ::get_callback
	 * @covers ::get_permissions
	 * @covers ::get_filter
	 * @covers ::get_location
	 */
	public function test_getters() {
		$callback = function () {
			return 'test';
		};

		$filter = function () {
			return true;
		};

		$meta = array( 'version' => '1.0.0' );
		$categories = array( 'test', 'example' );
		$input_schema = array( 'type' => 'object' );
		$output_schema = array( 'type' => 'string' );

		$feature = new WP_Feature(
			'test-feature',
			array(
				'name'          => 'Test Feature',
				'description'   => 'Test Description',
				'type'          => WP_Feature::TYPE_TOOL,
				'meta'          => $meta,
				'categories'    => $categories,
				'input_schema'  => $input_schema,
				'output_schema' => $output_schema,
				'callback'      => $callback,
				'permissions'   => 'read',
				'filter'        => $filter,
			)
		);

		$this->assertEquals( 'test-feature', $feature->get_id() );
		$this->assertEquals( 'Test Feature', $feature->get_name() );
		$this->assertEquals( 'Test Description', $feature->get_description() );
		$this->assertEquals( WP_Feature::TYPE_TOOL, $feature->get_type() );
		$this->assertEquals( $meta, $feature->get_meta() );
		$this->assertEquals( $categories, $feature->get_categories() );
		$this->assertEquals( $input_schema, $feature->get_input_schema() );
		$this->assertEquals( $output_schema, $feature->get_output_schema() );
		$this->assertEquals( $callback, $feature->get_callback() );
		$this->assertEquals( 'read', $feature->get_permissions() );
		$this->assertEquals( $filter, $feature->get_filter() );
		$this->assertEquals( WP_Feature::LOCATION_SERVER, $feature->get_location() );
	}

	/**
	 * Test running a feature.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		// Create a mock callback that returns a specific value.
		$callback = function ( $context ) {
			return $context['test'] . ' processed';
		};

		$feature = new WP_Feature(
			'test-feature',
			array(
				'name'        => 'Test Feature',
				'description' => 'Test Description',
				'callback'    => $callback,
			)
		);

		$context = array( 'test' => 'input' );
		$result = $feature->run( $context );
		$this->assertEquals( 'input processed', $result );

		// Test running feature without a callback.
		$feature_no_callback = new WP_Feature(
			'no-callback',
			array(
				'name'        => 'No Callback',
				'description' => 'Feature without callback',
			)
		);

		$result = $feature_no_callback->run( $context );
		$this->assertEquals( $context, $result );
	}

	/**
	 * Test input validation.
	 *
	 * @covers ::run
	 * @covers ::validate_input
	 */
	public function test_input_validation() {
		// This test just verifies that input schema validation is called.
		// We'll mock a basic callback.
		$callback = function ( $context ) {
			return $context;
		};

		// Create a feature with input schema.
		$feature = new WP_Feature(
			'test-feature',
			array(
				'name'         => 'Test Feature',
				'description'  => 'Test Description',
				'callback'     => $callback,
				'input_schema' => array( 'type' => 'object' ),
			)
		);

		// Mock rest_validate_value_from_schema to return true.
		WP_Mock::userFunction( 'rest_validate_value_from_schema' )
			->andReturn( true );

		// Run should succeed and return the context.
		$context = array( 'test' => 'value' );
		$result = $feature->run( $context );
		$this->assertEquals( $context, $result );
	}

	/**
	 * Test output validation.
	 *
	 * @covers ::run
	 * @covers ::validate_output
	 */
	public function test_output_validation() {
		// This test verifies that output schema validation is called.
		// We'll mock a basic callback that returns a predictable output.
		$callback = function () {
			return 'valid output';
		};

		// Create a feature with output schema.
		$feature = new WP_Feature(
			'test-feature',
			array(
				'name'          => 'Test Feature',
				'description'   => 'Test Description',
				'callback'      => $callback,
				'output_schema' => array( 'type' => 'string' ),
			)
		);

		// Mock rest_validate_value_from_schema to return true.
		WP_Mock::userFunction( 'rest_validate_value_from_schema' )
			->andReturn( true );

		// Run should succeed and return the callback result.
		$result = $feature->run();
		$this->assertEquals( 'valid output', $result );
	}

	/**
	 * Test feature to array conversion.
	 *
	 * @covers ::to_array
	 * @covers ::get_filter_id
	 */
	public function test_to_array() {
		$feature_id = 'test/feature';
		$feature_name = 'Test Feature';
		$feature_desc = 'Test Description';
		$meta = array( 'version' => '1.0' );
		$categories = array( 'test' );
		$input_schema = array( 'type' => 'object' );
		$output_schema = array( 'type' => 'string' );
		$permissions = 'read';

		$feature = new WP_Feature(
			$feature_id,
			array(
				'name'          => $feature_name,
				'description'   => $feature_desc,
				'type'          => WP_Feature::TYPE_TOOL,
				'meta'          => $meta,
				'categories'    => $categories,
				'input_schema'  => $input_schema,
				'output_schema' => $output_schema,
				'permissions'   => $permissions,
			)
		);

		$expected = array(
			'id'            => $feature_id,
			'name'          => $feature_name,
			'description'   => $feature_desc,
			'type'          => WP_Feature::TYPE_TOOL,
			'meta'          => $meta,
			'categories'    => $categories,
			'input_schema'  => $input_schema,
			'output_schema' => $output_schema,
			'permissions'   => $permissions,
			'location'      => WP_Feature::LOCATION_SERVER,
		);

		$this->assertEquals( $expected, $feature->to_array() );
	}

	/**
	 * Test filters applied in the get_permissions method.
	 *
	 * @covers ::get_permissions
	 * @covers ::get_filter_id
	 */
	public function test_permissions_filters() {
		// Create a feature with permissions.
		$original_role = 'administrator';
		$expected_role = 'editor';

		$feature = new WP_Feature(
			'test-feature',
			array(
				'name'        => 'Test Feature',
				'description' => 'Test Description',
				'permissions' => $original_role,
			)
		);

		WP_Mock::expectFilterAdded(
			'wp_feature_permissions',
			array( $feature, 'get_permissions' ),
			20,
			1
		);

		WP_Mock::expectFilterAdded(
			'wp_feature_test_feature_permissions',
			array( $feature, 'get_permissions' ),
			20,
			1
		);

		WP_Mock::onFilter( 'wp_feature_permissions' )
			->with( $original_role )
			->reply( $expected_role );

		$actual_role = $feature->get_permissions();
		$this->assertEquals( $expected_role, $actual_role );
	}

	/**
	 * Test filters and actions applied in the run method.
	 *
	 * @covers ::run
	 * @covers ::get_filter_id
	 */
	public function test_run_filters_and_actions() {
		// Test pre_run_context filters.
		$context = array( 'input' => 'value' );
		$modified_context = array( 'input' => 'modified' );
		$output = array( 'input' => 'MODIFIED' );

		// Create a simple callback that returns a predictable value.
		$callback = function ( $ctx ) {
			return array_map( 'strtoupper', $ctx );
		};

		// Create a feature.
		$feature = new WP_Feature(
			'test-feature',
			array(
				'name'        => 'Test Feature',
				'description' => 'Test Description',
				'callback'    => $callback,
			)
		);

		// Set up pre_run_context filter expectations.
		WP_Mock::onFilter( 'wp_feature_pre_run_context' )
			->with( $context, $feature )
			->reply( $modified_context );

		WP_Mock::onFilter( 'wp_feature_test_feature_pre_run_context' )
			->with( $modified_context, $feature )
			->reply( $modified_context );

		// Set up before_run action expectations.
		WP_Mock::expectAction( 'wp_feature_before_run', $modified_context, $feature );
		WP_Mock::expectAction( 'wp_feature_test_feature_before_run', $modified_context, $feature );

		// Set up run_result filter expectations.
		$initial_result = $output; // The result after callback execution.
		WP_Mock::onFilter( 'wp_feature_run_result' )
			->with( $initial_result, $modified_context, $feature )
			->reply( $initial_result );

		WP_Mock::onFilter( 'wp_feature_test_feature_run_result' )
			->with( $initial_result, $modified_context, $feature )
			->reply( $initial_result );

		// Set up after_run action expectations.
		WP_Mock::expectAction( 'wp_feature_after_run', $initial_result, $modified_context, $feature );
		WP_Mock::expectAction( 'wp_feature_test_feature_after_run', $initial_result, $modified_context, $feature );

		// Run the feature with the initial context.
		$result = $feature->run( $context );

		// Verify the result matches our expected output.
		$this->assertEquals( $output, $result );
	}
}

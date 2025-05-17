<?php
/**
 * OneOf Schema Test.
 *
 * @package WordPress\Features_API
 */
class WP_Schema_OneOf_Test extends WP_UnitTestCase {
        public function set_up() {
                parent::set_up();
                wp_register_feature( array(
                        'id'          => 'oneof-test',
                        'name'        => 'OneOf Test',
                        'description' => 'Test feature with oneOf schema',
                        'type'        => 'tool',
                        'callback'    => function( $context ) { return $context; },
                        'input_schema' => array(
                                'type'       => 'object',
                                'properties' => array(
                                        'value' => array(
                                                'oneOf'   => array(
                                                        array( 'type' => 'string' ),
                                                        array( 'type' => 'integer' ),
                                                ),
                                                'required' => true,
                                        ),
                                ),
                        ),
                        'output_schema' => array(
                                'type'       => 'object',
                                'properties' => array(
                                        'value' => array(
                                                'oneOf' => array(
                                                        array( 'type' => 'string' ),
                                                        array( 'type' => 'integer' ),
                                                ),
                                        ),
                                ),
                        ),
                ) );
        }

        public function tear_down() {
                wp_unregister_feature( 'oneof-test' );
                parent::tear_down();
        }

        public function test_oneof_accepts_string_or_integer() {
                $feature = wp_find_feature( 'oneof-test' );
                $request = new WP_REST_Request( $feature->get_rest_method(), '/' );
                $request->set_param( 'value', 'hello' );
                $result = $feature->call( $request );
                $this->assertIsArray( $result );
                $this->assertSame( 'hello', $result['value'] );

                $request = new WP_REST_Request( $feature->get_rest_method(), '/' );
                $request->set_param( 'value', 123 );
                $result = $feature->call( $request );
                $this->assertIsArray( $result );
                $this->assertSame( 123, $result['value'] );
        }

        public function test_oneof_rejects_invalid_type() {
                $feature = wp_find_feature( 'oneof-test' );
                $request = new WP_REST_Request( $feature->get_rest_method(), '/' );
                $request->set_param( 'value', array( 'invalid' ) );
                $result = $feature->call( $request );
                $this->assertInstanceOf( WP_Error::class, $result );
        }
}

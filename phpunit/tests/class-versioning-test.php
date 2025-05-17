<?php
/**
 * Versioning Helper Tests.
 *
 * @package WordPress\Features_API
 */
class WP_Versioning_Test extends WP_UnitTestCase {
    public function test_is_deprecated_returns_true_when_version_after_deprecated() {
        $feature = new WP_Feature( 'test/deprecated', array(
            'name' => 'Test',
            'description' => 'desc',
            'version' => '2.0.0',
            'deprecated_version' => '1.0.0',
        ) );

        $this->assertTrue( $feature->is_deprecated() );
    }

    public function test_get_alternatives_returns_value() {
        $feature = new WP_Feature( 'test/alt', array(
            'name' => 'Test',
            'description' => 'desc',
            'alternatives' => array( 'other/feature' ),
        ) );

        $this->assertSame( array( 'other/feature' ), $feature->get_alternatives() );
    }

    public function test_deprecated_hooks_fire_and_filter_blocks_execution() {
        $feature = new WP_Feature( 'test/hook', array(
            'name' => 'Test',
            'description' => 'desc',
            'version' => '2.0.0',
            'deprecated_version' => '1.0.0',
            'callback' => function() {
                return 'ran';
            },
        ) );

        $triggered = 0;
        add_action( 'wp_feature_deprecated_run', function() use ( &$triggered ) {
            $triggered++;
        }, 10, 4 );

        add_filter( 'wp_feature_handle_deprecated', '__return_false', 10, 3 );

        $request = new WP_REST_Request( 'POST', '/test' );
        $result  = $feature->call( $request );

        remove_filter( 'wp_feature_handle_deprecated', '__return_false', 10 );

        $this->assertSame( 1, $triggered );
        $this->assertWPError( $result );
    }
}

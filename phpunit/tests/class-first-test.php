<?php
/**
 * Initial Test Class.
 *
 * @package WordPress\Features_API
 */
class WP_First_Test extends WP_UnitTestCase {
	protected static $admin_id;
	protected static $post_id;

	public static function wpSetupBeforeClass( $factory ) {
		self::$admin_id = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		self::$post_id = self::factory()->post->create(
			array(
				'post_status'   => 'publish',
				'post_date'     => '2017-02-14 00:00:00',
				'post_date_gmt' => '2017-02-14 00:00:00',
				'post_title'   => 'My Test post',
			)
		);
	}

	public static function wpTearDownAfterClass() {
		self::delete_user( self::$admin_id );
		wp_delete_post( self::$post_id );
	}

	public function set_up() {
		parent::set_up();
	}

	public function test_populates_target_hints_for_administrator() {
		wp_set_current_user( self::$admin_id );
		$response = rest_do_request( '/wp/v2/posts' );
		$post     = $response->get_data()[0];
		$this->assertSame( 'My Test post', $post['title']['rendered'] );
	}
}

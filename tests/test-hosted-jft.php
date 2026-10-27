<?php
/**
 * Integration tests for the Hosted JFT plugin.
 */

class Test_Hosted_JFT extends WP_UnitTestCase {

	const CUSTOM_FIELD = 'jft_date';

	public function setUp(): void {
		parent::setUp();
		update_option( 'jft_custom_field', self::CUSTOM_FIELD );
		update_option( 'jft_timezone', '' );
	}

	public function tearDown(): void {
		delete_option( 'jft_custom_field' );
		delete_option( 'jft_timezone' );
		delete_option( 'jft_page_url' );
		delete_option( 'jft_more_text' );
		parent::tearDown();
	}

	/**
	 * Create a published post matching "today" (UTC) via the custom field.
	 *
	 * @param array $overrides Post field overrides.
	 * @return int Post ID.
	 */
	private function make_todays_post( array $overrides = [] ): int {
		$post_id = self::factory()->post->create(
			array_merge(
				[
					'post_title'   => 'Just For Today',
					'post_content' => 'Today we practice acceptance.',
					'post_excerpt' => 'Acceptance.',
					'post_status'  => 'publish',
				],
				$overrides
			)
		);
		update_post_meta( $post_id, self::CUSTOM_FIELD, gmdate( 'm-d' ) );
		return $post_id;
	}

	// -------------------------------------------------------------------------
	// Wiring
	// -------------------------------------------------------------------------

	public function test_singleton_returns_same_instance() {
		$this->assertSame( Hosted_JFT::get_instance(), Hosted_JFT::get_instance() );
	}

	public function test_shortcode_is_registered() {
		$this->assertTrue( shortcode_exists( 'hosted_jft' ) );
	}

	public function test_rest_route_is_registered() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/hosted-jft/v1/jft', $routes );
	}

	// -------------------------------------------------------------------------
	// sanitize_timezone
	// -------------------------------------------------------------------------

	public function test_sanitize_timezone_accepts_valid() {
		$this->assertSame( 'America/New_York', Hosted_JFT::sanitize_timezone( 'America/New_York' ) );
	}

	public function test_sanitize_timezone_rejects_invalid() {
		$this->assertSame( '', Hosted_JFT::sanitize_timezone( 'Not/AZone' ) );
		$this->assertSame( '', Hosted_JFT::sanitize_timezone( 'america/new_york' ) );
	}

	public function test_sanitize_timezone_trims_and_handles_non_string() {
		$this->assertSame( 'UTC', Hosted_JFT::sanitize_timezone( '  UTC  ' ) );
		$this->assertSame( '', Hosted_JFT::sanitize_timezone( '' ) );
		$this->assertSame( '', Hosted_JFT::sanitize_timezone( null ) );
		$this->assertSame( '', Hosted_JFT::sanitize_timezone( [ 'UTC' ] ) );
	}

	// -------------------------------------------------------------------------
	// get_jft
	// -------------------------------------------------------------------------

	public function test_get_jft_returns_null_without_custom_field() {
		delete_option( 'jft_custom_field' );
		$this->make_todays_post();
		$this->assertNull( Hosted_JFT::get_jft() );
	}

	public function test_get_jft_returns_null_when_no_post_for_today() {
		$this->assertNull( Hosted_JFT::get_jft() );
	}

	public function test_get_jft_finds_todays_post() {
		$this->make_todays_post();
		$jft = Hosted_JFT::get_jft();
		$this->assertIsArray( $jft );
		$this->assertSame( 'Just For Today', $jft['title'] );
		$this->assertSame( 'Today we practice acceptance.', $jft['content'] );
		$this->assertSame( 'Acceptance.', $jft['excerpt'] );
		$this->assertNotEmpty( $jft['url'] );
	}

	public function test_get_jft_ignores_non_matching_dates() {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		update_post_meta( $post_id, self::CUSTOM_FIELD, '01-01' === gmdate( 'm-d' ) ? '12-31' : '01-01' );
		$this->assertNull( Hosted_JFT::get_jft() );
	}

	// -------------------------------------------------------------------------
	// Shortcode
	// -------------------------------------------------------------------------

	public function test_shortcode_outputs_title_and_content() {
		$this->make_todays_post();
		$html = do_shortcode( '[hosted_jft]' );
		$this->assertStringContainsString( 'Just For Today', $html );
		$this->assertStringContainsString( 'Today we practice acceptance.', $html );
		$this->assertStringContainsString( 'spo-title', $html );
	}

	public function test_shortcode_without_post_shows_message() {
		$html = do_shortcode( '[hosted_jft]' );
		$this->assertStringContainsString( 'No JFT found for today', $html );
	}

	public function test_shortcode_escapes_title() {
		$this->make_todays_post( [ 'post_title' => '<script>alert(1)</script>' ] );
		$html = do_shortcode( '[hosted_jft]' );
		$this->assertStringNotContainsString( '<script>alert(1)</script>', $html );
	}

	// -------------------------------------------------------------------------
	// REST endpoint
	// -------------------------------------------------------------------------

	public function test_rest_endpoint_returns_todays_jft() {
		$this->make_todays_post();
		$request  = new WP_REST_Request( 'GET', '/hosted-jft/v1/jft' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'Just For Today', $data[0]['title'] );
	}

	public function test_rest_endpoint_404_when_no_post() {
		$request  = new WP_REST_Request( 'GET', '/hosted-jft/v1/jft' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 404, $response->get_status() );
	}

	public function test_rest_endpoint_rejects_invalid_timezone() {
		$this->make_todays_post();
		$request = new WP_REST_Request( 'GET', '/hosted-jft/v1/jft' );
		$request->set_param( 'tz', 'Not/AZone' );
		$response = rest_get_server()->dispatch( $request );
		// Invalid tz is sanitized to '' and falls back to the configured/UTC zone.
		$this->assertSame( 200, $response->get_status() );
	}

	// -------------------------------------------------------------------------
	// Widget
	// -------------------------------------------------------------------------

	public function test_widget_outputs_excerpt_and_link() {
		$this->make_todays_post();
		update_option( 'jft_page_url', 'https://example.org/jft/' );
		update_option( 'jft_more_text', 'Read more' );

		$widget = new Hosted_JFT_Widget();
		$args   = [
			'before_widget' => '<section>',
			'after_widget'  => '</section>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
		];

		ob_start();
		$widget->widget( $args, [ 'title' => 'JFT' ] );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Acceptance.', $html );
		$this->assertStringContainsString( 'https://example.org/jft/', $html );
		$this->assertStringContainsString( 'Read more', $html );
	}

	public function test_widget_without_post_shows_message() {
		$widget = new Hosted_JFT_Widget();
		$args   = [
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '',
			'after_title'   => '',
		];

		ob_start();
		$widget->widget( $args, [] );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'No JFT found for today', $html );
	}
}

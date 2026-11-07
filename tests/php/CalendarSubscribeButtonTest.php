<?php

namespace BernskioldMedia\WP\EventsCalendarIcalFeeds\Tests;

use BernskioldMedia\WP\EventsCalendarIcalFeeds\Calendar_Subscribe_Button;
use BernskioldMedia\WP\EventsCalendarIcalFeeds\Endpoints;

/**
 * @covers \BernskioldMedia\WP\EventsCalendarIcalFeeds\Calendar_Subscribe_Button
 */
class CalendarSubscribeButtonTest extends TestCase {

	public function set_up(): void {
		parent::set_up();

		update_option( 'blogname', 'Test Site' );
		$this->set_permalink_structure( '/%postname%/' );
		tribe_update_option( 'eventsSlug', 'events' );
	}

	public function tear_down(): void {
		remove_all_filters( 'events_calendar_ical_feeds_app_links' );

		parent::tear_down();
	}

	public function test_replaces_the_events_calendar_ical_link(): void {
		$this->assertSame( 10, has_filter( 'tribe_template_html:events/v2/components/ical-link', [ Calendar_Subscribe_Button::class, 'render' ] ) );
	}

	public function test_app_links(): void {
		$links = Calendar_Subscribe_Button::get_app_links( 'https://example.org/events/ical-feed', 'Test Site' );

		$this->assertSame( [ 'apple', 'google', 'outlook', 'microsoft' ], array_keys( $links ) );

		$this->assertSame( 'webcal://example.org/events/ical-feed', $links['apple']['url'] );
		$this->assertSame( 'https://calendar.google.com/calendar/render?cid=webcal%3A%2F%2Fexample.org%2Fevents%2Fical-feed', $links['google']['url'] );
		$this->assertSame(
			'https://outlook.live.com/owa?path=/calendar/action/compose&rru=addsubscription&url=webcal%3A%2F%2Fexample.org%2Fevents%2Fical-feed&name=Test%20Site',
			$links['outlook']['url']
		);
		$this->assertStringStartsWith( 'https://outlook.office.com/owa?', $links['microsoft']['url'] );
	}

	public function test_app_links_keep_the_query_string(): void {
		$links = Calendar_Subscribe_Button::get_app_links( 'http://example.org/events/ical-feed?categories=5', 'Concerts' );

		$this->assertSame( 'webcal://example.org/events/ical-feed?categories=5', $links['apple']['url'] );
		$this->assertStringContainsString( 'cid=webcal%3A%2F%2Fexample.org%2Fevents%2Fical-feed%3Fcategories%3D5', $links['google']['url'] );
	}

	public function test_app_links_can_be_filtered(): void {
		add_filter( 'events_calendar_ical_feeds_app_links', static function ( array $links ) {
			unset( $links['microsoft'] );

			return $links;
		} );

		$this->assertArrayNotHasKey( 'microsoft', Calendar_Subscribe_Button::get_app_links( 'https://example.org/feed', 'Name' ) );
	}

	public function test_render_offers_the_full_feed(): void {
		$this->go_to( home_url( '/' ) );

		$html = Calendar_Subscribe_Button::render( '<a>Original</a>' );

		$this->assertStringNotContainsString( 'Original', $html );
		$this->assertStringContainsString( 'class="ecif-subscription ', $html );
		$this->assertStringContainsString( 'value="' . esc_attr( Endpoints::get_feed_url() ) . '"', $html );
		$this->assertStringContainsString( 'href="webcal://example.org/events/ical-feed"', $html );
		// Only one feed, so there is nothing to choose between.
		$this->assertStringNotContainsString( 'type="radio"', $html );
	}

	public function test_render_offers_the_category_of_the_view(): void {
		$term = self::factory()->term->create_and_get( [ 'taxonomy' => 'tribe_events_cat', 'name' => 'Concerts', 'slug' => 'concerts' ] );

		$html = Calendar_Subscribe_Button::render( '', null, null, $this->view_template( [ 'tribe_events_cat' => 'concerts' ] ) );

		$this->assertSame( 2, substr_count( $html, 'type="radio"' ) );
		$this->assertStringContainsString( 'All events', $html );
		$this->assertStringContainsString( 'Only events in Concerts', $html );
		$this->assertStringContainsString( esc_attr( Endpoints::get_prefiltered_feed_url( $term->term_id ) ), $html );
		$this->assertStringContainsString( 'name=Concerts%20at%20Test%20Site', $html );
	}

	public function test_render_offers_the_tag_of_the_view(): void {
		$term = self::factory()->tag->create_and_get( [ 'name' => 'Free', 'slug' => 'free' ] );

		$html = Calendar_Subscribe_Button::render( '', null, null, $this->view_template( [ 'tag' => 'free' ] ) );

		$this->assertStringContainsString( 'Only events tagged Free', $html );
		$this->assertStringContainsString( esc_attr( Endpoints::get_prefiltered_feed_url( null, $term->term_id ) ), $html );
	}

	public function test_render_ignores_unknown_terms(): void {
		$this->go_to( home_url( '/' ) );

		$html = Calendar_Subscribe_Button::render( '', null, null, $this->view_template( [ 'tribe_events_cat' => 'does-not-exist' ] ) );

		$this->assertStringNotContainsString( 'type="radio"', $html );
	}

	/**
	 * A stand-in for The Events Calendar's template object, which knows the
	 * URL arguments of the view being rendered.
	 *
	 * @param array<string, string> $url_args
	 */
	protected function view_template( array $url_args ): object {
		$view = new class( $url_args ) {
			public function __construct( private array $url_args ) {}

			public function get_url_args(): array {
				return $this->url_args;
			}
		};

		return new class( $view ) {
			public function __construct( private object $view ) {}

			public function get_view(): object {
				return $this->view;
			}
		};
	}

}

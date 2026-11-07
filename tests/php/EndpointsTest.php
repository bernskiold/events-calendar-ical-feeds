<?php

namespace BernskioldMedia\WP\EventsCalendarIcalFeeds\Tests;

use BernskioldMedia\WP\EventsCalendarIcalFeeds\Endpoints;
use BernskioldMedia\WP\EventsCalendarIcalFeeds\Install;
use BernskioldMedia\WP\EventsCalendarIcalFeeds\Plugin;

/**
 * @covers \BernskioldMedia\WP\EventsCalendarIcalFeeds\Endpoints
 * @covers \BernskioldMedia\WP\EventsCalendarIcalFeeds\Install
 */
class EndpointsTest extends TestCase {

	public function set_up(): void {
		parent::set_up();

		$this->set_permalink_structure( '/%postname%/' );
		tribe_update_option( 'eventsSlug', 'events' );
	}

	public function tear_down(): void {
		remove_all_filters( 'events_calendar_ical_feeds_feed_slug' );
		remove_all_filters( 'events_calendar_ical_feeds_prefiltered_url' );

		parent::tear_down();
	}

	public function test_hooks_are_registered(): void {
		$this->assertSame( 10, has_action( 'init', [ Endpoints::class, 'rules' ] ) );
		$this->assertSame( 10, has_filter( 'query_vars', [ Endpoints::class, 'query_vars' ] ) );
		$this->assertSame( 10, has_filter( 'template_include', [ Endpoints::class, 'router' ] ) );
	}

	public function test_install_adds_the_rewrite_rule(): void {
		Install::install();

		$rules = get_option( 'rewrite_rules' );

		$this->assertArrayHasKey( '^events/ical-feed[/]?$', $rules );
		$this->assertSame( 'index.php?calendar_feed=true', $rules['^events/ical-feed[/]?$'] );
	}

	public function test_feed_slug_can_be_filtered(): void {
		add_filter( 'events_calendar_ical_feeds_feed_slug', static fn() => 'subscribe' );

		$this->assertSame( home_url( 'events/subscribe' ), Endpoints::get_feed_url() );

		Install::install();

		$this->assertArrayHasKey( '^events/subscribe[/]?$', get_option( 'rewrite_rules' ) );
	}

	public function test_feed_url_uses_the_events_slug(): void {
		tribe_update_option( 'eventsSlug', 'kalender' );

		$this->assertSame( home_url( 'kalender/ical-feed' ), Endpoints::get_feed_url() );
	}

	public function test_query_var_is_added(): void {
		$this->assertContains( 'calendar_feed', Endpoints::query_vars( [] ) );
	}

	public function test_feed_url_resolves_to_the_feed_query_var(): void {
		Install::install();

		$this->go_to( Endpoints::get_feed_url() );

		$this->assertSame( 'true', get_query_var( 'calendar_feed' ) );
		$this->assertSame( Plugin::get_path( 'views/ical-feed.php' ), Endpoints::router( 'index.php' ) );
	}

	public function test_router_leaves_other_templates_alone(): void {
		$this->go_to( home_url( '/' ) );

		$this->assertSame( 'index.php', Endpoints::router( 'index.php' ) );
	}

	public function test_prefiltered_url_with_category(): void {
		$this->assertSame(
			add_query_arg( 'categories', 12, Endpoints::get_feed_url() ),
			Endpoints::get_prefiltered_feed_url( 12 )
		);
	}

	public function test_prefiltered_url_with_tag(): void {
		$this->assertSame(
			add_query_arg( 'tags', 7, Endpoints::get_feed_url() ),
			Endpoints::get_prefiltered_feed_url( null, 7 )
		);
	}

	public function test_prefiltered_url_prefers_category_over_tag(): void {
		$this->assertSame(
			add_query_arg( 'categories', 12, Endpoints::get_feed_url() ),
			Endpoints::get_prefiltered_feed_url( 12, 7 )
		);
	}

	public function test_prefiltered_url_without_terms_is_the_feed_url(): void {
		$this->go_to( home_url( '/' ) );

		$this->assertSame( Endpoints::get_feed_url(), Endpoints::get_prefiltered_feed_url() );
	}

	public function test_prefiltered_url_uses_the_queried_category(): void {
		$term = self::factory()->term->create_and_get( [ 'taxonomy' => 'tribe_events_cat', 'name' => 'Concerts' ] );
		$this->create_event( [ 'terms' => [ 'tribe_events_cat' => [ $term->term_id ] ] ] );

		$this->go_to( get_term_link( $term ) );

		$this->assertTrue( is_tax( 'tribe_events_cat' ) );
		$this->assertSame(
			add_query_arg( 'categories', $term->term_id, Endpoints::get_feed_url() ),
			Endpoints::get_prefiltered_feed_url()
		);
	}

	public function test_prefiltered_url_can_be_filtered(): void {
		add_filter( 'events_calendar_ical_feeds_prefiltered_url', static fn( $url ) => add_query_arg( 'amount', 10, $url ) );

		$this->assertSame(
			add_query_arg( [ 'categories' => 12, 'amount' => 10 ], Endpoints::get_feed_url() ),
			Endpoints::get_prefiltered_feed_url( 12 )
		);
	}

}

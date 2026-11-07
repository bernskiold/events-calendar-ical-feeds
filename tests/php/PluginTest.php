<?php

namespace BernskioldMedia\WP\EventsCalendarIcalFeeds\Tests;

use BernskioldMedia\WP\EventsCalendarIcalFeeds\Plugin;

/**
 * @covers \BernskioldMedia\WP\EventsCalendarIcalFeeds\Plugin
 */
class PluginTest extends TestCase {

	public function tear_down(): void {
		wp_deregister_script( 'alpinejs' );
		wp_deregister_script( 'ecif-main' );
		wp_deregister_style( 'ecif-main' );
		remove_all_filters( 'events_calendar_ical_load_assets' );

		parent::tear_down();
	}

	public function test_boots_with_the_events_calendar(): void {
		$this->assertTrue( Plugin::has_dependencies() );
		$this->assertInstanceOf( Plugin::class, events_calendar_ical_feeds() );
		$this->assertSame( 1, did_action( 'events_calendar_ical_feeds_loaded' ) );
	}

	public function test_registers_built_assets(): void {
		Plugin::assets();

		$scripts = wp_scripts();
		$styles  = wp_styles();

		$this->assertSame( Plugin::get_url( 'dist/alpinejs.js' ), $scripts->registered['alpinejs']->src );
		$this->assertSame( Plugin::get_url( 'dist/ical-feed.js' ), $scripts->registered['ecif-main']->src );
		$this->assertContains( 'alpinejs', $scripts->registered['ecif-main']->deps );
		$this->assertSame( Plugin::get_url( 'dist/ical-feed.css' ), $styles->registered['ecif-main']->src );
		$this->assertSame( 'replace', $styles->get_data( 'ecif-main', 'rtl' ) );

		// Versions come from the generated asset files.
		$asset = require Plugin::get_path( 'dist/ical-feed.asset.php' );
		$this->assertSame( $asset['version'], $scripts->registered['ecif-main']->ver );
	}

	public function test_keeps_an_alpinejs_the_site_registered(): void {
		wp_register_script( 'alpinejs', 'https://example.org/alpine.js', [], '3.0.0', true );

		Plugin::assets();

		$this->assertSame( 'https://example.org/alpine.js', wp_scripts()->registered['alpinejs']->src );
	}

	public function test_assets_can_be_disabled(): void {
		add_filter( 'events_calendar_ical_load_assets', '__return_false' );

		Plugin::assets();

		$this->assertFalse( wp_script_is( 'ecif-main', 'registered' ) );
	}

	public function test_assets_are_only_enqueued_on_calendar_pages(): void {
		$this->go_to( home_url( '/' ) );
		Plugin::assets();

		$this->assertFalse( wp_script_is( 'ecif-main', 'enqueued' ) );

		$this->go_to( get_permalink( $this->create_event() ) );
		Plugin::assets();

		$this->assertTrue( wp_script_is( 'ecif-main', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'ecif-main', 'enqueued' ) );
	}

}

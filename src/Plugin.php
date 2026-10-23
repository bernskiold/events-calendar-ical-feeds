<?php

namespace BernskioldMedia\WP\EventsCalendarIcalFeeds;

use ECIF_Vendor\BernskioldMedia\WP\PluginBase\BasePlugin;

defined( 'ABSPATH' ) || exit;

class Plugin extends BasePlugin {

	protected static string $slug             = 'events_calendar_ical_feeds';
	protected static string $version          = '1.3.0';
	protected static string $textdomain       = 'events-calendar-ical-feeds';
	protected static string $plugin_file_path = EVENTS_CALENDAR_ICAL_FEEDS_FILE_PATH;

	protected static array $boot = [
		Endpoints::class,
		Calendar_Subscribe_Button::class,
	];

	protected function init_hooks(): void {
		parent::init_hooks();

		add_action( 'wp_enqueue_scripts', [ self::class, 'assets' ] );
	}

	public static function assets(): void {
		// Give user the option of disabling assets.
		if ( true !== apply_filters( 'events_calendar_ical_load_assets', true ) ) {
			return;
		}

		if ( ! wp_script_is( 'alpinejs', 'registered' ) ) {
			wp_register_script( 'alpinejs', self::get_assets_url( 'scripts/dist/alpinejs.js' ), [], '3.5.1', true );
		}

		wp_register_script( 'ecif-main', self::get_assets_url( 'scripts/dist/ical-feed.js' ), [ 'alpinejs' ], self::get_version(), true );
		wp_register_style( 'ecif-main', self::get_assets_url( 'styles/ical-feed.css' ), [], self::get_version(), 'screen' );

		// Only enqueue where we need it.
		if ( is_post_type_archive( 'tribe_events' ) || is_singular( 'tribe_events' ) || get_post_type() === 'tribe_organizer' || get_post_type() === 'tribe_venue'  ) {
			wp_enqueue_script( 'alpinejs' );
			wp_enqueue_script( 'ecif-main' );
			wp_enqueue_style( 'ecif-main' );
		}
	}

}

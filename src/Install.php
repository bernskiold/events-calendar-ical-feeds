<?php
/**
 * Installer
 *
 * @package BernskioldMedia\WP\EventsCalendarIcalFeeds
 */

namespace BernskioldMedia\WP\EventsCalendarIcalFeeds;

defined( 'ABSPATH' ) || exit;

class Install {

	/**
	 * Runs on activation. The feed rewrite rule is registered on init, which
	 * has already run, so add it before flushing or the feed 404s until the
	 * permalinks are saved.
	 */
	public static function install(): void {
		Endpoints::rules();
		flush_rewrite_rules();

		do_action( 'events_calendar_ical_feeds_install' );
	}

	/**
	 * Runs on deactivation and drops the feed rewrite rule.
	 */
	public static function uninstall(): void {
		flush_rewrite_rules();
	}

}

<?php
/**
 * Plugin Name:         iCalendar Feeds for The Events Calendar
 * Plugin URI:          https://github.com/bernskiold/events-calendar-ical-feeds
 * Description:         Extends The Events Calendar with iCal feeds that users can subscribe to in their favorite calendar application.
 * Version:             1.3.0
 * Requires at least:   7.0
 * Tested up to:        7.1
 * Requires PHP:        8.2
 * Requires Plugins:    the-events-calendar
 * Author:              Bernskiold Media
 * Author URI:          https://bernskioldmedia.com
 * License:             GPL-3.0-or-later
 * Text Domain:         events-calendar-ical-feeds
 * Domain Path:         /languages/
 *
 * @package BernskioldMedia\WP\EventsCalendarIcalFeeds
 */

use BernskioldMedia\WP\EventsCalendarIcalFeeds\Install;
use BernskioldMedia\WP\EventsCalendarIcalFeeds\Plugin;
use YahnisElsts\PluginUpdateChecker\v6\PucFactory;

defined( 'ABSPATH' ) || exit;

/**
 * Autoloader
 */
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

if ( file_exists( WP_CONTENT_DIR . '/vendor/autoload.php' ) ) {
	require_once WP_CONTENT_DIR . '/vendor/autoload.php';
}

/**
 * Basic Constants
 */
define( 'EVENTS_CALENDAR_ICAL_FEEDS_FILE_PATH', __FILE__ );

register_activation_hook( __FILE__, [ Install::class, 'install' ] );
register_deactivation_hook( __FILE__, [ Install::class, 'uninstall' ] );

/**
 * Initialize and boot the plugin once The Events Calendar has loaded.
 *
 * @return Plugin|null
 */
function events_calendar_ical_feeds(): ?Plugin {
	if ( ! Plugin::has_dependencies() ) {
		return null;
	}

	return Plugin::instance();
}

add_action( 'plugins_loaded', 'events_calendar_ical_feeds' );

/**
 * Update the plugin from the ZIP attached to each GitHub release.
 */
if ( class_exists( PucFactory::class ) ) {
	$events_calendar_ical_feeds_updater = PucFactory::buildUpdateChecker( 'https://github.com/bernskiold/events-calendar-ical-feeds', __FILE__, 'events-calendar-ical-feeds' );
	$events_calendar_ical_feeds_updater->getVcsApi()->enableReleaseAssets();
}

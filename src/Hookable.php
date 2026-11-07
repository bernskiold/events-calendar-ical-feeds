<?php

namespace BernskioldMedia\WP\EventsCalendarIcalFeeds;

defined( 'ABSPATH' ) || exit;

/**
 * Classes booted by the plugin register their hooks in a static hooks() method.
 */
interface Hookable {

	public static function hooks(): void;

}

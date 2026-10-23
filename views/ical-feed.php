<?php
/**
 * Generate the iCal Feed
 *
 * This file processes query params into filterable arguments,
 * builds the Calendar Feed and echos the string to the page.
 */

header( 'Content-Type: text/calendar; charset=utf-8' );
header( 'Content-Disposition: inline; filename="calendar.ics"' );

$feed = new \BernskioldMedia\WP\EventsCalendarIcalFeeds\Calendar_Feed();

if ( isset( $_GET['from'] ) ) {
	$from = (string) wp_strip_all_tags( $_GET['from'] );
	$feed->from( $from );
}
else {
	$default_start_date = apply_filters( 'events_calendar_ical_feeds_default_start_date', '-30 days' );
	$feed->from( $default_start_date );
}

if ( isset( $_GET['to'] ) ) {
	$to = (string) wp_strip_all_tags( $_GET['to'] );
	$feed->to( $to );
}
else {
	$default_end_date = apply_filters( 'events_calendar_ical_feeds_default_end_date', '+6 months' );
	$feed->to( $default_end_date );
}

if ( isset( $_GET['venue'] ) ) {
	$venue_id = (int) wp_strip_all_tags( $_GET['venue'] );
	$feed->venue( $venue_id );
}

if ( isset( $_GET['categories'] ) ) {
	$categories = explode( ',', wp_strip_all_tags( $_GET['categories'] ) );
	$feed->categories( $categories );
}

if ( isset( $_GET['tags'] ) ) {
	$tags = explode( ',', wp_strip_all_tags( $_GET['tags'] ) );
	$feed->tags( $tags );
}

if ( isset( $_GET['amount'] ) ) {
	$amount = (int) wp_strip_all_tags( $_GET['amount'] );
	$feed->amount( $amount );
}

echo $feed->create();
exit;

<?php

namespace BernskioldMedia\WP\EventsCalendarIcalFeeds\Tests;

use BernskioldMedia\WP\EventsCalendarIcalFeeds\Calendar_Feed;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

/**
 * @covers \BernskioldMedia\WP\EventsCalendarIcalFeeds\Calendar_Feed
 */
class CalendarFeedTest extends TestCase {

	public function set_up(): void {
		parent::set_up();

		update_option( 'blogname', 'Test Site' );
	}

	public function tear_down(): void {
		remove_all_filters( 'events_calendar_ical_feeds_max_amount' );
		remove_all_actions( 'events_calendar_ical_create_calendar' );
		remove_all_actions( 'events_calendar_ical_event_from_id' );

		parent::tear_down();
	}

	/**
	 * The feed with its folded lines unfolded, so long values can be matched.
	 */
	protected function create_feed( Calendar_Feed $feed ): string {
		return str_replace( "\r\n ", '', $feed->create() );
	}

	protected function feed(): Calendar_Feed {
		return ( new Calendar_Feed() )->from( '-30 days' )->to( '+6 months' );
	}

	public function test_feed_is_a_calendar_named_after_the_site(): void {
		$ical = $this->create_feed( $this->feed() );

		$this->assertStringStartsWith( "BEGIN:VCALENDAR\r\n", $ical );
		$this->assertStringContainsString( "NAME:Test Site\r\n", $ical );
		$this->assertStringContainsString( "DESCRIPTION:Events from Test Site\r\n", $ical );
		$this->assertStringContainsString( "REFRESH-INTERVAL;VALUE=DURATION:PT60M\r\n", $ical );
		$this->assertStringEndsWith( "END:VCALENDAR", rtrim( $ical ) );
	}

	public function test_feed_contains_events_in_range(): void {
		$event_id = $this->create_event( [ 'post_title' => 'Summer concert' ] );
		$this->create_event( [ 'post_title' => 'Long ago', 'start' => '-1 year 10:00', 'end' => '-1 year 12:00' ] );

		$ical = $this->create_feed( $this->feed() );

		$this->assertStringContainsString( "SUMMARY:Summer concert\r\n", $ical );
		$this->assertStringContainsString( "DESCRIPTION:Test excerpt\r\n", $ical );
		$this->assertStringContainsString( "UID:{$event_id}\r\n", $ical );
		$this->assertStringContainsString( 'URL:' . get_permalink( $event_id ), $ical );
		$this->assertStringNotContainsString( 'Long ago', $ical );
	}

	public function test_event_times_use_the_site_timezone(): void {
		update_option( 'timezone_string', 'Europe/Stockholm' );

		$this->create_event( [ 'start' => '2030-06-01 10:00', 'end' => '2030-06-01 12:30' ] );

		$ical = $this->create_feed( $this->feed()->from( '2030-05-01' )->to( '2030-07-01' ) );

		$this->assertStringContainsString( 'DTSTART;TZID=Europe/Stockholm:20300601T100000', $ical );
		$this->assertStringContainsString( 'DTEND;TZID=Europe/Stockholm:20300601T123000', $ical );
	}

	public function test_all_day_events_are_full_day(): void {
		$this->create_event( [ 'EventAllDay' => 'yes', 'start' => '2030-06-01', 'end' => '2030-06-01' ] );

		$ical = $this->create_feed( $this->feed()->from( '2030-05-01' )->to( '2030-07-01' ) );

		$this->assertMatchesRegularExpression( '/DTSTART;[^\r\n]*VALUE=DATE:20300601\r\n/', $ical );
		$this->assertStringContainsString( "X-MICROSOFT-CDO-ALLDAYEVENT:TRUE\r\n", $ical );
	}

	public function test_edited_events_look_revised(): void {
		$event_id = $this->create_event();

		$created = get_post_time( 'U', true, $event_id );

		// Edited an hour after it was created. wp_update_post() would use the current time.
		global $wpdb;
		$wpdb->update( $wpdb->posts, [
			'post_modified'     => get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $created + 3600 ) ),
			'post_modified_gmt' => gmdate( 'Y-m-d H:i:s', $created + 3600 ),
		], [ 'ID' => $event_id ] );
		clean_post_cache( $event_id );

		$ical    = $this->create_feed( $this->feed() );
		$revised = gmdate( 'Ymd\THis\Z', $created + 3600 );

		$this->assertStringContainsString( "DTSTAMP:{$revised}\r\n", $ical );
		$this->assertStringContainsString( "LAST-MODIFIED:{$revised}\r\n", $ical );
		$this->assertStringContainsString( "SEQUENCE:3600\r\n", $ical );
	}

	public function test_venue_is_the_location(): void {
		$venue_id = tribe_create_venue( [
			'Venue'   => 'Concert Hall',
			'Address' => 'Main Street 1',
			'City'    => 'Stockholm',
		] );

		$this->create_event( [ 'EventVenueID' => $venue_id ] );

		$ical = $this->create_feed( $this->feed() );

		$this->assertMatchesRegularExpression( '/LOCATION:Concert Hall.*Stockholm\r\n/', $ical );
	}

	public function test_feed_for_a_venue(): void {
		$venue_id = tribe_create_venue( [ 'Venue' => 'Concert Hall' ] );

		$this->create_event( [ 'post_title' => 'At the hall', 'EventVenueID' => $venue_id ] );
		$this->create_event( [ 'post_title' => 'Elsewhere' ] );

		$feed = $this->feed()->venue( $venue_id );
		$ical = $this->create_feed( $feed );

		$this->assertSame( 'Concert Hall in Test Site', $feed->get_calendar_name() );
		$this->assertStringContainsString( 'SUMMARY:At the hall', $ical );
		$this->assertStringNotContainsString( 'SUMMARY:Elsewhere', $ical );
	}

	public function test_feed_for_an_organizer(): void {
		$organizer_id = tribe_create_organizer( [ 'Organizer' => 'Choir' ] );

		$this->create_event( [ 'post_title' => 'Choir night', 'EventOrganizerID' => $organizer_id ] );
		$this->create_event( [ 'post_title' => 'Elsewhere' ] );

		$feed = $this->feed()->organizer( $organizer_id );
		$ical = $this->create_feed( $feed );

		$this->assertSame( 'Choir for Test Site', $feed->get_calendar_name() );
		$this->assertStringContainsString( 'SUMMARY:Choir night', $ical );
		$this->assertStringNotContainsString( 'SUMMARY:Elsewhere', $ical );
	}

	public function test_feed_for_categories(): void {
		$concerts = self::factory()->term->create( [ 'taxonomy' => 'tribe_events_cat', 'name' => 'Concerts' ] );
		$talks    = self::factory()->term->create( [ 'taxonomy' => 'tribe_events_cat', 'name' => 'Talks' ] );

		$this->create_event( [ 'post_title' => 'A concert', 'terms' => [ 'tribe_events_cat' => [ $concerts ] ] ] );
		$this->create_event( [ 'post_title' => 'A talk', 'terms' => [ 'tribe_events_cat' => [ $talks ] ] ] );
		$this->create_event( [ 'post_title' => 'Uncategorized' ] );

		$feed = $this->feed()->categories( [ $concerts ] );
		$ical = $this->create_feed( $feed );

		$this->assertSame( 'Concerts at Test Site', $feed->get_calendar_name() );
		$this->assertStringContainsString( 'SUMMARY:A concert', $ical );
		$this->assertStringNotContainsString( 'SUMMARY:A talk', $ical );
		$this->assertStringNotContainsString( 'SUMMARY:Uncategorized', $ical );

		// The feed URL passes IDs as strings from the query string.
		$feed = $this->feed()->categories( [ (string) $concerts, (string) $talks ] );
		$ical = $this->create_feed( $feed );

		$this->assertSame( 'Test Site', $feed->get_calendar_name() );
		$this->assertStringContainsString( 'SUMMARY:A concert', $ical );
		$this->assertStringContainsString( 'SUMMARY:A talk', $ical );
		$this->assertStringNotContainsString( 'SUMMARY:Uncategorized', $ical );
	}

	public function test_feed_for_a_tag(): void {
		$tag = self::factory()->tag->create( [ 'name' => 'Free' ] );

		$this->create_event( [ 'post_title' => 'Free event', 'terms' => [ 'post_tag' => [ $tag ] ] ] );
		$this->create_event( [ 'post_title' => 'Paid event' ] );

		$feed = $this->feed()->tags( [ $tag ] );
		$ical = $this->create_feed( $feed );

		$this->assertSame( 'Free at Test Site', $feed->get_calendar_name() );
		$this->assertStringContainsString( 'SUMMARY:Free event', $ical );
		$this->assertStringNotContainsString( 'SUMMARY:Paid event', $ical );
	}

	public function test_amount_limits_the_events(): void {
		for ( $i = 1; $i <= 3; $i++ ) {
			$this->create_event( [ 'post_title' => "Event {$i}", 'start' => "+{$i} days 10:00", 'end' => "+{$i} days 11:00" ] );
		}

		$ical = $this->create_feed( $this->feed()->amount( 2 ) );

		$this->assertSame( 2, substr_count( $ical, 'BEGIN:VEVENT' ) );
	}

	public function test_amount_is_capped(): void {
		$this->assertSame( Calendar_Feed::MAX_AMOUNT_OF_EVENTS, ( new Calendar_Feed() )->amount( 10000 )->amount );

		add_filter( 'events_calendar_ical_feeds_max_amount', static fn() => 50 );

		$this->assertSame( 50, ( new Calendar_Feed() )->amount( 10000 )->amount );
		$this->assertSame( 20, ( new Calendar_Feed() )->amount( 20 )->amount );
	}

	public function test_calendar_and_events_can_be_changed_by_actions(): void {
		$event_id = $this->create_event();
		$seen     = null;

		add_action( 'events_calendar_ical_create_calendar', static function ( Calendar $calendar ) {
			$calendar->name( 'Renamed' );
		} );

		add_action( 'events_calendar_ical_event_from_id', static function ( Event $event, int $id ) use ( &$seen ) {
			$seen = $id;
			$event->name( 'Changed title' );
		}, 10, 2 );

		$ical = $this->create_feed( $this->feed() );

		$this->assertSame( $event_id, $seen );
		$this->assertStringContainsString( "NAME:Renamed\r\n", $ical );
		$this->assertStringContainsString( "SUMMARY:Changed title\r\n", $ical );
	}

}

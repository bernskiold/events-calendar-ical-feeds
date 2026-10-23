<?php

namespace BernskioldMedia\WP\EventsCalendarIcalFeeds;


use DateTime;
use DateTimeZone;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\Properties\DateTimeProperty;
use Spatie\IcalendarGenerator\Properties\TextProperty;
use WP_Query;

/**
 * Class Calendar_Feed
 *
 * @package BernskioldMedia\WP\EventsCalendarIcalFeeds
 */
class Calendar_Feed {

	public int $amount = 100;

	public ?string $start_date   = null;
	public ?string $end_date     = null;
	public ?string $event_date   = null;
	public ?int    $venue_id     = null;
	public ?int    $organizer_id = null;
	public array   $categories   = [];
	public array   $tags   = [];

	public const REFRESH_INTERVAL_IN_MINUTES = 60;
	public const MAX_AMOUNT_OF_EVENTS        = 500;

	public function create(): string {
		$calendar = Calendar::create()
		                    ->name( $this->get_calendar_name() )
		                    ->refreshInterval( self::REFRESH_INTERVAL_IN_MINUTES )
		                    ->productIdentifier( get_bloginfo( 'name' ) )
		                    ->description( sprintf( __( 'Events from %s', 'events-calendar-ical-feeds' ), get_bloginfo( 'name' ) ) );

		foreach ( $this->get_query()->get_posts() as $post_id ) {
			$calendar->event( self::event_from_id( $post_id ) );
		}

		/**
		 * Allow folks to modify the iCal Calendar object before it's returned.
		 *
		 * @param Spatie\IcalendarGenerator\Components\Calendar $calendar The iCal Calendar object that can be modified (passed by reference).
		 */
		do_action_ref_array( 'events_calendar_ical_create_calendar', [ &$calendar ] );

		return $calendar->get();
	}

	protected function get_query(): WP_Query {
		$query = tribe_get_events( $this->get_filters(), true );
		$query->set( 'fields', 'ids' );

		if ( ! empty( $this->categories ) ) {
			$query->set( 'tax_query', [
				[
					'taxonomy' => 'tribe_events_cat',
					'field'    => 'term_id',
					'terms'    => $this->categories,
				],
			] );
		}

		if ( ! empty( $this->tags ) ) {
			$query->set( 'tax_query', [
				[
					'taxonomy' => 'post_tag',
					'field'    => 'term_id',
					'terms'    => $this->tags,
				],
			] );
		}

		$query = apply_filters( 'events_calendar_ical_query', $query );

		return $query;
	}

	public function from( string $start_date ): self {
		$this->start_date = $start_date;

		return $this;
	}

	public function to( string $end_date ): self {
		$this->end_date = $end_date;

		return $this;
	}

	public function on( string $event_date ): self {
		$this->event_date = $event_date;

		return $this;
	}

	public function venue( int $venue_id ): self {
		$this->venue_id = $venue_id;

		return $this;
	}

	public function organizer( int $organizer_id ): self {
		$this->organizer_id = $organizer_id;

		return $this;
	}

	public function categories( array $categories = [] ): self {
		$this->categories = $categories;

		return $this;
	}

	public function tags( array $tags = [] ): self {
		$this->tags = $tags;

		return $this;
	}

	public function amount( int $amount ): self {
		$max_amount = (int) apply_filters( 'events_calendar_ical_feeds_max_amount', self::MAX_AMOUNT_OF_EVENTS, $this );

		if ( $amount > $max_amount ) {
			$amount = $max_amount;
		}

		$this->amount = $amount;

		return $this;
	}

	protected function get_filters(): array {
		$filters = [];

		if ( $this->start_date ) {
			$filters['start_date'] = $this->start_date;
		}

		if ( $this->end_date ) {
			$filters['end_date'] = $this->end_date;
		}

		if ( $this->event_date ) {
			$filters['event_date'] = $this->event_date;
		}

		if ( $this->venue_id ) {
			$filters['venue'] = $this->venue_id;
		}

		if ( $this->organizer_id ) {
			$filters['organizer'] = $this->organizer_id;
		}

		$filters['posts_per_page'] = $this->amount;

		return $filters;
	}

	public function get_calendar_name(): string {
		if ( $this->venue_id ) {
			return sprintf( _x( '%2$s in %1$s', 'VenueName in SiteName', 'events-calendar-ical-feeds' ), get_bloginfo( 'name' ), get_the_title( $this->venue_id ) );
		}

		if ( $this->organizer_id ) {
			return sprintf( _x( '%2$s for %1$s', 'OrganizerName for SiteName', 'events-calendar-ical-feeds' ), get_bloginfo( 'name' ), get_the_title( $this->organizer_id ) );
		}

		if ( ! empty( $this->categories ) && count( $this->categories ) === 1 ) {
			return sprintf( _x( '%2$s at %1$s', 'CategoryName at SiteName', 'events-calendar-ical-feeds' ), get_bloginfo( 'name' ),
				get_term_field( 'name', $this->categories[0] ) );
		}

		if ( ! empty( $this->tags ) && count( $this->tags ) === 1 ) {
			return sprintf( _x( '%2$s at %1$s', 'TagName at SiteName', 'events-calendar-ical-feeds' ), get_bloginfo( 'name' ),
				get_term_field( 'name', $this->tags[0] ) );
		}

		return get_bloginfo( 'name' );
	}

	protected static function event_from_id( int $event_id ): Event {
		$tribe_event = tribe_get_event( $event_id );
		$venue       = tribe_get_venue( $event_id );
		$start_time  = tribe_get_start_date( $event_id, true, 'Y-m-d H:i:s' );
		$end_time    = tribe_get_end_date( $event_id, true, 'Y-m-d H:i:s' );
		$created     = (int) get_post_time( 'U', true, $tribe_event );
		$modified    = (int) get_post_modified_time( 'U', true, $tribe_event );
		$revised     = self::make_utc_date( max( $created, $modified ) );

		/*
		 * Calendar apps only replace an event they already have when it looks
		 * revised. In a published feed DTSTAMP is when the event was last
		 * changed, LAST-MODIFIED says the same and SEQUENCE grows with every
		 * edit. With DTSTAMP set to the creation date, edits never reached
		 * subscribers while new events did.
		 */
		$event = Event::create()
		              ->name( $tribe_event->post_title )
		              ->description( $tribe_event->post_excerpt )
		              ->uniqueIdentifier( $event_id )
		              ->createdAt( $revised )
		              ->startsAt( self::make_timezone_date( $start_time ) )
		              ->url( get_permalink( $event_id ) );

		$event->appendProperty( DateTimeProperty::fromDateTime( 'LAST-MODIFIED', $revised, true ) );
		$event->appendProperty( TextProperty::create( 'SEQUENCE', (string) max( 0, $modified - $created ) ) );

		if ( $end_time ) {
			$event->endsAt( self::make_timezone_date( $end_time ) );
		}

		if ( $venue ) {
			$event->address( tribe_get_venue_single_line_address( $event_id, false ) );
			$event->addressName( $venue );
		}

		if ( tribe_event_is_all_day( $event_id ) ) {
			$event->fullDay();
		}

		/**
		 * Allow folks to modify the event output to add extra parameters.
		 *
		 * @param Spatie\IcalendarGenerator\Components\Event $event    The iCal event object that can be modified (passed by reference).
		 * @param int                                        $event_id The ID of the event we're working with.
		 */
		do_action_ref_array( 'events_calendar_ical_event_from_id', [ &$event, $event_id ] );

		return $event;
	}

	protected static function make_timezone_date( ?string $date_string = null ): DateTime {
		return new DateTime( $date_string, new DateTimeZone( wp_timezone_string() ) );
	}

	protected static function make_utc_date( int $timestamp ): DateTime {
		return ( new DateTime( '@' . $timestamp ) )->setTimezone( new DateTimeZone( 'UTC' ) );
	}

}

<?php

namespace BernskioldMedia\WP\EventsCalendarIcalFeeds\Tests;

use WP_UnitTestCase;

abstract class TestCase extends WP_UnitTestCase {

	/**
	 * Creates a published event through The Events Calendar's API so its meta
	 * and custom tables are filled in the same way as in the admin.
	 *
	 * @param array<string, mixed> $args Overrides for tribe_create_event(),
	 *                                   plus `start` and `end` as strtotime() strings
	 *                                   and `terms` as taxonomy => term IDs.
	 */
	protected function create_event( array $args = [] ): int {
		$start = $args['start'] ?? '+3 days 10:00';
		$end   = $args['end'] ?? '+3 days 12:00';
		$terms = $args['terms'] ?? [];
		unset( $args['start'], $args['end'], $args['terms'] );

		$start_time = strtotime( $start );
		$end_time   = strtotime( $end );

		$event_id = tribe_create_event( array_merge( [
			'post_title'       => 'Test event',
			'post_excerpt'     => 'Test excerpt',
			'post_status'      => 'publish',
			'EventStartDate'   => gmdate( 'Y-m-d', $start_time ),
			'EventStartHour'   => gmdate( 'H', $start_time ),
			'EventStartMinute' => gmdate( 'i', $start_time ),
			'EventEndDate'     => gmdate( 'Y-m-d', $end_time ),
			'EventEndHour'     => gmdate( 'H', $end_time ),
			'EventEndMinute'   => gmdate( 'i', $end_time ),
		], $args ) );

		$this->assertIsInt( $event_id );

		// tax_input needs a user who can assign terms, so set them directly.
		foreach ( $terms as $taxonomy => $term_ids ) {
			wp_set_object_terms( $event_id, $term_ids, $taxonomy );
		}

		$this->commit_custom_tables();

		return $event_id;
	}

	/**
	 * The Events Calendar writes its custom tables rows (the occurrences that
	 * event queries join on) on shutdown, which never runs in a test.
	 */
	protected function commit_custom_tables(): void {
		$controller = 'TEC\\Events\\Custom_Tables\\V1\\Updates\\Controller';

		if ( class_exists( $controller ) ) {
			tribe( $controller )->commit_updates();
		}
	}

}

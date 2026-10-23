<?php

namespace BernskioldMedia\WP\EventsCalendarIcalFeeds;


use WP_Term;

/**
 * Class Calendar_Subscribe_Button
 *
 * @package BernskioldMedia\WP\EventsCalendarIcalFeeds
 */
class Calendar_Subscribe_Button {

	public static function hooks(): void {
		add_filter( 'tribe_template_html:events/v2/components/ical-link', [ self::class, 'render' ], 10, 4 );
	}

	/**
	 * Replaces The Events Calendar's subscribe links with ours.
	 *
	 * @param string $html     The original HTML.
	 * @param string $file     The template file.
	 * @param array  $name     The template name.
	 * @param object $template The Events Calendar template, which knows the view being rendered.
	 */
	public static function render( $html, $file = null, $name = null, $template = null ): string {
		$term = self::get_filtered_term( $template );

		$feeds = [
			[
				'title' => $term ? __( 'All events', 'events-calendar-ical-feeds' ) : '',
				'url'   => Endpoints::get_feed_url(),
				'name'  => get_bloginfo( 'name' ),
			],
		];

		if ( $term ) {
			$is_tag = 'post_tag' === $term->taxonomy;

			$feeds[] = [
				/* translators: %s: Category or tag name. */
				'title' => sprintf( $is_tag ? __( 'Only events tagged %s', 'events-calendar-ical-feeds' ) : __( 'Only events in %s', 'events-calendar-ical-feeds' ), $term->name ),
				'url'   => Endpoints::get_prefiltered_feed_url( $is_tag ? null : $term->term_id, $is_tag ? $term->term_id : null ),
				/* translators: 1: Site name. 2: Category or tag name. */
				'name'  => sprintf( _x( '%2$s at %1$s', 'CategoryName at SiteName', 'events-calendar-ical-feeds' ), get_bloginfo( 'name' ), $term->name ),
			];
		}

		ob_start();
		include Plugin::get_path( 'views/subscribe-button.php' );

		return ob_get_clean();
	}

	/**
	 * One-click subscribe links for the common calendar apps.
	 *
	 * @return array<string, array{label: string, title: string, url: string}>
	 */
	public static function get_app_links( string $feed_url, string $name ): array {
		$webcal_url = preg_replace( '#^https?://#', 'webcal://', $feed_url );

		$links = [
			'apple'     => [
				'label' => __( 'iPhone/Mac', 'events-calendar-ical-feeds' ),
				'title' => __( 'Apple Calendar (iPhone, iPad, Mac)', 'events-calendar-ical-feeds' ),
				'url'   => $webcal_url,
			],
			'google'    => [
				'label' => __( 'Google', 'events-calendar-ical-feeds' ),
				'title' => __( 'Google Calendar', 'events-calendar-ical-feeds' ),
				'url'   => 'https://calendar.google.com/calendar/render?cid=' . rawurlencode( $webcal_url ),
			],
			'outlook'   => [
				'label' => __( 'Outlook.com', 'events-calendar-ical-feeds' ),
				'title' => __( 'Outlook.com', 'events-calendar-ical-feeds' ),
				'url'   => self::get_outlook_url( 'live', $webcal_url, $name ),
			],
			'microsoft' => [
				'label' => __( 'Microsoft 365', 'events-calendar-ical-feeds' ),
				'title' => __( 'Outlook for work or school (Microsoft 365)', 'events-calendar-ical-feeds' ),
				'url'   => self::get_outlook_url( 'office', $webcal_url, $name ),
			],
		];

		return apply_filters( 'events_calendar_ical_feeds_app_links', $links, $feed_url, $name );
	}

	protected static function get_outlook_url( string $domain, string $webcal_url, string $name ): string {
		return 'https://outlook.' . $domain . '.com/owa?path=/calendar/action/compose&rru=addsubscription&url=' . rawurlencode( $webcal_url ) . '&name=' . rawurlencode( $name );
	}

	/**
	 * The category or tag the calendar is filtered on.
	 *
	 * The view knows this in every calendar view, also when it's loaded over
	 * REST as the visitor navigates. is_tax() only works on a full page load
	 * and not at all in month view.
	 */
	protected static function get_filtered_term( $template = null ): ?WP_Term {
		$args = [];

		if ( is_object( $template ) && method_exists( $template, 'get_view' ) ) {
			$view = $template->get_view();

			if ( is_object( $view ) && method_exists( $view, 'get_url_args' ) ) {
				$args = (array) $view->get_url_args();
			}
		}

		foreach ( [ 'tribe_events_cat' => 'tribe_events_cat', 'tag' => 'post_tag', 'post_tag' => 'post_tag' ] as $arg => $taxonomy ) {
			if ( empty( $args[ $arg ] ) ) {
				continue;
			}

			$slug = is_array( $args[ $arg ] ) ? reset( $args[ $arg ] ) : $args[ $arg ];
			$term = get_term_by( 'slug', (string) $slug, $taxonomy );

			if ( $term instanceof WP_Term ) {
				return $term;
			}
		}

		if ( is_tax( 'tribe_events_cat' ) || is_tag() ) {
			$term = get_queried_object();

			if ( $term instanceof WP_Term ) {
				return $term;
			}
		}

		return null;
	}

}

<?php

namespace BernskioldMedia\WP\EventsCalendarIcalFeeds;


use ECIF_Vendor\BernskioldMedia\WP\PluginBase\Interfaces\Hookable;

class Endpoints implements Hookable {

	public static function hooks(): void {
		add_action( 'init', [ self::class, 'rules' ] );
		add_filter( 'query_vars', [ self::class, 'query_vars' ] );
		add_action( 'template_include', [ self::class, 'router' ] );
	}

	protected static function get_events_slug(): string{

		if( function_exists( 'tribe_get_option' ) ) {

			$slug      = tribe_get_option( 'eventsSlug' );

			if ( empty( $slug ) ) {
				$slug = 'calendar';
			}

		} else {
			$slug = 'calendar';
		}

		return $slug;
	}

	protected static function get_feed_slug(): string {
		return apply_filters( 'events_calendar_ical_feeds_feed_slug', 'ical-feed' );
	}

	public static function rules(): void {
		add_rewrite_rule( '^' . self::get_events_slug() . '/' . self::get_feed_slug() . '[/]?$', 'index.php?calendar_feed=true', 'top' );
	}

	public static function query_vars( array $query_vars ): array {
		$query_vars[] = 'calendar_feed';

		return $query_vars;
	}

	public static function router( string $template ): string {
		if ( get_query_var( 'calendar_feed' ) === 'true' ) {
			return Plugin::get_path( 'views/ical-feed.php' );
		}

		return $template;
	}

	public static function get_feed_url(): string {
		return home_url( self::get_events_slug().'/'.self::get_feed_slug() );
	}

	/**
	 * The feed URL limited to a category or tag. Without arguments it uses
	 * the archive being viewed, which isn't known in every calendar view
	 * (e.g. month view, or after the calendar navigates), so callers that
	 * know better should pass the term IDs.
	 */
	public static function get_prefiltered_feed_url( ?int $category_id = null, ?int $tag_id = null ): string {

		$url = self::get_feed_url();

		if ( null === $category_id && is_tax( 'tribe_events_cat' ) ) {
			$category_id = get_queried_object_id();
		}

		if ( null === $tag_id && is_tag() ) {
			$tag_id = get_queried_object_id();
		}

		if ( $category_id ) {
			$url = add_query_arg( 'categories', $category_id, $url );
		} elseif ( $tag_id ) {
			$url = add_query_arg( 'tags', $tag_id, $url );
		}

		$url = apply_filters( 'events_calendar_ical_feeds_prefiltered_url', $url );

		return esc_url_raw( $url );
	}

}

<?php

namespace BernskioldMedia\WP\EventsCalendarIcalFeeds;

defined( 'ABSPATH' ) || exit;

class Plugin {

	public const TEXTDOMAIN = 'events-calendar-ical-feeds';

	/**
	 * Classes with a static hooks() method to boot on load.
	 */
	private const BOOT = [
		Endpoints::class,
		Calendar_Subscribe_Button::class,
	];

	protected static string $version = '2.0.1';

	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function __construct() {
		$this->init_hooks();

		do_action( 'events_calendar_ical_feeds_loaded' );
	}

	protected function init_hooks(): void {
		do_action( 'events_calendar_ical_feeds_init_hooks' );

		add_action( 'init', [ self::class, 'load_languages' ] );
		add_action( 'wp_enqueue_scripts', [ self::class, 'assets' ] );

		foreach ( self::BOOT as $class ) {
			$class::hooks();
		}
	}

	/**
	 * Whether The Events Calendar is loaded. Checked on plugins_loaded, so it
	 * also works when The Events Calendar is network activated or loaded as a
	 * must-use plugin.
	 */
	public static function has_dependencies(): bool {
		return class_exists( 'Tribe__Events__Main' );
	}

	public static function get_path( string $file = '' ): string {
		return untrailingslashit( plugin_dir_path( EVENTS_CALENDAR_ICAL_FEEDS_FILE_PATH ) ) . '/' . $file;
	}

	public static function get_url( string $file = '' ): string {
		return untrailingslashit( plugin_dir_url( EVENTS_CALENDAR_ICAL_FEEDS_FILE_PATH ) ) . '/' . $file;
	}

	public static function get_version(): string {
		return static::$version;
	}

	public static function get_textdomain(): string {
		return self::TEXTDOMAIN;
	}

	public static function load_languages(): void {
		$textdomain = self::TEXTDOMAIN;
		$locale     = is_admin() ? get_user_locale() : get_locale();
		$locale     = apply_filters( 'plugin_locale', $locale, $textdomain );

		unload_textdomain( $textdomain );
		// Start checking in the main language dir, then fall back to the plugin.
		load_textdomain( $textdomain, WP_LANG_DIR . "/{$textdomain}/{$textdomain}-{$locale}.mo" );
		load_plugin_textdomain( $textdomain, false, dirname( plugin_basename( EVENTS_CALENDAR_ICAL_FEEDS_FILE_PATH ) ) . '/languages' );
	}

	public static function assets(): void {
		// Give user the option of disabling assets.
		if ( true !== apply_filters( 'events_calendar_ical_load_assets', true ) ) {
			return;
		}

		$main = self::get_asset( 'ical-feed' );

		wp_register_script( 'ecif-main', self::get_url( 'dist/ical-feed.js' ), $main['dependencies'], $main['version'], [
			'in_footer' => true,
			'strategy'  => 'defer',
		] );
		wp_register_style( 'ecif-main', self::get_url( 'dist/ical-feed.css' ), [], $main['version'], 'screen' );
		wp_style_add_data( 'ecif-main', 'rtl', 'replace' );

		// Only enqueue where we need it.
		if ( is_post_type_archive( 'tribe_events' ) || is_singular( 'tribe_events' ) || in_array( get_post_type(), [ 'tribe_organizer', 'tribe_venue' ], true ) ) {
			wp_enqueue_script( 'ecif-main' );
			wp_enqueue_style( 'ecif-main' );
		}
	}

	/**
	 * The dependencies and version that @wordpress/scripts generates for a
	 * built entry in dist/.
	 *
	 * @return array{dependencies: string[], version: string}
	 */
	protected static function get_asset( string $name ): array {
		$file  = self::get_path( "dist/{$name}.asset.php" );
		$asset = file_exists( $file ) ? require $file : [];

		return [
			'dependencies' => $asset['dependencies'] ?? [],
			'version'      => $asset['version'] ?? self::get_version(),
		];
	}

}

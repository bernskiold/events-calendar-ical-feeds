# iCalendar Feeds for The Events Calendar

![License](https://img.shields.io/github/license/bernskioldmedia/events-calendar-ical-feeds) ![Downloads](https://img.shields.io/github/downloads/bernskioldmedia/events-calendar-ical-feeds/total)

Extends The Events Calendar with iCal feeds that users can subscribe to in their favorite calendar application. By default, in The Events Calendar, users can export an iCalendar
feed, but not subscribe to it.

We believe the use for a calendar export is to subscribe so that updates properly reflect. Therefore, this plugin adds a subscribable, auto-updating feed.

## Requirements

- WordPress 6.5 or later
- PHP 8.1 or later
- [The Events Calendar](https://wordpress.org/plugins/the-events-calendar/)

## Installation

You can install this package via composer.

```bash
composer require bernskioldmedia/events-calendar-ical-feeds
```

You can also install this package by grabbing the built ZIP file from the [releases page](https://github.com/bernskioldmedia/events-calendar-ical-feeds/releases) and uploading it to your plugins folder.

The plugin will update through the core WordPress plugin updater.

## Usage

The feed URL is based on the events slug in the settings of The Event Calendar.

For the default slug `events`, the feed URL will be: `https://yourdomain.com/events/ical-feed`

_Please note that the default feed is `/ical` while this one is at `/ical-feed`._

### Query Parameters

You can customize which events show up in a feed through the available query parameters.

### Setting a start date.

Set a custom start date from when to start fetching events. By default, this is rolling 30 days before the current day.

`from=2021-01-01`

### Setting an end date.

Set a custom end date until when to fetch events. By default, this is rolling six months after the current day.

`to=2021-12-31`

### Showing events for a specific venue

You can create a venue-specific calendar feed by adding the venue parameter and passing the venue ID.

`venue=3`

### Showing events for a specific organizer

You can create an organizer-specific calendar feed by adding the organizer parameter and passing the organizer ID.

`organizer=3`

### Showing events for one or many event categories

You can create an event category-specific calendar feed by adding the category parameter and passing one or more event category IDs, comma-separated.

`categories=28` or `categories=25,28,93`

### Controlling how many events to include

You can customize the number of events to show in the calendar. By default, this is 100.

There is an upper limit for performance reasons and to stop anyone from abusing and sinking your server. By default, this is 500 but can be customized in a filter (see below).

`amount=250`

## Filters & Actions

We make a few filters and actions available for customizing and extending.

### Controlling the max amount of events in the feed

Define how many items can be at most be included in the feed. No more items will be allowed, even if set via the filters. Defaults to `500`.

```php
// Allow 1000 events to be loaded into the iCal feed.
add_filter( 'events_calendar_ical_feeds_max_amount', function(int $max_events, \BernskioldMedia\WP\EventsCalendarIcalFeeds\Calendar_Feed $feed) {
	return 1000;
} );
```

### Customizing the default start date

Define how long before the current dates we should fetch events for the feeds. By default, this is `-30 days` and means that events in the past 30 days will show up in the
calendar.

Be mindful of this, as a larger timespan will mean a bigger feed, which has performance implications.

Return any valuable compatible with `DateTime`.

```php
// Including events starting 45 days from any given day.
add_filter( 'events_calendar_ical_feeds_default_start_date', function( string $start_date ) {
	return '-45 days';
});
```

### Customizing the default end date

Define how long after the current dates we should fetch events for the feeds. By default, this is to `+6 months` and means that events in the next six months will appear in the
calendar.

Be mindful of this, as a larger timespan will mean a bigger feed, which has performance implications.

Return any valuable compatible with `DateTime`.

```php
// Including events rolling one year from today.
add_filter( 'events_calendar_ical_feeds_default_end_date', function( string $end_date ) {
	return '+1 year';
});
```

### Customizing the default feed slug

If you don't like the `/ical-feed` suffix of the events slug, you can customize it through this filter. Return any one directory level string.

Please note that returning `/ical` here will clash with the built-in feed in The Events Calendar.

_After changing, don't forget to refresh your permalinks._

```php
// Making the URL /events/subscribe.
add_filter( 'events_calendar_ical_feeds_feed_slug', function() {
	return 'subscribe';
});
```

### Customizing the calendar feed

You can customize the calendar feed before output if you want to add/change/remove anything that this plugin does by default. Please see [spatie/icalendar-generator](https://github.com/spatie/icalendar-generator) for how to interact with the calendar object.

```php
// Customize the calendar feed.
add_action( 'events_calendar_ical_create_calendar', function( \Spatie\IcalendarGenerator\Components\Calendar $calendar ) {
	// $calendar->...
});
```

### Customizing the event object

You can customize the event object before it is added to the calendar feed if you want to add/change/remove anything that this plugin does by default. Please see [spatie/icalendar-generator](https://github.com/spatie/icalendar-generator) for how to interact with the event object.

This can be used for example to customize the data or to add additional metadata based on the event ID.

```php
// Customize the calendar feed.
add_action( 'events_calendar_ical_event_from_id', function( \Spatie\IcalendarGenerator\Components\Event $event, int $event_id ) {
	// $event->...
});
```

### Preventing asset loading

If you don't want this plugin to load its own assets (see below), you can prevent it by returning false from this filter. It returns true by default.

```php
// Prevent Events Calendar iCal Feeds from loading assets.
add_action( 'events_calendar_ical_load_assets', '__return_false' );
```

## Assets

This plugin requires [Alpine.js](https://alpinejs.dev) for a few frontend operations. Because Alpine.js is something that you might already be running, or have across many projects, we try to be nice when checking for it.

We load it by checking if the handle `alpinejs` is already registered. If it is, we won't touch it. Just enqueue it. If it isn't, we'll register and enqueue the copy bundled with the plugin.

To completely disable loading of assets, see "Preventing asset loading" above.

## Development

The front-end assets are built with [`@wordpress/scripts`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/). Sources live in `assets/`, and the build writes to `dist/`, which is committed.

```bash
npm run setup   # npm install, composer install and a first build
npm run start   # rebuild on change
npm run build   # production build
npm run lint:js
npm run lint:css
```

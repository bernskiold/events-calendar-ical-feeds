<?php
/**
 * Subscribe button
 *
 * This button replaces Events Calendar Ical Link Button
 *
 * @var array $feeds List of feeds to offer, each with a title, url and name.
 *                    With more than one, the last (most specific) is preselected.
 */

use BernskioldMedia\WP\EventsCalendarIcalFeeds\Calendar_Subscribe_Button;

$panel_id = wp_unique_id( 'ecif-subscription-' );
$selected = count( $feeds ) - 1;

?>
<div class="ecif-subscription tribe-events-c-ical tribe-common-b2 tribe-common-b3--min-medium">
	<button
		type="button"
		class="tribe-events-c-ical__link js-ecif-subscription-toggle"
		title="<?php echo esc_attr__( 'Subscribe to the calendar', 'events-calendar-ical-feeds' ); ?>"
		aria-expanded="false"
		aria-controls="<?php echo esc_attr( $panel_id ); ?>"
	>
		<?php echo esc_html__( 'Subscribe', 'events-calendar-ical-feeds' ); ?>
	</button>

	<div class="ecif-subscription-info" id="<?php echo esc_attr( $panel_id ); ?>" hidden>
		<p class="ecif-subscription-info__title"><?php esc_html_e( 'Subscribe to calendar', 'events-calendar-ical-feeds' ); ?></p>
		<p><?php esc_html_e( 'Choose your calendar app. New and changed events are then added to your calendar automatically.', 'events-calendar-ical-feeds' ); ?></p>

		<?php if ( count( $feeds ) > 1 ) : ?>
			<fieldset class="ecif-subscription-info__choice">
				<legend class="screen-reader-text"><?php esc_html_e( 'Which events?', 'events-calendar-ical-feeds' ); ?></legend>
				<?php foreach ( $feeds as $index => $feed ) : ?>
					<label>
						<input type="radio" class="js-ecif-subscription-feed" name="<?php echo esc_attr( $panel_id ); ?>-feed" value="<?php echo (int) $index; ?>" <?php checked( $selected, $index ); ?> />
						<?php echo esc_html( $feed['title'] ); ?>
					</label>
				<?php endforeach; ?>
			</fieldset>
		<?php endif; ?>

		<?php foreach ( $feeds as $index => $feed ) : ?>
			<div class="ecif-subscription-info__feed" data-feed="<?php echo (int) $index; ?>" <?php echo $selected === $index ? '' : 'hidden'; ?>>
				<ul class="ecif-subscription-info__apps">
					<?php foreach ( Calendar_Subscribe_Button::get_app_links( $feed['url'], $feed['name'] ) as $key => $link ) : ?>
						<li>
							<a class="ecif-subscription-info__app ecif-subscription-info__app--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $link['url'], [ 'http', 'https', 'webcal' ] ); ?>" title="<?php echo esc_attr( $link['title'] ); ?>" <?php echo 0 === strpos( $link['url'], 'webcal://' ) ? '' : 'target="_blank" rel="noopener"'; ?>>
								<?php echo esc_html( $link['label'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>

				<p class="ecif-subscription-info__other"><?php esc_html_e( 'Other calendar app? Copy the address and add it as a subscribed calendar:', 'events-calendar-ical-feeds' ); ?></p>
				<div class="ecif-subscription-info__actions">
					<input class="js-copy-tec-ical-feed-url ecif-subscription-info__url" readonly value="<?php echo esc_attr( $feed['url'] ); ?>" aria-label="<?php esc_attr_e( 'Calendar address', 'events-calendar-ical-feeds' ); ?>" />
					<button
						type="button"
						class="js-copy-tec-ical-feed-button ecif-subscription-info__copy"
						data-copied="<?php esc_attr_e( 'Copied!', 'events-calendar-ical-feeds' ); ?>"
						data-failed="<?php esc_attr_e( 'The address is selected. Copy it from the menu that appears.', 'events-calendar-ical-feeds' ); ?>"
					><?php esc_html_e( 'Copy', 'events-calendar-ical-feeds' ); ?></button>
				</div>
				<p class="ecif-subscription-info__copied js-copy-tec-ical-feed-result" aria-live="polite"></p>
			</div>
		<?php endforeach; ?>
	</div>

</div>

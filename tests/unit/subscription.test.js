/**
 * External dependencies
 */
import {
	afterEach,
	beforeAll,
	beforeEach,
	describe,
	expect,
	it,
	vi,
} from 'vitest';

/**
 * Internal dependencies
 */
import {
	closeFrom,
	copyFeed,
	listen,
	selectFeed,
	togglePanel,
} from '../../assets/scripts/subscription';

const feedMarkup = ( index, url, hidden ) => `
	<div class="ecif-subscription-info__feed" data-feed="${ index }" ${
		hidden ? 'hidden' : ''
	}>
		<input class="js-copy-tec-ical-feed-url" readonly value="${ url }" />
		<button type="button" class="js-copy-tec-ical-feed-button" data-copied="Copied!" data-failed="Copy it by hand.">Copy</button>
		<p class="js-copy-tec-ical-feed-result" aria-live="polite"></p>
	</div>`;

// The markup views/subscribe-button.php renders for a category archive.
const markup = `
	<div class="ecif-subscription">
		<button type="button" class="js-ecif-subscription-toggle" aria-expanded="false" aria-controls="panel">Subscribe</button>
		<div class="ecif-subscription-info" id="panel" hidden>
			<fieldset>
				<label><input type="radio" class="js-ecif-subscription-feed" name="feed" value="0" /> All events</label>
				<label><input type="radio" class="js-ecif-subscription-feed" name="feed" value="1" checked /> Only events in Concerts</label>
			</fieldset>
			${ feedMarkup( 0, 'https://example.org/events/ical-feed', true ) }
			${ feedMarkup( 1, 'https://example.org/events/ical-feed?categories=5', false ) }
		</div>
	</div>`;

const $ = ( selector ) => document.querySelector( selector );
const feed = ( index ) => $( `[data-feed="${ index }"]` );

beforeAll( () => {
	listen( document );
} );

beforeEach( () => {
	document.body.innerHTML = markup;
} );

describe( 'togglePanel', () => {
	it( 'opens and closes the panel', () => {
		const button = $( '.js-ecif-subscription-toggle' );

		togglePanel( button );
		expect( $( '#panel' ).hidden ).toBe( false );
		expect( button.getAttribute( 'aria-expanded' ) ).toBe( 'true' );

		togglePanel( button );
		expect( $( '#panel' ).hidden ).toBe( true );
		expect( button.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
	} );

	it( 'can be forced open or closed', () => {
		const button = $( '.js-ecif-subscription-toggle' );

		togglePanel( button, true );
		togglePanel( button, true );
		expect( $( '#panel' ).hidden ).toBe( false );

		togglePanel( button, false );
		expect( $( '#panel' ).hidden ).toBe( true );
	} );
} );

describe( 'selectFeed', () => {
	it( 'shows only the picked feed', () => {
		selectFeed( $( 'input[value="0"]' ) );

		expect( feed( 0 ).hidden ).toBe( false );
		expect( feed( 1 ).hidden ).toBe( true );
	} );
} );

describe( 'closeFrom', () => {
	it( 'closes the open panel and focuses the toggle', () => {
		const button = $( '.js-ecif-subscription-toggle' );
		togglePanel( button, true );

		expect( closeFrom( $( '.js-copy-tec-ical-feed-url' ) ) ).toBe( true );
		expect( $( '#panel' ).hidden ).toBe( true );
		expect( document.activeElement ).toBe( button );
	} );

	it( 'does nothing when the panel is closed or outside a panel', () => {
		expect( closeFrom( $( '.js-copy-tec-ical-feed-url' ) ) ).toBe( false );
		expect( closeFrom( document.body ) ).toBe( false );
	} );
} );

describe( 'copyFeed', () => {
	const originalClipboard = navigator.clipboard;
	const originalSecure = window.isSecureContext;

	afterEach( () => {
		Object.defineProperty( navigator, 'clipboard', {
			value: originalClipboard,
			configurable: true,
		} );
		Object.defineProperty( window, 'isSecureContext', {
			value: originalSecure,
			configurable: true,
		} );
		delete document.execCommand;
	} );

	it( 'copies the address of its own feed with the clipboard API', async () => {
		const writeText = vi.fn( () => Promise.resolve() );
		Object.defineProperty( navigator, 'clipboard', {
			value: { writeText },
			configurable: true,
		} );
		Object.defineProperty( window, 'isSecureContext', {
			value: true,
			configurable: true,
		} );

		const button = feed( 1 ).querySelector( 'button' );

		await expect( copyFeed( button ) ).resolves.toBe( true );
		expect( writeText ).toHaveBeenCalledWith(
			'https://example.org/events/ical-feed?categories=5'
		);

		const result = feed( 1 ).querySelector(
			'.js-copy-tec-ical-feed-result'
		);
		expect( result.textContent ).toBe( 'Copied!' );
		expect( result.classList.contains( 'is-active' ) ).toBe( true );
	} );

	it( 'falls back to execCommand outside a secure context', async () => {
		Object.defineProperty( window, 'isSecureContext', {
			value: false,
			configurable: true,
		} );
		document.execCommand = vi.fn( () => true );

		await expect(
			copyFeed( feed( 0 ).querySelector( 'button' ) )
		).resolves.toBe( true );
		expect( document.execCommand ).toHaveBeenCalledWith( 'copy' );
	} );

	it( 'says so and leaves the address selected when copying fails', async () => {
		Object.defineProperty( window, 'isSecureContext', {
			value: false,
			configurable: true,
		} );
		document.execCommand = vi.fn( () => false );

		const input = feed( 0 ).querySelector( 'input' );

		await expect(
			copyFeed( feed( 0 ).querySelector( 'button' ) )
		).resolves.toBe( false );
		expect( document.activeElement ).toBe( input );
		expect( input.selectionEnd ).toBe( input.value.length );
		expect(
			feed( 0 ).querySelector( '.js-copy-tec-ical-feed-result' )
				.textContent
		).toBe( 'Copy it by hand.' );
	} );
} );

describe( 'listen', () => {
	it( 'handles clicks, feed changes and Escape', () => {
		const button = $( '.js-ecif-subscription-toggle' );

		button.click();
		expect( $( '#panel' ).hidden ).toBe( false );

		const radio = $( 'input[value="0"]' );
		radio.checked = true;
		radio.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		expect( feed( 0 ).hidden ).toBe( false );
		expect( feed( 1 ).hidden ).toBe( true );

		radio.dispatchEvent(
			new window.KeyboardEvent( 'keydown', {
				key: 'Escape',
				bubbles: true,
			} )
		);
		expect( $( '#panel' ).hidden ).toBe( true );
	} );

	it( 'works for markup that replaces the calendar later', () => {
		// The Events Calendar swaps the whole view when navigating.
		document.body.innerHTML = markup.replace( /panel/g, 'panel-2' );

		$( '.js-ecif-subscription-toggle' ).click();

		expect( $( '#panel-2' ).hidden ).toBe( false );
	} );
} );

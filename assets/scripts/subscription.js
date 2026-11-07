/**
 * Subscribe button behaviour.
 *
 * The Events Calendar replaces the calendar markup whenever the visitor
 * changes month or view, so everything here works from the element an event
 * happened on instead of binding to the buttons on page load.
 */

const ROOT = '.ecif-subscription';
const PANEL = '.ecif-subscription-info';
const FEED = '.ecif-subscription-info__feed';

/**
 * Opens or closes the panel that a toggle button controls.
 *
 * @param {HTMLElement} button The toggle button.
 * @param {boolean}     [open] Whether to open. Toggles when left out.
 */
export function togglePanel( button, open ) {
	const root = button.closest( ROOT );
	const panel = root && root.querySelector( PANEL );

	if ( ! panel ) {
		return;
	}

	const isOpen = open === undefined ? panel.hidden : open;

	panel.hidden = ! isOpen;
	button.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
}

/**
 * Shows the feed that a radio button picks and hides the others.
 *
 * @param {HTMLInputElement} radio The selected radio button.
 */
export function selectFeed( radio ) {
	const root = radio.closest( ROOT );

	if ( ! root ) {
		return;
	}

	root.querySelectorAll( FEED ).forEach( ( feed ) => {
		feed.hidden = feed.dataset.feed !== radio.value;
	} );
}

/**
 * Closes the open panel that holds the focused element, and moves the focus
 * back to its toggle button.
 *
 * @param {Element} element The focused element.
 * @return {boolean} Whether a panel was closed.
 */
export function closeFrom( element ) {
	const root = element.closest( ROOT );
	const panel = root && root.querySelector( PANEL );
	const button = root && root.querySelector( '.js-ecif-subscription-toggle' );

	if ( ! panel || ! button || panel.hidden ) {
		return false;
	}

	togglePanel( button, false );
	button.focus();

	return true;
}

function selectInput( input ) {
	// iOS won't select text in a read-only field.
	const readOnly = input.readOnly;

	input.readOnly = false;
	input.focus();
	input.setSelectionRange( 0, input.value.length );
	input.readOnly = readOnly;
}

function fallbackCopy( input ) {
	selectInput( input );

	try {
		return document.execCommand( 'copy' );
	} catch {
		return false;
	}
}

/**
 * Copies the feed address next to a copy button and reports the result.
 *
 * @param {HTMLElement} button The copy button.
 * @return {Promise<boolean>} Whether the address was copied.
 */
export function copyFeed( button ) {
	const feed = button.closest( FEED );
	const input = feed && feed.querySelector( '.js-copy-tec-ical-feed-url' );
	const result =
		feed && feed.querySelector( '.js-copy-tec-ical-feed-result' );

	if ( ! input ) {
		return Promise.resolve( false );
	}

	const copied =
		navigator.clipboard && window.isSecureContext
			? navigator.clipboard.writeText( input.value ).then(
					() => true,
					() => fallbackCopy( input )
				)
			: Promise.resolve( fallbackCopy( input ) );

	return copied.then( ( success ) => {
		if ( ! success ) {
			// Leave the address selected so it can be copied by hand.
			selectInput( input );
		}

		if ( result ) {
			result.textContent = success
				? button.dataset.copied
				: button.dataset.failed;
			result.classList.add( 'is-active' );
		}

		return success;
	} );
}

/**
 * Listens on the document, so markup added later works too.
 *
 * @param {Document} doc The document to listen on.
 */
export function listen( doc = document ) {
	doc.addEventListener( 'click', ( event ) => {
		const toggle = event.target.closest( '.js-ecif-subscription-toggle' );

		if ( toggle ) {
			togglePanel( toggle );
			return;
		}

		const copy = event.target.closest( '.js-copy-tec-ical-feed-button' );

		if ( copy ) {
			copyFeed( copy );
		}
	} );

	doc.addEventListener( 'change', ( event ) => {
		if ( event.target.matches( '.js-ecif-subscription-feed' ) ) {
			selectFeed( event.target );
		}
	} );

	doc.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' && closeFrom( event.target ) ) {
			event.preventDefault();
		}
	} );
}

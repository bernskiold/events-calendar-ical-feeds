/**
 * Copy the feed address.
 *
 * The Events Calendar replaces the calendar markup whenever the visitor
 * changes month or view, so listen on the document instead of binding to
 * the buttons on page load.
 */

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
	} catch ( err ) {
		return false;
	}
}

function copyInput( input ) {
	if ( navigator.clipboard && window.isSecureContext ) {
		return navigator.clipboard.writeText( input.value ).then(
			() => true,
			() => fallbackCopy( input )
		);
	}

	return Promise.resolve( fallbackCopy( input ) );
}

document.addEventListener( 'click', function( event ) {
	const button = event.target.closest( '.js-copy-tec-ical-feed-button' );

	if ( ! button ) {
		return;
	}

	const feed = button.closest( '.ecif-subscription-info__feed' );
	const input = feed && feed.querySelector( '.js-copy-tec-ical-feed-url' );
	const result = feed && feed.querySelector( '.js-copy-tec-ical-feed-result' );

	if ( ! input ) {
		return;
	}

	copyInput( input ).then( function( copied ) {
		if ( ! copied ) {
			// Leave the address selected so it can be copied by hand.
			selectInput( input );
		}

		if ( result ) {
			result.textContent = copied ? button.dataset.copied : button.dataset.failed;
			result.classList.add( 'is-active' );
		}
	} );
} );

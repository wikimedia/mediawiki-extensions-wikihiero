$( () => {
	'use strict';

	/* eslint-disable no-jquery/no-global-selector */
	const textarea = OO.ui.infuse( $( '#hiero-text' ) ),
		submit = OO.ui.infuse( $( '#hiero-submit' ) ),
		$result = $( '#hiero-result' );
	/* eslint-enable no-jquery/no-global-selector */

	function onChange() {
		submit.setDisabled( !textarea.getValue() );
	}
	textarea.on( 'change', onChange );
	onChange();

	function onSubmit() {
		$result.css( 'opacity', 0.5 );
		const text = textarea.getValue();
		const data = {
			format: 'json',
			action: 'parse',
			text: '<hiero>' + text + '</hiero>',
			disablepp: ''
		};
		$.post( mw.util.wikiScript( 'api' ),
			data,
			( response ) => {
				const html = '<table class="wikitable">' +
					'<tr><th>' + mw.msg( 'wikihiero-input' ) + '</th><th>' +
					mw.msg( 'wikihiero-result' ) + '</th></tr>' +
					'<tr><td><code>&lt;hiero&gt;' +
					mw.html.escape( text ).replace( '\n', '<br/>' ) +
					'&lt;/hiero&gt;</code></td>' +
					'<td>' + response.parse.text[ '*' ] + '</td></tr></table>';
				$result.html( html );
			}
		).fail( () => {
			$result.text( mw.msg( 'wikihiero-load-error' ) );
		} ).always( () => {
			$result.css( 'opacity', 1 );
		} );
	}
	submit.on( 'click', onSubmit );

	// eslint-disable-next-line no-jquery/no-global-selector
	$( '.mw-hiero-toc a' ).on( 'click', ( e ) => {
		if ( e.target.hash ) {
			e.preventDefault();
			// eslint-disable-next-line no-jquery/no-global-selector
			$( window ).scrollTop( $( e.target.hash ).offset().top - $( '.mw-hiero-form' ).outerHeight() );
		}
	} );

	// eslint-disable-next-line no-jquery/no-global-selector
	$( '.mw-hiero-code' ).on( 'click', ( e ) => {
		const val = textarea.getValue().trim();
		textarea.setValue(
			val + ( val ? ' ' : '' ) + $( e.currentTarget ).find( '.mw-hiero-syntax' ).text()
		);
		textarea.moveCursorToEnd().focus();
		onSubmit();
	} );

} );

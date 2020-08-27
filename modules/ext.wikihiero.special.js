$( function () {
	'use strict';

	/* eslint-disable no-jquery/no-global-selector */
	var textarea = OO.ui.infuse( $( '#hiero-text' ) ),
		submit = OO.ui.infuse( $( '#hiero-submit' ) ),
		$result = $( '#hiero-result' );
	/* eslint-enable no-jquery/no-global-selector */

	function onChange() {
		submit.setDisabled( !textarea.getValue() );
	}
	textarea.on( 'change', onChange );
	onChange();

	function onSubmit() {
		var text, data;

		$result.css( 'opacity', 0.5 );
		text = textarea.getValue();
		data = {
			format: 'json',
			action: 'parse',
			text: '<hiero>' + text + '</hiero>',
			disablepp: ''
		};
		$.post( mw.util.wikiScript( 'api' ),
			data,
			function ( response ) {
				var html = '<table class="wikitable">' +
					'<tr><th>' + mw.msg( 'wikihiero-input' ) + '</th><th>' +
					mw.msg( 'wikihiero-result' ) + '</th></tr>' +
					'<tr><td><code>&lt;hiero&gt;' +
					mw.html.escape( text ).replace( '\n', '<br/>' ) +
					'&lt;/hiero&gt;</code></td>' +
					'<td>' + response.parse.text[ '*' ] + '</td></tr></table>';
				$result.html( html );
			}
		).fail( function () {
			$result.text( mw.msg( 'wikihiero-load-error' ) );
		} ).always( function () {
			$result.css( 'opacity', 1 );
		} );
	}
	submit.on( 'click', onSubmit );

	// eslint-disable-next-line no-jquery/no-global-selector
	$( '.mw-hiero-toc a' ).on( 'click', function ( e ) {
		if ( this.hash ) {
			e.preventDefault();
			// eslint-disable-next-line no-jquery/no-global-selector
			$( window ).scrollTop( $( this.hash ).offset().top - $( '.mw-hiero-form' ).outerHeight() );
		}
	} );

	// eslint-disable-next-line no-jquery/no-global-selector
	$( '.mw-hiero-code' ).on( 'click', function () {
		var val = textarea.getValue().trim();
		textarea.setValue(
			val + ( val ? ' ' : '' ) + $( this ).find( '.mw-hiero-syntax' ).text()
		);
		textarea.moveCursorToEnd().focus();
		onSubmit();
	} );

} );

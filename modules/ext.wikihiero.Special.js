( function () {
	'use strict';

	var $textarea = OO.ui.infuse( $( '#hiero-text' ).closest( '.oo-ui-widget' ) ),
		$submit = OO.ui.infuse( $( '#hiero-submit' ).closest( '.oo-ui-widget' ) ),
		$result = $( '#hiero-result' );

	$textarea.$input.keyup( function () {
		if ( $textarea.getValue().length === 0 ) {
			$submit.setDisabled( true );
		} else {
			$submit.setDisabled( false );
		}
	} );
	$textarea.$input.keyup();

	$submit.$input.click( function ( e ) {
		var gettext, text, data;

		e.preventDefault();
		$result.hide();
		$result.injectSpinner( 'hiero' );
		gettext = $textarea.getValue();
		text = gettext.replace( /<\/?hiero>/gm, '' );
		data = {
			format: 'json',
			action: 'parse',
			text: '<hiero>' + text + '</hiero>',
			disablepp: ''
		};
		$.post( mw.util.wikiScript( 'api' ),
			data,
			function ( data ) {
				var html = '<table class="wikitable">' +
					'<tr><th>' + mw.msg( 'wikihiero-input' ) + '</th><th>' +
					mw.msg( 'wikihiero-result' ) + '</th></tr>' +
					'<tr><td><code>&lt;hiero&gt;' +
					mw.html.escape( text ).replace( '\n', '<br/>' ) +
					'&lt;/hiero&gt;</code></td>' +
					'<td>' + data.parse.text[ '*' ] + '</td></tr></table>';
				$.removeSpinner( 'hiero' );
				$result.html( html );
				$result.show();
			}
		).error( function () {
			$result.text( mw.msg( 'wikihiero-load-error' ) );
			$result.show();
		} );
	} );

}() );

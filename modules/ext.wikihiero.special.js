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
				const $table = $( '<table>' ).addClass( 'wikitable' );

				const $headerRow = $( '<tr>' )
					.append( $( '<th>' ).text( mw.msg( 'wikihiero-input' ) ) )
					.append( $( '<th>' ).text( mw.msg( 'wikihiero-result' ) ) );

				const escapedText = mw.html.escape( text ).replace( '\n', '<br/>' );
				const $code = $( '<code>' ).html( '&lt;hiero&gt;' + escapedText + '&lt;/hiero&gt;' );

				const $dataRow = $( '<tr>' )
					.append( $( '<td>' ).append( $code ) )
					.append( $( '<td>' ).html( response.parse.text[ '*' ] ) );
				$table.append( $headerRow, $dataRow );

				$result.html( $table );
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

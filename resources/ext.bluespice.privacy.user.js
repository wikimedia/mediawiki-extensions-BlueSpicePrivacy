( function( mw, $, bs ) {
	$( '.bs-privacy-user-section' ).each( function( k, section ) {
		let $section = $( section );

		let rlModule = $section.data( 'rl-module' );
		if ( !rlModule ) {
			return;
		}

		mw.loader.using( rlModule ).then( function() {
			let sectionCallback = $section.data( 'callback' );
			let func = bs.privacy.util.funcFromCallback( sectionCallback );

			let config = {};
			if( $section.data( 'config' ) ) {
				config = $section.data( 'config' );
			}

			let widget = new func( $.extend( {
				$element: $section
			}, config ) );
			widget.init();
		} );
	} );

} )( mediaWiki, jQuery, blueSpice );

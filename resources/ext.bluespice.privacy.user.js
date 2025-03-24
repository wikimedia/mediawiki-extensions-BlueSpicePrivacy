( function ( mw, $, bs ) {
	$( '.bs-privacy-user-section' ).each( ( k, section ) => {
		const $section = $( section );

		const rlModule = $section.data( 'rl-module' );
		if ( !rlModule ) {
			return;
		}

		mw.loader.using( rlModule ).then( () => {
			const sectionCallback = $section.data( 'callback' );
			const func = bs.privacy.util.funcFromCallback( sectionCallback );

			let config = {};
			if ( $section.data( 'config' ) ) {
				config = $section.data( 'config' );
			}

			const widget = new func( Object.assign( { // eslint-disable-line new-cap
				$element: $section
			}, config ) );
			widget.init();
		} );
	} );

}( mediaWiki, jQuery, blueSpice ) );

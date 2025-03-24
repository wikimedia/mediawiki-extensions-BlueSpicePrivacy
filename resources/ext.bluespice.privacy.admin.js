( function ( mw, $ ) {
	const requestManager = new bs.privacy.widget.RequestManager( {
		$element: $( '#bs-privacy-admin-requests' )
	} );
	requestManager.init();

	function initPanel( $section ) {
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
	}

	$( '.bs-privacy-admin-section' ).each( ( k, section ) => {
		const $section = $( section );

		const rlModule = $section.data( 'rl-module' );
		if ( !rlModule ) {
			initPanel( $section );
			return;
		}
		mw.loader.using( rlModule ).then( () => {
			initPanel( $section );
		} );
	} );
}( mediaWiki, jQuery ) );

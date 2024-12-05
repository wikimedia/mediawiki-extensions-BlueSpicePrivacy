( function( mw, $ ) {
	var requestManager = new bs.privacy.widget.RequestManager( {
		$element: $( '#bs-privacy-admin-requests' )
	} );
	requestManager.init();

	function initPanel( $section ) {
		var sectionCallback = $section.data( 'callback' );
		var func = bs.privacy.util.funcFromCallback( sectionCallback );

		var config = {};
		if( $section.data( 'config' ) ) {
			config = $section.data( 'config' );
		}

		var widget = new func( $.extend( {
			$element: $section
		}, config ) );
		widget.init();
	}

	$( '.bs-privacy-admin-section' ).each( function( k, section ) {
		var $section = $( section );

		var rlModule = $section.data( 'rl-module' );
		if ( !rlModule ) {
			initPanel( $section );
			return;
		}
		mw.loader.using( rlModule ).then( function() {
			initPanel( $section );
		} );
	} );
} )( mediaWiki, jQuery );

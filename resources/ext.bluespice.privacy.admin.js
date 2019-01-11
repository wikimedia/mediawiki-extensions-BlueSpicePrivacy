( function( mw, $ ) {
	Ext.onReady( function() {
		Ext.Loader.setPath(
			'BS.Privacy',
			'/extensions/BlueSpicePrivacy/resources/BS.Privacy'
		);

		var requestManager = new bs.privacy.widget.RequestManager( {
			$element: $( '#bs-privacy-admin-requests' )
		} );

		var consentOverview = new bs.privacy.widget.ConsentOverview( {
			$element: $( '#bs-privacy-admin-consents' )
		} );

		requestManager.init();
		consentOverview.init();
	} );
} )( mediaWiki, jQuery );
( function( mw, $, bs ) {
	var handlerConfig = mw.config.get( "bsPrivacyCookieConsentHandlerConfig" );
	mw.loader.using( handlerConfig.RLModule ).done( function() {
		var callback = handlerConfig.class;

		var func = bs.privacy.util.funcFromCallback( callback );

		try {
			var handler = new func( {
				cookieName: handlerConfig.cookieName,
				cookieMap: handlerConfig.map
			} );
		} catch( err ) {
			return;
		}

		// Bind to change cookie settings footer link
		$( 'body' ).on( 'click', '#bs-privacy-footer-change-cookie-settings', function() {
			var openSettings = handler.settingsOpen;
			openSettings.apply( handler );
		} );
	} );
} )( mediaWiki, jQuery, blueSpice );

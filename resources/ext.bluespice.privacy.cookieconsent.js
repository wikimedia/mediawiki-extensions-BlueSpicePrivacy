$( function() {
	var handlerConfig = mw.config.get( "bsPrivacyCookieConsentHandlerConfig" );
	var callback = handlerConfig.class;

	var func = bs.privacy.util.funcFromCallback( callback );

	var handler = new func( {
		cookieName: handlerConfig.cookieName,
		cookieMap: handlerConfig.map
	} );

	// Bind to change cookie settings footer link
	$( 'body' ).on( 'click', '#bs-privacy-footer-change-cookie-settings', function() {
		var openSettings = handler.settingsOpen;
		openSettings.apply( handler );
	} );
} );

$( function() {
	var handlerConfig = mw.config.get( "bsPrivacyCookieConsentHandlerConfig" );
	var callback = handlerConfig.class;

	var func = bs.privacy.util.funcFromCallback( callback );

	new func( {
		cookieName: handlerConfig.cookieName,
		cookieMap: handlerConfig.map
	} );
} );

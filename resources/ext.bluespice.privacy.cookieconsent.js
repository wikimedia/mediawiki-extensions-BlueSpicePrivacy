( function( mw, $, bs ) {
	var handlerConfig = mw.config.get( "bsPrivacyCookieConsentHandlerConfig" ),
		handler = null,
		cookieGetterOrig = document.__lookupGetter__( "cookie" ),
		cookieSetterOrig = document.__lookupSetter__( "cookie" ),
		cookieQueue = [];

	mw.loader.using( handlerConfig.RLModule ).done( function() {
		var callback = handlerConfig.class;

		var func = bs.privacy.util.funcFromCallback( callback );

		try {
			handler = new func( {
				cookieName: handlerConfig.cookieName,
				cookieMap: handlerConfig.map,
				cookieSetterOrig: cookieSetterOrig
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

	// Override default mechanism for setting cookies
	Object.defineProperty(document, "cookie", {
		get: function () {
			return cookieGetterOrig.apply(document);
		},
		set: function () {
			return setIfAllowed.apply( this, arguments );
		}.bind( this ),
		configurable: true
	} );

	function setIfAllowed() {
		// Check if cookie is allowed with handler
		// If handler not yet available, queue cookies
		if ( !handler ) {
			cookieQueue.push( arguments );
			return false;
		} else {
			if ( cookieQueue.length > 0 ) {
				for ( var i = 0; i < cookieQueue.length; i ++ ) {
					handler.setIfAllowed.apply( handler, cookieQueue[i] );
				}
				cookieQueue = [];
			}

			return handler.setIfAllowed.apply( handler, arguments );
		}
	}
} )( mediaWiki, jQuery, blueSpice );

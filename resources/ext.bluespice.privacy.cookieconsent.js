( function ( mw, $, bs ) {
	const handlerConfig = mw.config.get( 'bsPrivacyCookieConsentHandlerConfig' );
	let handler = null;
	const cookieGetterOrig = document.__lookupGetter__( 'cookie' ); // eslint-disable-line no-underscore-dangle
	const cookieSetterOrig = document.__lookupSetter__( 'cookie' ); // eslint-disable-line no-underscore-dangle
	let cookieQueue = [];

	mw.loader.using( handlerConfig.RLModule ).done( () => {
		const callback = handlerConfig.class;

		const func = bs.privacy.util.funcFromCallback( callback );

		try {
			handler = new func( { // eslint-disable-line new-cap
				cookiePrefix: handlerConfig.cookiePrefix,
				cookiePath: handlerConfig.cookiePath,
				cookieName: handlerConfig.cookieName,
				cookieMap: handlerConfig.map,
				cookieSetterOrig: cookieSetterOrig
			} );
		} catch ( err ) {
			return;
		}

		$( '#userloginForm .mw-htmlform .warningbox' ).attr( 'tabindex', 0 );
		// Bind to change cookie settings footer link
		$( 'body' ).on( 'click', '#bs-privacy-footer-change-cookie-settings', () => {
			const openSettings = handler.settingsOpen;
			openSettings.apply( handler );
		} );
	} );

	// Override default mechanism for setting cookies
	Object.defineProperty( document, 'cookie', {
		get: function () {
			return cookieGetterOrig.apply( document );
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
				for ( let i = 0; i < cookieQueue.length; i++ ) {
					handler.setIfAllowed.apply( handler, cookieQueue[ i ] );
				}
				cookieQueue = [];
			}

			return handler.setIfAllowed.apply( handler, arguments );
		}
	}
}( mediaWiki, jQuery, blueSpice ) );

( function ( mw, $, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.widget = bs.privacy.widget || {};
	bs.privacy.cookieConsent = bs.privacy.cookieConsent || {};

	bs.privacy.util = {
		funcFromCallback: _funcFromCallback
	};

	function _funcFromCallback( callback ) { // eslint-disable-line no-underscore-dangle
		const parts = callback.split( '.' );
		let func = window[ parts[ 0 ] ];
		for ( let i = 1; i < parts.length; i++ ) {
			func = func[ parts[ i ] ];
		}
		return func;
	}

}( mediaWiki, jQuery, blueSpice ) );

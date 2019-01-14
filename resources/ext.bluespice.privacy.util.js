( function( mw, $, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.widget = bs.privacy.widget || {};
	bs.privacy.cookieConsent = bs.privacy.cookieConsent || {};

	bs.privacy.util = {
		funcFromCallback: _funcFromCallback
	};

	function _funcFromCallback( callback ) {
		var parts = callback.split( '.' );
		var func = window[parts[0]];
		for( var i = 1; i < parts.length; i++ ) {
			func = func[parts[i]];
		}
		return func;
	}

} )( mediaWiki, jQuery, blueSpice );
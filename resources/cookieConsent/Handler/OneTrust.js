( function( mw, $ ) {
	bs.privacy.cookieConsent.OneTrust = function( cfg ) {
		cfg = cfg || {};

		bs.privacy.cookieConsent.OneTrust.parent.call( this, cfg );
	};

	OO.inheritClass( bs.privacy.cookieConsent.OneTrust, bs.privacy.cookieConsent.BaseHandler );

	bs.privacy.cookieConsent.OneTrust.prototype.getGroups = function() {
		var cookie = $.cookie( this.cookieName );

		if( !cookie ) {
			return [];
		}
		// Find `groups=((.*=?))\' in the cookie
		var match = cookie.match( /groups=(.*=?)/ );
		if( !match ) {
			return [];
		}
		// Get the first match
		var groups = match[1];

		var pairs = groups.split( "," );
		groups = {};
		for( var i = 0; i < pairs.length; i++ ){
			var pair = pairs[i].split( ":" );
			groups[( pair[0] + '' ).trim()] = pair[1] === "1";
		}

		return groups;
	};

	bs.privacy.cookieConsent.OneTrust.prototype.getCookieName = function( prefixed ) {
		return this.cookieName;
	};

	bs.privacy.cookieConsent.OneTrust.prototype.settingsOpen = function() {
		Optanon.ToggleInfoDisplay();
	};
} )( mediaWiki, jQuery );

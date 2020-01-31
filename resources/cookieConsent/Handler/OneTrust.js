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
		var parsed = JSON.parse( '{"' + decodeURI( cookie ).replace(/"/g, '\\"').replace(/&/g, '","').replace(/=/g,'":"') + '"}' );

		if( !parsed.groups ) {
			return [];
		}
		var groups = parsed.groups;

		var pairs = groups.split( "," );
		groups = {};
		for( var i = 0; i < pairs.length; i++ ){
			var pair = pairs[i].split(":");
			groups[( pair[0]+'' ).trim()] = pair[1] === "1" ? true : false;
		}

		return groups;
	};

	bs.privacy.cookieConsent.OneTrust.prototype.settingsOpen = function() {
		Optanon.ToggleInfoDisplay();
	};
} )( mediaWiki, jQuery );

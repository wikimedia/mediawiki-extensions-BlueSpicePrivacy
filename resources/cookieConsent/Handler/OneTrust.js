( function ( mw, $ ) {
	bs.privacy.cookieConsent.OneTrust = function ( cfg ) {
		cfg = cfg || {};

		bs.privacy.cookieConsent.OneTrust.parent.call( this, cfg );
	};

	OO.inheritClass( bs.privacy.cookieConsent.OneTrust, bs.privacy.cookieConsent.BaseHandler );

	bs.privacy.cookieConsent.OneTrust.prototype.getGroups = function () {
		const cookie = $.cookie( this.cookieName );

		if ( !cookie ) {
			return [];
		}
		// Find `groups=((.*=?))\' in the cookie
		const match = cookie.match( /groups=(.*=?)/ );
		if ( !match ) {
			return [];
		}
		// Get the first match
		let groups = match[ 1 ];

		const pairs = groups.split( ',' );
		groups = {};
		for ( let i = 0; i < pairs.length; i++ ) {
			const pair = pairs[ i ].split( ':' );
			groups[ ( String( pair[ 0 ] ) ).trim() ] = pair[ 1 ] === '1';
		}

		return groups;
	};

	bs.privacy.cookieConsent.OneTrust.prototype.getCookieName = function ( prefixed ) { // eslint-disable-line no-unused-vars
		return this.cookieName;
	};

	bs.privacy.cookieConsent.OneTrust.prototype.settingsOpen = function () {
		Optanon.ToggleInfoDisplay(); // eslint-disable-line no-undef
	};

	bs.privacy.cookieConsent.OneTrust.prototype.isCookieAllowed = function ( cookieName ) {
		if ( cookieName === 'OptanonAlertBoxClosed' ) {
			return true;
		}
		return bs.privacy.cookieConsent.OneTrust.parent.prototype.isCookieAllowed.call( this, cookieName );
	};
}( mediaWiki, jQuery ) );

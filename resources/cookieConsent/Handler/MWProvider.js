( function( mw, $ ) {

	bs.privacy.cookieConsent.MWProvider = function( cfg ) {
		cfg = cfg || {};

		bs.privacy.cookieConsent.MWProvider.parent.call( this, cfg );
	};

	OO.inheritClass( bs.privacy.cookieConsent.MWProvider, bs.privacy.cookieConsent.BaseHandler );

	bs.privacy.cookieConsent.MWProvider.prototype.getGroups = function() {
		var cookie = $.cookie( this.cookieName );
		if( !cookie ) {
			return [];
		}

		var parsed = JSON.parse( cookie );
		if( !parsed.groups ) {
			return [];
		}

		return parsed.groups;
	};

	bs.privacy.cookieConsent.MWProvider.prototype.settingsOpen = function() {
		var windowManager = OO.ui.getWindowManager();
		var cfg = {
			values: this.getGroups(),
			size: 'normal'
		};
		var dialog = new bs.privacy.dialog.CookieConsentSettings( cfg );
		windowManager.addWindows( [ dialog ] );
		windowManager.openWindow( dialog ).closed.then( function ( data ) {
			if( !data ) {
				return;
			}
			if( data.action === 'save' ) {
				var newVal = {};
				for( var groupName in this.getGroups() ) {
					if( data.results.indexOf( groupName ) !== -1 ) {
						newVal[groupName] = true;
					} else {
						newVal[groupName] = false;
					}
				}
				var cookieVal = {
					groups: newVal
				};

				// Set the cookie - expires in 20 years
				$.cookie( this.cookieName, JSON.stringify( cookieVal ), { path: '/', expires: 20 * 365 } );
			}
		}.bind( this ) );
	};
} )( mediaWiki, jQuery );
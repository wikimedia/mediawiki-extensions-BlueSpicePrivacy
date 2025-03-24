( function ( mw ) {

	bs.privacy.cookieConsent.MWProvider = function ( cfg ) {
		cfg = cfg || {};

		bs.privacy.cookieConsent.MWProvider.parent.call( this, cfg );
	};

	OO.inheritClass( bs.privacy.cookieConsent.MWProvider, bs.privacy.cookieConsent.BaseHandler );

	bs.privacy.cookieConsent.MWProvider.prototype.getGroups = function () {
		const settings = localStorage.getItem( this.getCookieName( true ) );
		if ( !settings ) {
			return [];
		}

		const parsed = JSON.parse( settings );
		if ( !parsed.groups ) {
			return [];
		}

		return parsed.groups;
	};

	bs.privacy.cookieConsent.MWProvider.prototype.settingsOpen = function () {
		const windowManager = OO.ui.getWindowManager();
		const cfg = {
			values: this.getGroups(),
			size: 'normal'
		};
		const dialog = new bs.privacy.dialog.CookieConsentSettings( cfg );
		windowManager.addWindows( [ dialog ] );
		windowManager.openWindow( dialog ).closed.then( ( data ) => {
			if ( !data ) {
				return;
			}
			if ( data.action === 'save' ) {
				const newVal = {};
				for ( const groupName in this.getGroups() ) {
					if ( data.results.indexOf( groupName ) !== -1 ) {
						newVal[ groupName ] = true;
					} else {
						newVal[ groupName ] = false;
					}
				}
				const cookieVal = JSON.stringify( {
					groups: newVal
				} );

				// Set the cookie - expires in 20 years
				mw.cookie.set( this.getCookieName(), cookieVal, { path: '/', expires: 20 * 365 } );
				localStorage.setItem( this.getCookieName( true ), cookieVal );
			}
		} );
	};
}( mediaWiki ) );

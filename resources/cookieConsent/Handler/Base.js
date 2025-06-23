( function ( mw, $, bs ) {
	bs.privacy = bs.privacy || {};
	bs.privacy.cookieConsent = {};

	bs.privacy.cookieConsent.BaseHandler = function ( cfg ) {
		cfg = cfg || {};

		this.cookieName = cfg.cookieName;
		this.cookiePrefix = cfg.cookiePrefix;
		this.cookiePath = cfg.cookiePath;
		this.cookieMap = cfg.cookieMap;
		this.cookieSetterOrig = cfg.cookieSetterOrig;

		this.groups = this.getGroups();

		window.addEventListener( 'beforeunload', this.handleCookieRemoval.bind( this ) );

		$( document ).bind( 'ajaxSend', this.handleCookieRemoval.bind( this ) ); // eslint-disable-line no-jquery/no-bind
	};

	OO.initClass( bs.privacy.cookieConsent.BaseHandler );

	bs.privacy.cookieConsent.BaseHandler.prototype.setIfAllowed = function () {
		if ( arguments.length === 0 ) {
			this.cookieSetterOrig.apply( document, arguments );
			return;
		}
		const cookie = arguments[ 0 ];
		if ( !cookie ) {
			return true;
		}
		const bits = cookie.split( '=' );
		if ( !bits ) {
			return true;
		}
		const cookieName = bits.shift();
		if ( !cookieName ) {
			return false;
		}
		if ( this.isCookieAllowed( cookieName ) ) {
			this.cookieSetterOrig.apply( document, arguments );
			return;
		}
		// Unset cookie in case its already set
		this.cookieSetterOrig.apply( document, [
			cookieName + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=' + this.cookiePath
		] );
		return false;
	};

	bs.privacy.cookieConsent.BaseHandler.prototype.getCookieName = function ( prefixed ) {
		if ( !prefixed ) {
			return '_' + this.cookieName;
		}
		return this.cookiePrefix + this.getCookieName( false );
	};

	bs.privacy.cookieConsent.BaseHandler.prototype.isCookieAllowed = function ( cookieName ) {
		if ( cookieName === this.getCookieName( true ) ) {
			// Always allow cookie preferences cookie
			return true;
		}
		const cookieGroup = this.getCookieGroup( cookieName );
		if ( !cookieGroup || !this.groups.hasOwnProperty( cookieGroup ) ) {
			return false;
		}

		return this.groups[ cookieGroup ];
	};

	bs.privacy.cookieConsent.BaseHandler.prototype.getGroups = function () {
		// STUB
		return [];
	};

	bs.privacy.cookieConsent.BaseHandler.prototype.getCookieGroup = function ( cookieName ) {

		for ( const groupId in this.cookieMap ) {
			if ( !this.cookieMap.hasOwnProperty( groupId ) ) {
				continue;
			}
			for ( const idx in this.cookieMap[ groupId ] ) {
				if ( !this.cookieMap[ groupId ].hasOwnProperty( idx ) ) {
					continue;
				}
				const cookieItem = this.cookieMap[ groupId ][ idx ];
				if ( !cookieItem.type || cookieItem.type === 'exact' ) {
					if ( cookieName === cookieItem.name ) {
						return groupId;
					}
				}
				if ( cookieItem.type === 'regex' ) {
					const re = new RegExp( cookieItem.name );
					if ( re.test( cookieName ) ) {
						return groupId;
					}
				}
			}
		}
		console.warn( 'Cookie ' + cookieName + ' is not registered with any cookie groups' ); // eslint-disable-line no-console
		return null;
	};

	bs.privacy.cookieConsent.BaseHandler.prototype.parseActiveCookies = function () {
		const pairs = document.cookie.split( ';' );
		this.cookies = [];
		for ( let i = 0; i < pairs.length; i++ ) {
			const pair = pairs[ i ].split( '=' );
			this.cookies.push( ( String( pair[ 0 ] ) ).trim() );
		}
	};

	bs.privacy.cookieConsent.BaseHandler.prototype.handleCookieRemoval = function ( e ) { // eslint-disable-line no-unused-vars
		this.parseActiveCookies();

		for ( const idx in this.cookies ) {
			const cookieName = this.cookies[ idx ];
			if ( !this.isCookieAllowed( cookieName ) ) {
				$.removeCookie( cookieName, { path: this.cookiePath } );
			}
		}
	};

	bs.privacy.cookieConsent.BaseHandler.prototype.getSettingsWidget = function () {
		const settingsWidget = new OO.ui.ButtonWidget( {
			label: mw.message( 'bs-privacy-consent-cookie-settings-label' ).text()
		} );

		settingsWidget.$element.css( 'display', 'block' );

		settingsWidget.on( 'click', this.settingsOpen.bind( this ) );

		return settingsWidget;
	};

	bs.privacy.cookieConsent.BaseHandler.prototype.settingsOpen = function () {
		// STUB
	};
}( mediaWiki, jQuery, blueSpice ) );

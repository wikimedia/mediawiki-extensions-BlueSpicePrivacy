( function( mw, $, bs ) {
	bs.privacy = bs.privacy || {};
	bs.privacy.cookieConsent = {};

	bs.privacy.cookieConsent.BaseHandler = function( cfg ) {
		cfg = cfg || {};

		this.cookieName = mw.config.get( 'wgCookiePrefix' ) + '_' + cfg.cookieName;
		this.cookieMap = cfg.cookieMap;

		this.groups = this.getGroups();

		window.addEventListener( 'beforeunload', this.handleCookieRemoval.bind( this ) );

		$( document ).bind( "ajaxSend", this.handleCookieRemoval.bind( this ) );
	};

	OO.initClass( bs.privacy.cookieConsent.BaseHandler );

	bs.privacy.cookieConsent.BaseHandler.prototype.isCookieAllowed = function( cookieName ) {
		var cookieGroup = this.getCookieGroup( cookieName );

		if( !(cookieGroup in this.groups) ) {
			return true;
		}
		if( this.groups[cookieGroup] === true ) {

			return true;
		}
		return false;
	};

	bs.privacy.cookieConsent.BaseHandler.prototype.getGroups = function() {
		// STUB
		return [];
	};

	bs.privacy.cookieConsent.BaseHandler.prototype.getCookieGroup = function( cookieName ) {
		for( var groupId in this.cookieMap ) {
			for( var idx in this.cookieMap[groupId] ) {
				var cookieItem = this.cookieMap[groupId][idx];
				if( !cookieItem.type || cookieItem.type === 'exact' ) {
					if( cookieName === cookieItem.name ) {
						return groupId;
					}
				}
				if( cookieItem.type === 'regex' ) {
					var re = new RegExp( cookieItem.name );
					if( re.test( cookieName ) ) {
						return groupId;
					}
				}
			}
		}
		return '';
	};

	bs.privacy.cookieConsent.BaseHandler.prototype.parseActiveCookies = function() {
		var pairs = document.cookie.split( ";" );
		this.cookies = [];
		for( var i = 0; i < pairs.length; i++ ){
			var pair = pairs[i].split("=");
			this.cookies.push(( pair[0]+'' ).trim());
		}
	};

	bs.privacy.cookieConsent.BaseHandler.prototype.handleCookieRemoval = function( e ) {
		this.parseActiveCookies();

		for( var idx in this.cookies ) {
			var cookieName = this.cookies[idx];
			if( !this.isCookieAllowed( cookieName ) ) {
				$.removeCookie( cookieName, { path: '/' } );
			}
		}
	};

	bs.privacy.cookieConsent.BaseHandler.prototype.getSettingsWidget = function() {
		var settingsWidget = new OO.ui.ButtonWidget( {
			framed: false,
			label: mw.message( 'bs-privacy-consent-cookie-settings-label' ).text()
		} );

		settingsWidget.$element.css( 'display', 'block' );

		settingsWidget.on( 'click', this.settingsOpen.bind( this ) );

		return settingsWidget;
	};

	bs.privacy.cookieConsent.BaseHandler.prototype.settingsOpen = function() {
		// STUB
	};
} )( mediaWiki, jQuery, blueSpice );

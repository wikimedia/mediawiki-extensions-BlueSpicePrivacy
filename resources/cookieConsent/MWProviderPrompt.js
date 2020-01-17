( function( mw, $, bs ){
	bs.privacy = bs.privacy || {};

	bs.privacy.cookieConsent.MWProviderPrompt = function() {
		this.cookieName =
			mw.config.get( 'wgCookiePrefix' ) + '_' +
			mw.config.get( "bsPrivacyCookieConsentHandlerConfig" ).cookieName;
		this.groups = mw.config.get( "bsPrivacyCookieConsentHandlerConfig" ).cookieGroups;

		if( this.cookieExists() === false ) {
			this.showFirstLoad();
		} else if ( !$.cookie( this.cookieName ) ) {
			// If cookie is not set, but settings do exist in localStorage,
			// translate settings from local storage to cookie so they can be passed to the server
			$.cookie( this.cookieName, localStorage.getItem( this.cookieName ), { path: '/', expires: 20 * 365 } );
		}
	};

	OO.initClass( bs.privacy.cookieConsent.MWProviderPrompt );

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.cookieExists = function() {
		return localStorage.getItem( this.cookieName ) !== null;
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.showFirstLoad = function() {
		this.makeBar();
		this.makeOverlay();

		$( 'body' ).append( this.$overlay, this.bar.$element );

		this.$overlay.fadeIn( 500 );
		this.bar.$element.fadeIn( 500 );

		this.bar.on( 'cookieSettingsChanged', this.onCookieSettingsChanged.bind( this ) );
		this.bar.on( 'cookieSettingsOpen', this.showSettingsDialog.bind( this ) );
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.makeOverlay = function() {
		this.$overlay = $( '<div>' ).addClass( 'bs-privacy-cookie-consent-mw-provider-overlay' );
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.makeBar = function() {
		if ( this.bar ) {
			return;
		}

		this.bar = new OO.ui.HorizontalLayout( {
			classes: ['bs-privacy-cookie-consent-mw-provider-bar']
		} );

		var disclamer = new OO.ui.LabelWidget( {
			label: mw.message( 'bs-privacy-cookie-consent-mw-provider-disclaimer' ).text(),
			classes: [ 'disclaimer' ]
		} );
		var settings = new OO.ui.ButtonWidget( {
			framed: false,
			label: mw.message( 'bs-privacy-cookie-consent-mw-provider-settings-btn-label' ).text(),
			icon: 'next'
		} );
		settings.on( 'click', function() {
			this.bar.emit( 'cookieSettingsOpen' );
		}.bind( this ) );

		var acceptAll = new OO.ui.ButtonWidget( {
			flags: [
				'primary',
				'progressive'
			],
			icon: 'check',
			label: mw.message( 'bs-privacy-cookie-consent-mw-provider-accept-all-btn-label' ).text()
		} );
		acceptAll.on( 'click', function() {
			this.bar.emit( 'cookieSettingsChanged', '*' );
		}.bind( this ) );

		this.bar.addItems( [
			disclamer,
			settings,
			acceptAll
		] );
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.onCookieSettingsChanged = function( groups ) {
		var cookieVal = JSON.stringify( {
			groups: this.getGroupsForCookie( groups )
		} );

		// Set the cookie - expires in 20 years
		$.cookie( this.cookieName, cookieVal, { path: '/', expires: 20 * 365 } );
		localStorage.setItem( this.cookieName, cookieVal );

		this.bar.$element.remove();
		this.$overlay.remove();
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.getGroupsForCookie = function( groups ) {
		var ret = {};
		for( var groupName in this.groups ) {
			if( groups === '*' ) {
				ret[groupName] = true;
				continue;
			}

			if( groups.indexOf( groupName ) !== -1 ) {
				ret[groupName] = true;
			} else {
				ret[groupName] = false;
			}
		}

		return ret;
	};


	bs.privacy.cookieConsent.MWProviderPrompt.prototype.showSettingsDialog = function() {
		var windowManager = OO.ui.getWindowManager();
		var cfg = {
			size: 'normal'
		};
		var dialog = new bs.privacy.dialog.CookieConsentSettings( cfg );
		windowManager.addWindows( [ dialog ] );
		windowManager.openWindow( dialog ).closed.then( function ( data ) {
			if( data.action === 'save' ) {
				this.onCookieSettingsChanged( data.results );
			}
		}.bind( this ) );
	};

	new bs.privacy.cookieConsent.MWProviderPrompt();

} )( mediaWiki, jQuery, blueSpice );



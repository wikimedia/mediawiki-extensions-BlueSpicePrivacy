( function ( mw, $, bs ) {
	bs.privacy = bs.privacy || {};
	$( '.mw-ui-container #userloginForm' ).css( 'pointer-events', 'visible' );

	bs.privacy.cookieConsent.MWProviderPrompt = function () {
		this.handlerConfig = mw.config.get( 'bsPrivacyCookieConsentHandlerConfig' );
		this.acceptMandatory = this.handlerConfig.acceptMandatory;
		this.groups = this.handlerConfig.cookieGroups;

		if ( this.shouldCookieBannerBeShown() ) {
			this.showFirstLoad();
			$( '#wpLoginAttempt' ).attr( 'disabled', 'disabled' );
		}
	};

	OO.initClass( bs.privacy.cookieConsent.MWProviderPrompt );

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.cookieExists = function () {
		if ( mw.cookie.get( this.getCookieName() ) ) {
			return true;
		} else if ( localStorage.getItem( this.getCookieName( true ) ) !== null ) {
			// If cookie is not set, but settings do exist in localStorage,
			// translate settings from local storage to cookie so they can be passed to the server
			mw.cookie.set(
				this.getCookieName(),
				localStorage.getItem( this.getCookieName( true ) ),
				{ path: '/', expires: 20 * 365 }
			);
			return true;
		}
		// Neither cookie nor localstorage entry exists
		return false;
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.getCookieName = function ( prefixed ) {
		if ( !prefixed ) {
			return '_' + this.handlerConfig.cookieName;
		}
		return this.handlerConfig.cookiePrefix + this.getCookieName( false );
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.showFirstLoad = function () {
		this.makeBar();

		if ( this.acceptMandatory ) {
			this.makeOverlay();
			$( 'body' ).append( this.$overlay );
			this.$overlay.fadeIn( 500 ); // eslint-disable-line no-jquery/no-fade
		}
		this.bar.$element.fadeIn( 500 ); // eslint-disable-line no-jquery/no-fade

		this.bar.on( 'cookieSettingsChanged', this.onCookieSettingsChanged.bind( this ) );
		this.bar.on( 'cookieSettingsOpen', this.showSettingsDialog.bind( this ) );
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.makeOverlay = function () {
		this.$overlay = $( '<div>' ).addClass( 'bs-privacy-cookie-consent-mw-provider-overlay' );
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.makeBar = function () {
		if ( this.bar ) {
			return;
		}

		this.bar = new OO.ui.HorizontalLayout( {
			classes: [ 'bs-privacy-cookie-consent-mw-provider-bar' ]
		} );

		const disclamer = new OO.ui.LabelWidget( {
			label: mw.message( 'bs-privacy-cookie-consent-mw-provider-disclaimer' ).text(),
			classes: [ 'disclaimer' ]
		} );
		// Accessibility: Set tabindex fot tab navigation
		disclamer.$element.attr( 'tabindex', '0' );

		const settings = new OO.ui.ButtonWidget( {
			framed: false,
			label: mw.message( 'bs-privacy-cookie-consent-mw-provider-settings-btn-label' ).text(),
			icon: 'next'
		} );
		settings.on( 'click', () => {
			this.bar.emit( 'cookieSettingsOpen' );
		} );

		const acceptAll = new OO.ui.ButtonWidget( {
			flags: [
				'primary',
				'progressive'
			],
			icon: 'check',
			label: mw.message( 'bs-privacy-cookie-consent-mw-provider-accept-all-btn-label' ).text()
		} );
		acceptAll.on( 'click', () => {
			this.bar.emit( 'cookieSettingsChanged', '*' );
		} );

		this.bar.addItems( [
			disclamer,
			settings,
			acceptAll
		] );

		// Accessibility: prepend element to be first focus with tab navigation
		$( 'body' ).prepend( this.bar.$element );
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.onCookieSettingsChanged = function ( groups ) {
		groups = this.getGroupsForCookie( groups );
		for ( const groupName in this.groups ) {
			if ( !this.groups.hasOwnProperty( groupName ) ) {
				continue;
			}
			const groupConfig = this.groups[ groupName ];
			if ( groupConfig.hasOwnProperty( 'jsCallback' ) ) {
				this.executeExternalCallback( groupConfig.jsCallback, groups[ groupName ] );
			}
		}
		const cookieVal = JSON.stringify( {
			groups: groups
		} );

		// Set the cookie - expires in 20 years
		mw.cookie.set( this.getCookieName(), cookieVal, { path: '/', expires: 20 * 365 } );
		localStorage.setItem( this.getCookieName( true ), cookieVal );

		this.bar.$element.remove();
		if ( this.$overlay ) {
			this.$overlay.remove();
			$( '#wpLoginAttempt' ).removeAttr( 'disabled' );
		}
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.executeExternalCallback = function ( callbackData, group ) {
		if ( callbackData.hasOwnProperty( 'module' ) && callbackData.hasOwnProperty( 'callback' ) ) {
			mw.loader.using( callbackData.module ).done( () => {
				let args = { value: group };
				if ( callbackData.hasOwnProperty( 'args' ) ) {
					args = $.extend( callbackData.args, args ); // eslint-disable-line no-jquery/no-extend
				}
				bs.util.runCallback( callbackData.callback, [ args ], this );
			} );
		}
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.getGroupsForCookie = function ( groups ) {
		const ret = {};
		for ( const groupName in this.groups ) {
			if ( !this.groups.hasOwnProperty( groupName ) ) {
				continue;
			}
			if ( groups === '*' ) {
				ret[ groupName ] = true;
				continue;
			}

			if ( groups.indexOf( groupName ) !== -1 ) {
				ret[ groupName ] = true;
			} else {
				ret[ groupName ] = false;
			}
		}

		return ret;
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.showSettingsDialog = function () {
		const windowManager = OO.ui.getWindowManager();
		const cfg = {
			size: 'normal'
		};
		const dialog = new bs.privacy.dialog.CookieConsentSettings( cfg );
		windowManager.addWindows( [ dialog ] );
		windowManager.openWindow( dialog ).closed.then( ( data ) => {
			if ( !data ) {
				return;
			}
			if ( data.action === 'save' ) {
				this.onCookieSettingsChanged( data.results );
			}
		} );
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.shouldCookieBannerBeShown = function () {
		return this.cookieExists() === false && this.acceptMandatory;
	};

	new bs.privacy.cookieConsent.MWProviderPrompt(); // eslint-disable-line no-new

}( mediaWiki, jQuery, blueSpice ) );

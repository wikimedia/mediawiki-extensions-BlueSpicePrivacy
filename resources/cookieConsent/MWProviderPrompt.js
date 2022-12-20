( function( mw, $, bs ){
	bs.privacy = bs.privacy || {};
	$( ".mw-ui-container #userloginForm" ).css( "pointer-events", "visible" );

	bs.privacy.cookieConsent.MWProviderPrompt = function() {
		this.cookieName =
			mw.config.get( 'wgCookiePrefix' ) + '_' +
			mw.config.get( 'bsPrivacyCookieConsentHandlerConfig' ).cookieName;
		this.acceptMandatory = mw.config.get( 'bsPrivacyCookieAcceptMandatory' );
		this.groups = mw.config.get( "bsPrivacyCookieConsentHandlerConfig" ).cookieGroups;

		if( this.cookieExists() === false ) {
			this.showFirstLoad();
			$( '#wpLoginAttempt' ).attr( 'disabled', 'disabled' );
		}
	};

	OO.initClass( bs.privacy.cookieConsent.MWProviderPrompt );

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.cookieExists = function() {
		if ( $.cookie( this.cookieName ) ) {
			return true;
		} else if ( localStorage.getItem( this.cookieName ) !== null ) {
			// If cookie is not set, but settings do exist in localStorage,
			// translate settings from local storage to cookie so they can be passed to the server
			$.cookie( this.cookieName, localStorage.getItem( this.cookieName ), { path: '/', expires: 20 * 365 } );
			return true;
		}
		// Neither cookie nor localstorage entry exists
		return false;
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.showFirstLoad = function() {
		this.makeBar();

		if( this.acceptMandatory ) {
			this.makeOverlay();
			$( 'body' ).append( this.$overlay );
			this.$overlay.fadeIn( 500 );
		}
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
		// Accessibility: Set tabindex fot tab navigation
		disclamer.$element.attr( 'tabindex', '0' );

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

		// Accessibility: prepend element to be first focus with tab navigation
		$( 'body' ).prepend( this.bar.$element );
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.onCookieSettingsChanged = function( groups ) {
		groups = this.getGroupsForCookie( groups );
		for ( var groupName in this.groups ) {
			if ( !this.groups.hasOwnProperty( groupName ) ) {
				continue;
			}
			var groupConfig = this.groups[groupName];
			if( groupConfig.hasOwnProperty( 'jsCallback' ) ) {
				this.executeExternalCallback( groupConfig.jsCallback, groups[groupName] );
			}
		}
		var cookieVal = JSON.stringify( {
			groups: groups
		} );

		// Set the cookie - expires in 20 years
		$.cookie( this.cookieName, cookieVal, { path: '/', expires: 20 * 365 } );
		localStorage.setItem( this.cookieName, cookieVal );

		this.bar.$element.remove();
		if ( this.$overlay ) {
			this.$overlay.remove();
			$( '#wpLoginAttempt' ).removeAttr( 'disabled' );
		}
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.executeExternalCallback = function( callbackData, group ) {
		if ( callbackData.hasOwnProperty( 'module' ) && callbackData.hasOwnProperty( 'callback' ) ) {
			mw.loader.using( callbackData.module ).done( function() {
				var args = { value: group };
				if ( callbackData.hasOwnProperty( 'args' ) ) {
					args = $.extend( callbackData.args, args );
				}
				bs.util.runCallback( callbackData.callback, [ args ], this );
			}.bind( this ) );
		}
	};

	bs.privacy.cookieConsent.MWProviderPrompt.prototype.getGroupsForCookie = function( groups ) {
		var ret = {};
		for( var groupName in this.groups ) {
			if ( !this.groups.hasOwnProperty( groupName ) ) {
				continue;
			}
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
			if ( !data ) {
				return;
			}
			if( data.action === 'save' ) {
				this.onCookieSettingsChanged( data.results );
			}
		}.bind( this ) );
	};

	new bs.privacy.cookieConsent.MWProviderPrompt();

} )( mediaWiki, jQuery, blueSpice );



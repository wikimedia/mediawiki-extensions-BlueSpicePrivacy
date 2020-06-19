( function( mw, $, bs, d, undefined ){
	window.bs.privacy = bs.privacy || {};
	bs.privacy.dialog = bs.privacy.dialog || {};

	bs.privacy.dialog.CookieConsentSettings = function( cfg ) {
		cfg = cfg || {};

		this.values = cfg.values || {};
		this.groups = mw.config.get( "bsPrivacyCookieConsentHandlerConfig" ).cookieGroups;

		bs.privacy.dialog.CookieConsentSettings.super.call( this, cfg );
	};

	OO.inheritClass( bs.privacy.dialog.CookieConsentSettings, OO.ui.ProcessDialog );

	bs.privacy.dialog.CookieConsentSettings.static.name = 'cookieConsentSettings';

	bs.privacy.dialog.CookieConsentSettings.static.title = mw.message( 'bs-privacy-cookie-consent-mw-provider-settings-dialog-title' ).text();

	bs.privacy.dialog.CookieConsentSettings.static.actions = [
		{
			action: 'save',
			label: mw.message( 'bs-privacy-cookie-consent-mw-provider-settings-dialog-save' ).text(),
			flags: 'primary',
			disabled: false
		},
		{
			label: mw.message( 'bs-privacy-cookie-consent-mw-provider-settings-dialog-cancel' ).text(),
			flags: 'safe'
		}

	];

	bs.privacy.dialog.CookieConsentSettings.prototype.getActionProcess = function ( action ) {
		var me = this;

		if( action === 'save' ) {
			return new OO.ui.Process( function() {
				var results = [];

				for( var groupName in me.switches ) {
					var sw = me.switches[groupName];

					if( sw.getValue() === true ) {
						results.push( groupName );
					}
				}

				return me.close( { action: action, results: results } );
			} );
		}

		return bs.privacy.dialog.CookieConsentSettings.super.prototype.getActionProcess.call( this, action );
	};

	bs.privacy.dialog.CookieConsentSettings.prototype.initialize = function() {
		bs.privacy.dialog.CookieConsentSettings.super.prototype.initialize.call( this );

		var content = [];
		this.switches = {};
		for ( var groupName in this.groups ) {
			if ( !this.groups.hasOwnProperty( groupName ) ) {
				continue;
			}
			var groupSettings = this.groups[groupName];

			var label = groupSettings.label;
			if ( mw.message( groupSettings.label ).exists() ) {
				label = mw.message( groupSettings.label ).text();
			}
			var desc = groupSettings.desc;
			if ( mw.message( groupSettings.desc ).exists() ) {
				desc = mw.message( groupSettings.desc ).text();
			}

			var value = true;
			if( groupName in this.values ) {
				value = this.values[groupName];
			} else {
				if( groupSettings.type === 'opt-in' ) {
					value = false;
				}
			}
			if( groupSettings.type === 'always-on' ) {
				value = true;
			}
			var toggle = new OO.ui.ToggleSwitchWidget( {
				value: value,
				disabled: groupSettings.type === 'always-on'
			} );

			this.switches[groupName] = toggle;

			var field = new OO.ui.FieldLayout( toggle, {
				align: 'left',
				helpInline: false,
				label: label,
				help: desc
			} );
			content.push( field );
		}

		this.layout = new OO.ui.PanelLayout( {
			expanded: true,
			framed: false,
			padded: true,
			content: content,
			classes: [ 'bs-privacy-cookie-consent-mw-provider-dialog' ]
		} );

		this.$body.append( this.layout.$element );
	};

} )( mediaWiki, jQuery, blueSpice, document );

( function ( mw, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.dialog = bs.privacy.dialog || {};

	bs.privacy.dialog.CookieConsentSettings = function ( cfg ) {
		cfg = cfg || {};

		this.values = cfg.values || {};
		this.groups = mw.config.get( 'bsPrivacyCookieConsentHandlerConfig' ).cookieGroups;

		bs.privacy.dialog.CookieConsentSettings.super.call( this, cfg );
	};

	OO.inheritClass( bs.privacy.dialog.CookieConsentSettings, OO.ui.ProcessDialog );

	bs.privacy.dialog.CookieConsentSettings.static.name = 'cookieConsentSettings';

	bs.privacy.dialog.CookieConsentSettings.static.title = mw.message( 'bs-privacy-cookie-consent-mw-provider-settings-dialog-title' ).text();

	bs.privacy.dialog.CookieConsentSettings.static.actions = [
		{
			action: 'save',
			label: mw.message( 'bs-privacy-cookie-consent-mw-provider-settings-dialog-save' ).text(),
			flags: [ 'primary', 'progressive' ]
		},
		{
			label: mw.message( 'cancel' ).text(),
			flags: [ 'safe', 'close' ]
		}

	];

	bs.privacy.dialog.CookieConsentSettings.prototype.getActionProcess = function ( action ) {
		const me = this;

		if ( action === 'save' ) {
			return new OO.ui.Process( () => {
				const results = [];

				for ( const groupName in me.switches ) {
					const sw = me.switches[ groupName ];

					if ( sw.getValue() === true ) {
						results.push( groupName );
					}
				}

				return me.close( { action: action, results: results } );
			} );
		}

		return bs.privacy.dialog.CookieConsentSettings.super.prototype.getActionProcess.call( this, action );
	};

	bs.privacy.dialog.CookieConsentSettings.prototype.initialize = function () {
		bs.privacy.dialog.CookieConsentSettings.super.prototype.initialize.call( this );

		const content = [];
		this.switches = {};
		for ( const groupName in this.groups ) {
			if ( !this.groups.hasOwnProperty( groupName ) ) {
				continue;
			}
			const groupSettings = this.groups[ groupName ];

			let label = groupSettings.label;
			if ( mw.message( groupSettings.label ).exists() ) { // eslint-disable-line mediawiki/msg-doc
				label = mw.message( groupSettings.label ).text(); // eslint-disable-line mediawiki/msg-doc
			}
			let desc = groupSettings.desc;
			if ( mw.message( groupSettings.desc ).exists() ) { // eslint-disable-line mediawiki/msg-doc
				desc = mw.message( groupSettings.desc ).text(); // eslint-disable-line mediawiki/msg-doc
			}

			let value = true;
			if ( groupName in this.values ) {
				value = this.values[ groupName ];
			} else {
				if ( groupSettings.type === 'opt-in' ) {
					value = false;
				}
			}
			if ( groupSettings.type === 'always-on' ) {
				value = true;
			}
			const toggle = new OO.ui.ToggleSwitchWidget( {
				value: value,
				disabled: groupSettings.type === 'always-on'
			} );

			this.switches[ groupName ] = toggle;

			const field = new OO.ui.FieldLayout( toggle, {
				align: 'left',
				helpInline: false,
				label: label,
				$overlay: this.$overlay,
				help: new OO.ui.HtmlSnippet( desc )
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

	bs.privacy.dialog.CookieConsentSettings.prototype.getBodyHeight = function () {
		return this.$body[ 0 ].scrollHeight + 20;
	};

}( mediaWiki, blueSpice ) );

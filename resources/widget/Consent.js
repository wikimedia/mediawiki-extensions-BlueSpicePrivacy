( function ( mw, $, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.widget = bs.privacy.widget || {};

	bs.privacy.widget.Consent = function ( cfg ) {
		cfg = cfg || {};

		cfg.title = cfg.title || mw.message( 'bs-privacy-consent-layout-label' ).text();
		cfg.subtitle = cfg.subtitle || mw.message( 'bs-privacy-consent-layout-help' ).text();
		bs.privacy.widget.Consent.parent.call( this, cfg );

		this.cookieConsentProvider = null;
		if ( cfg.cookieConsentProvider ) {
			const func = bs.privacy.util.funcFromCallback( cfg.cookieConsentProvider.class );
			this.cookieConsentProvider = new func( cfg.cookieConsentProvider.config ); // eslint-disable-line new-cap
		}
	};

	OO.inheritClass( bs.privacy.widget.Consent, bs.privacy.widget.Privacy );

	bs.privacy.widget.Consent.prototype.makeForm = function () {
		this.saveButton = new OO.ui.ButtonWidget( {
			label: mw.message( 'bs-privacy-consent-save-button' ).text(),
			flags: [
				'primary',
				'progressive'
			]
		} );
		this.saveButton.on( 'click', this.saveSettings.bind( this ) );

		this.consentInputs = {};
		this.getOptions().done( ( response ) => {
			if ( response.success === 1 ) {
				this.form = new OO.ui.FieldsetLayout();

				for ( const name in response.data.consents ) {
					const data = response.data.consents[ name ];

					const check = new OO.ui.CheckboxInputWidget( {
						selected: parseInt( data.value ) === 1
					} );
					this.consentInputs[ name ] = check;

					this.form.addItems( [
						// Html snippets - not particularly cool
						new OO.ui.FieldLayout( check, {
							align: 'inline',
							label: new OO.ui.HtmlSnippet( data.label ),
							help: new OO.ui.HtmlSnippet( data.help )
						} )
					] );
				}

				if ( this.cookieConsentProvider ) {
					this.form.addItems( [
						this.cookieConsentProvider.getSettingsWidget()
					] );
				}

				this.form.addItems( [ this.saveButton ] );

				this.layout.addItems( [ this.form ] );
			} else {
				this.displayError( mw.message( 'bs-privacy-consent-get-options-fail' ).text() );
			}
		} ).fail( () => {
			this.displayError( mw.message( 'bs-privacy-consent-get-options-fail' ).text() );
		} );
	};

	bs.privacy.widget.Consent.prototype.saveSettings = function () {
		const data = {};
		for ( const name in this.consentInputs ) {
			const widget = this.consentInputs[ name ];
			data[ name ] = widget.isSelected();
		}

		this.makeApiCall( {
			func: 'setConsent',
			data: JSON.stringify( { consents: data } )
		} ).done( ( response ) => {
			if ( response.success === 1 ) {
				return this.displaySuccess( mw.message( 'bs-privacy-consent-save-success' ).text() );
			}
			this.displayError( mw.message( 'bs-privacy-consent-save-fail' ).text() );
		} ).fail( () => {
			this.displayError( mw.message( 'bs-privacy-consent-save-fail' ).text() );
		} );
	};

	bs.privacy.widget.Consent.prototype.getOptions = function () {
		return this.makeApiCall( { func: 'getConsent' } );
	};

	bs.privacy.widget.Consent.prototype.makeApiCall = function ( data ) {
		return bs.privacy.widget.Consent.parent.prototype.makeApiCall.apply( this, [ 'consent', data ] );
	};
}( mediaWiki, jQuery, blueSpice ) );

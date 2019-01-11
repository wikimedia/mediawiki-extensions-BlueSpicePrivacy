( function( mw, $, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.widget = bs.privacy.widget || {};

	bs.privacy.widget.Consent = function( cfg ) {
		cfg = cfg || {};

		cfg.title = cfg.title || mw.message( 'bs-privacy-consent-layout-label' ).text();
		cfg.subtitle = cfg.subtitle || mw.message( 'bs-privacy-consent-layout-help' ).text();
		bs.privacy.widget.Consent.parent.call( this, cfg );
	};

	OO.inheritClass( bs.privacy.widget.Consent, bs.privacy.widget.Privacy );

	bs.privacy.widget.Consent.prototype.makeForm = function() {
		this.saveButton = new OO.ui.ButtonWidget( {
			label: mw.message( 'bs-privacy-consent-save-button' ).text(),
			flags: [
				'primary',
				'progressive'
			]
		} );
		this.saveButton.on( 'click', this.saveSettings.bind( this ) );

		this.consentInputs = {};
		this.getOptions().done( function( response ) {
			if( response.success === 1 ) {
				this.form = new OO.ui.FieldsetLayout();

				for( var name in response.data.consents ) {
					var data = response.data.consents[name];

					var check = new OO.ui.CheckboxInputWidget( {
						selected: parseInt( data.value ) === 1
					} );
					this.consentInputs[name] = check;

					this.form.addItems( [
						// Html snippets - not particularly cool
						new OO.ui.FieldLayout( check, {
							align: 'inline',
							label: new OO.ui.HtmlSnippet( data.label ),
							help: new OO.ui.HtmlSnippet( data.help )
						} )
					] );
				}

				this.form.addItems( [ this.saveButton ] );

				this.layout.addItems( [ this.form ] );
			} else {
				this.displayError( mw.message( 'bs-privacy-consent-get-options-fail' ).text() );
			}
		}.bind( this ) ).fail( function( response ) {
			this.displayError( mw.message( 'bs-privacy-consent-get-options-fail' ).text() );
		}.bind( this ) );
	};

	bs.privacy.widget.Consent.prototype.saveSettings = function() {
		var data = {};
		for( var name in this.consentInputs ) {
			var widget = this.consentInputs[name];
			data[name] = widget.isSelected();
		}

		this.makeApiCall( {
			func: 'setConsent',
			data: JSON.stringify( { consents: data } )
		} ).done( function( response ) {
			if( response.success === 1 ) {
				return this.displaySuccess( mw.message( 'bs-privacy-consent-save-success' ).text() );
			}
			this.displayError( mw.message( 'bs-privacy-consent-save-fail' ).text() );
		}.bind( this ) ).fail( function( response ) {
			this.displayError( mw.message( 'bs-privacy-consent-save-fail' ).text() );
		}.bind( this ) );
	};

	bs.privacy.widget.Consent.prototype.getOptions = function() {
		return this.makeApiCall( { func: 'getConsent' } );
	};

	bs.privacy.widget.Consent.prototype.makeApiCall = function( data ) {
		return bs.privacy.widget.Anonymize.parent.prototype.makeApiCall.apply( this, [ 'consent', data ] );
	};
} )( mediaWiki, jQuery, blueSpice );
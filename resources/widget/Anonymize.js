( function( mw, $, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.widget = bs.privacy.widget || {};

	bs.privacy.widget.Anonymize = function( cfg ) {
		cfg = cfg || {};

		cfg.title = cfg.title || mw.message( 'bs-privacy-anonymization-layout-label' ).text();
		cfg.subtitle = cfg.subtitle || mw.message( 'bs-privacy-anonymization-layout-help' ).text();
		bs.privacy.widget.Anonymize.parent.call( this, cfg );

		this.currentUsername = mw.config.get( 'wgUserName' );
		this.newUsername = '';
		this.typingTimer;
		this.typingDoneInterval = 500;
	};

	OO.inheritClass( bs.privacy.widget.Anonymize, bs.privacy.widget.PrivacyRequestable );

	bs.privacy.widget.Anonymize.prototype.makeRequestForm = function() {
		this.makeDirectForm();

		this.confirmButton = new OO.ui.ButtonWidget( {
			label: mw.message( 'bs-privacy-anonymization-request-button' ).text(),
			flags: [
				'primary',
				'progressive'
			]
		} );
		this.confirmButton.on( 'click', this.makeRequest.bind( this ) );
		this.form.$element.find( '.oo-ui-buttonWidget' ).replaceWith( this.confirmButton.$element );

		this.layout.addItems( this.form );
	};

	bs.privacy.widget.Anonymize.prototype.makePendingForm = function() {
		bs.privacy.widget.Anonymize.parent.prototype.makePendingForm.apply( this, [
			'bs-privacy-anonymization-request-pending'
		] );
	};

	bs.privacy.widget.Anonymize.prototype.makeDeniedForm = function( comment ) {
		bs.privacy.widget.Anonymize.parent.prototype.makeDeniedForm.call(
			this,
			'bs-privacy-anonymization-request-denied',
			false,
			comment
		);
	};

	bs.privacy.widget.Anonymize.prototype.makeRequest = function() {
		this.setLoading( true );
		this.makeApiCall( {
			func: 'submitRequest',
			data: JSON.stringify( {
				oldUsername: this.currentUsername,
				username: this.newUsername
			} )
		} ).done( function( response ) {
			if ( response.success === 1 ) {
				this.setLoading( false );
				this.form.$element.remove();
				this.makePendingForm();
				return;
			}
			this.displayError( mw.message( "bs-privacy-request-failed" ).text() );
		}.bind( this ) ).fail( function() {
			this.displayError( mw.message( "bs-privacy-request-failed" ).text() );
		}.bind( this ) );
	};

	bs.privacy.widget.Anonymize.prototype.makeDirectForm = function() {
		this.newNameInput = new OO.ui.TextInputWidget( {
			maxLength: 255
		} );

		this.confirmButton = new OO.ui.ButtonWidget( {
			label: mw.message( 'bs-privacy-anonymization-confirm-button-label' ).text(),
			flags: [
				'progressive',
				'primary'
			]
		} );
		this.confirmButton.on( 'click', this.anonymize.bind( this ) );

		this.form = new OO.ui.ActionFieldLayout( this.newNameInput, this.confirmButton, {
			align: 'top',
			label: mw.message( 'bs-privacy-anonymization-new-username-label' ).text()
		} );

		this.getUsername().done( function( response ) {
			if( response.success === 1 ) {
				// Random username retrieved
				this.newNameInput.setValue( response.data.username );
				this.newUsername = response.data.username;
				this.newNameInput.on( 'change', this.onNameInputChange.bind( this ) );
				this.layout.addItems( [ this.form ] );
			} else {
				this.displayError( mw.message( 'bs-privacy-anonymization-error-retrieving-name' ).text() );
			}
		}.bind( this ) ).fail( function( response ) {
			this.displayError( mw.message( 'bs-privacy-anonymization-error-retrieving-name' ).text() );
		}.bind( this ) );
	};

	bs.privacy.widget.Anonymize.prototype.getUsername = function() {
		return this.makeApiCall( { func: 'getUsername' } );
	};

	bs.privacy.widget.Anonymize.prototype.makeApiCall = function( data ) {
		return bs.privacy.widget.Anonymize.parent.prototype.makeApiCall.apply( this, [ 'anonymization', data ] );
	};

	bs.privacy.widget.Anonymize.prototype.displaySuccess = function( message ) {
		bs.privacy.widget.Anonymize.parent.prototype.displaySuccess.apply( this, [ message ] );

		var loginLink = mw.Title.makeTitle( -1, "Login" ).getUrl( {
			wpName: this.newUsername
		} );
		var loginButton = new OO.ui.ButtonWidget( {
			label: mw.message( 'bs-privacy-anonymize-login-button' ).text(),
			href: loginLink,
			framed: false,
			classes: [ 'bs-privacy-anonymize-login-btn' ]
		} );
		this.$element.append( loginButton.$element );
	};

	bs.privacy.widget.Anonymize.prototype.anonymize = function() {
		OO.ui.confirm( mw.message( 'bs-privacy-anonymization-final-prompt' ).text() )
			.done( function( confirmed ) {
				if( confirmed ) {
					this.doAnonymize();
				}
			}.bind( this ) );
	};

	bs.privacy.widget.Anonymize.prototype.doAnonymize = function() {
		// For sanity
		if( !this.newUsername ) {
			return this.displayError( mw.message( 'bs-privacy-anonymization-invalid-name' ).text() );
		}
		this.setLoading( true );

		this.form.$element.remove();
		this.makeApiCall( {
			func: 'anonymize',
			data: JSON.stringify( {
				oldUsername: this.currentUsername,
				username: this.newUsername
			} )
		} ).done( function( response ) {
			if ( response.success ) {
				this.displaySuccess( mw.message( 'bs-privacy-anonymization-success-anonymizing', this.newUsername ).text() );
			} else {
				this.displayError( mw.message( 'bs-privacy-anonymization-error-anonymizing' ).text() );
			}
		}.bind( this ) ).fail( function( response ) {
			this.displayError( mw.message( 'bs-privacy-anonymization-error-anonymizing' ).text() );
		}.bind( this ) );
	};

	bs.privacy.widget.Anonymize.prototype.onNameInputChange = function() {
		this.confirmButton.setDisabled( true );
		this.newUsername = '';

		clearTimeout( this.typingTimer );
		this.typingTimer = setTimeout( function() {
			this.checkUsername();
		}.bind( this ), this.typingDoneInterval );
	}

	bs.privacy.widget.Anonymize.prototype.checkUsername = function() {
		var username = this.newNameInput.getValue();
		this.$element.find( ".bs-privacy-error" ).remove();

		this.makeApiCall( {
			func: 'checkUsername',
			data: JSON.stringify( {
				username: username
			} )
		} ).done( function( response ) {
			if( response.success === 0 ) {
				return this.displayError( mw.message( 'bs-privacy-anonymization-error-check-name' ).text() );
			}
			if( response.data.exists === 1 ) {
				this.displayError( mw.message( 'bs-privacy-anonymization-username-exists' ).text() );
			} else if ( response.data.invalid === 1 ) {
				this.displayError( mw.message( 'bs-privacy-anonymization-invalid-name' ).text() );
			} else {
				this.newUsername = response.data.username;
				this.confirmButton.setDisabled( false );
			}
		}.bind( this ) ).fail( function() {
			this.displayError( mw.message( 'bs-privacy-anonymization-error-check-name' ).text() );
		}.bind( this ) );
	};

} )( mediaWiki, jQuery, blueSpice );
( function( mw, $, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.widget = bs.privacy.widget || {};

	bs.privacy.widget.PrivacyRequestable = function( cfg ) {
		cfg = cfg || {};

		bs.privacy.widget.PrivacyRequestable.parent.call( this, cfg );
		this.useRequests = !!this.$element.data( 'requestable' );
	};

	OO.inheritClass( bs.privacy.widget.PrivacyRequestable, bs.privacy.widget.Privacy );

	bs.privacy.widget.PrivacyRequestable.prototype.checkStatus = function() {
		return this.makeApiCall( { func: 'checkStatus' } );
	};

	bs.privacy.widget.PrivacyRequestable.prototype.makeForm = function() {
		if( this.useRequests ) {
			this.checkStatus().done( function( response ) {
				if ( response.success === 1 ) {
					var status = parseInt( response.data.status );
					if ( status === 0 ) {
						this.makeRequestForm();
					}  else {
						this.makeRequestStatusForm( status, response.data.comment );
					}
					return;
				}
				this.displayError( mw.message( "bs-privacy-api-error-generic" ).text() );
			}.bind( this ) ).fail( function() {
				this.displayError( mw.message( "bs-privacy-api-error-generic" ).text() );
			}.bind( this ) );
		} else {
			this.makeDirectForm();
		}
	};

	bs.privacy.widget.PrivacyRequestable.prototype.makeDirectForm = function() {
		// Stub
	};

	bs.privacy.widget.PrivacyRequestable.prototype.makeRequestForm = function() {
		// Stub
	};

	bs.privacy.widget.PrivacyRequestable.prototype.makeRequestStatusForm = function( status, comment ) {
		if ( status === 1 ) {
			return this.makePendingForm();
		} else if ( status === 2 ) {
			return this.makeDeniedForm( comment );
		}
		// Dont see a point in making approved status form, as
		// user wont be able to access their old account anymore
	};

	bs.privacy.widget.PrivacyRequestable.prototype.makePendingForm = function( label, buttonLabel ) {
		var statusLabel = new OO.ui.LabelWidget( {
			label: mw.message( label ).text()
		} );

		buttonLabel = buttonLabel || 'bs-privacy-cancel-request-button';
		var cancelButton = new OO.ui.ButtonWidget( {
			label: mw.message( buttonLabel ).text(),
			framed: false
		} );
		cancelButton.on( 'click', this.cancelRequest.bind( this ) );

		this.form = new OO.ui.HorizontalLayout( {
			items: [
				statusLabel,
				cancelButton
			]
		} );
		this.layout.addItems( [ this.form ] );
	};

	bs.privacy.widget.PrivacyRequestable.prototype.makeDeniedForm = function( label, buttonLabel, comment ) {
		var statusLabel = new OO.ui.LabelWidget( {
			label: mw.message( label ).text(),
			classes: [ 'bs-privacy-label-warning' ]
		} );
		var commentLabel = new OO.ui.LabelWidget( {
			label: mw.message( 'bs-privacy-request-denied-comment', comment ).text(),
			classes: [ 'bs-privacy-label-block' ]
		} );

		buttonLabel = buttonLabel || 'bs-privacy-acknowledge-request-button';
		var ackButton = new OO.ui.ButtonWidget( {
			label: mw.message( buttonLabel ).text(),
			framed: false
		} );
		ackButton.on( 'click', this.closeRequest.bind( this ) );

		this.form = new OO.ui.HorizontalLayout( {
			items: [
				statusLabel,
				commentLabel,
				ackButton
			]
		} );
		this.layout.addItems( [ this.form ] );
	};


	bs.privacy.widget.PrivacyRequestable.prototype.cancelRequest = function() {
		this.setLoading( true );
		this.makeApiCall( { func: 'cancelRequest' } ).done( function( response ) {
			if ( response.success === 1 ) {
				this.setLoading( false );
				this.form.$element.remove();
				this.makeRequestForm();
				return;
			}
			this.displayError( mw.message( "bs-privacy-request-cancel-failed" ).text() );
		}.bind( this ) ).fail( function() {
			this.displayError( mw.message( "bs-privacy-request-cancel-failed" ).text() );
		}.bind( this ) );
	};

	bs.privacy.widget.PrivacyRequestable.prototype.closeRequest = function() {
		this.setLoading( true );
		this.makeApiCall( { func: 'closeRequest' } ).done( function( response ) {
			if ( response.success === 1 ) {
				this.setLoading( false );
				this.form.$element.remove();
				this.makeRequestForm();
				return;
			}
			this.displayError( mw.message( "bs-privacy-request-cancel-failed" ).text() );
		}.bind( this ) ).fail( function() {
			this.displayError( mw.message( "bs-privacy-request-cancel-failed" ).text() );
		}.bind( this ) );
	};

} )( mediaWiki, jQuery, blueSpice );
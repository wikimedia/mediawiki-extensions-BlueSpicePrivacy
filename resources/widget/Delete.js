( function( mw, $, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.widget = bs.privacy.widget || {};

	bs.privacy.widget.Delete = function( cfg ) {
		cfg = cfg || {};

		cfg.title = cfg.title || mw.message( 'bs-privacy-deletion-layout-label' ).text();
		cfg.subtitle = cfg.subtitle || mw.message( 'bs-privacy-deletion-layout-help' ).text();
		bs.privacy.widget.Delete.parent.call( this, cfg );

		this.redirectPageName = this.$element.data( 'redirect-page' ) || mw.message( 'mainpage' ).text();
	};

	OO.inheritClass( bs.privacy.widget.Delete, bs.privacy.widget.PrivacyRequestable );

	bs.privacy.widget.Delete.prototype.makeApiCall = function( data ) {
		return bs.privacy.widget.Delete.parent.prototype.makeApiCall.apply( this, [ 'deletion', data ] );
	};

	bs.privacy.widget.Delete.prototype.makeRequestForm = function() {
		this.commentControl = new OO.ui.TextInputWidget( {
			maxLength: 255
		} );
		this.deleteButton = new OO.ui.ButtonWidget( {
			label: mw.message( 'bs-privacy-deletion-request-deletion-button' ).text(),
			flags: [
				'primary',
				'destructive'
			]
		} );
		this.deleteButton.on( 'click', this.makeRequest.bind( this ) );

		this.form = new OO.ui.ActionFieldLayout( this.commentControl, this.deleteButton, {
			align: 'top',
			label: mw.message( 'bs-privacy-deletion-submit-request-label' ).text()
		} );

		this.layout.addItems( this.form );
	};

	bs.privacy.widget.Delete.prototype.makePendingForm = function() {
		bs.privacy.widget.Delete.parent.prototype.makePendingForm.apply( this, [
			'bs-privacy-deletion-request-pending'
		] );
	};

	bs.privacy.widget.Delete.prototype.makeDeniedForm = function( comment ) {
		bs.privacy.widget.Delete.parent.prototype.makeDeniedForm.apply( this, [
			'bs-privacy-deletion-request-denied',
			false,
			comment
		] );
	};

	bs.privacy.widget.Delete.prototype.makeRequest = function() {
		this.setLoading( true );
		this.makeApiCall( {
			func: 'submitRequest',
			data: JSON.stringify( {
				comment: this.commentControl.getValue(),
				username: mw.config.get( 'wgUserName' )
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

	// Direct action
	bs.privacy.widget.Delete.prototype.makeDirectForm = function() {
		var deleteButton = new OO.ui.ButtonWidget( {
			label: mw.message( 'bs-privacy-deletion-button' ).text(),
			flags: [
				'primary',
				'destructive'
			]
		} );
		deleteButton.on( 'click', this.deleteAccount.bind( this ) );

		this.form = new OO.ui.HorizontalLayout( {
			items: [
				deleteButton
			]
		} );

		this.layout.addItems( this.form );
	};

	bs.privacy.widget.Delete.prototype.deleteAccount = function() {
		OO.ui.confirm( mw.message( 'bs-privacy-deletion-final-prompt' ).text() )
			.done( function( confirmed ) {
				if( confirmed ) {
					this.doDelete();
				}
			}.bind( this ) );
	};

	bs.privacy.widget.Delete.prototype.doDelete = function() {
		this.setLoading( true );

		this.makeApiCall( {
			func: 'delete',
			data: JSON.stringify( {
				username: mw.config.get( 'wgUserName' )
			} )
		} ).done( function( response ) {
			if ( response.success ) {
				var redirectPage = mw.Title.newFromText( this.redirectPageName );
				window.location.href = redirectPage.getUrl();
				return;
			} else {
				this.displayError( mw.message( 'bs-privacy-delete-error-deleting' ).text() );
			}
		}.bind( this ) ).fail( function( response ) {
			this.displayError( mw.message( 'bs-privacy-delete-error-deleting' ).text() );
		}.bind( this ) );
	};
} )( mediaWiki, jQuery, blueSpice );
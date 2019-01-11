( function( mw, $, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.widget = bs.privacy.widget || {};

	bs.privacy.widget.RequestManager = function( cfg ) {
		cfg.title = 'bs-privacy-admin-request-manager-title';
		cfg.subtitle = 'bs-privacy-admin-request-manager-help';
		
		this.enabled = mw.config.get( 'bsPrivacyEnableRequests' );

		bs.privacy.widget.RequestManager.parent.call( this, cfg );
	};

	OO.inheritClass( bs.privacy.widget.RequestManager, bs.privacy.widget.AdminWidget );

	bs.privacy.widget.RequestManager.prototype.makeForm = function() {
		if( this.enabled === false ) {
			return this.displayError( mw.message( 'bs-privacy-admin-requests-disabled' ).text() );
		}
		var $gridContainer = $( '<div>' ).attr( 'id', 'bs-privacy-extjs-requests' );
		this.$element.append( $gridContainer );

		this.grid = Ext.create( 'BS.Privacy.grid.Requests', {
			requestManager: this,
			renderTo: 'bs-privacy-extjs-requests'
		} );
	};

	bs.privacy.widget.RequestManager.prototype.onApprove = function( grid, rowIndex, colIndex ) {
		OO.ui.confirm( mw.message( 'bs-privacy-admin-approve-final-prompt' ).text() )
			.done( function( confirmed ) {
				if( confirmed ) {
					var rec = grid.getStore().getAt(rowIndex);
					var requestId = rec.get( 'requestId' );
					var module = rec.get( 'module' );

					this.executeRequestAction( requestId, 'approveRequest', module );
				}
			}.bind( this ) );
	};

	bs.privacy.widget.RequestManager.prototype.onDeny = function( grid, rowIndex, colIndex ) {
		OO.ui.prompt( mw.message( 'bs-privacy-admin-deny-prompt' ).text(), {
			textInput: {
				placeholder: mw.message( 'bs-privacy-admin-deny-comment-placeholder' ).text()
			}
		} ).done( function ( result ) {
			if ( result !== null ) {
				var rec = grid.getStore().getAt(rowIndex);
				var requestId = rec.get( 'requestId' );
				var module = rec.get( 'module' );

				this.executeRequestAction( requestId, 'denyRequest', module, { comment: result } );
			}
		}.bind( this ) );

	};

	bs.privacy.widget.RequestManager.prototype.executeRequestAction = function( requestId, action, module, data ) {
		data = data || {};

		var apiData = {
			action: 'bs-privacy',
			module: module,
			func: action,
			data: JSON.stringify( $.extend( {
				requestId: requestId
			}, data ) )
		};

		this.api.post( apiData ).done( function( response ) {
			if ( response.success === 1 ) {
				this.$element.find( ".bs-privacy-error" ).remove();
				return this.grid.getStore().reload();
			}
			this.displayError( 'bs-privacy-admin-request-action-failed' );
		}.bind( this ) ).fail( function() {
			this.displayError( 'bs-privacy-admin-request-action-failed' );
		}.bind( this ) );
	};

	bs.privacy.widget.RequestManager.prototype.displayError = function( message ) {
		this.$element.find( ".bs-privacy-error" ).remove();

		this.$element.append( new OO.ui.LabelWidget( {
			label: message || mw.message( 'bs-privacy-admin-request-action-failed' ).text(),
			classes:  [ "bs-privacy-error" ]
		} ).$element );
	};
} )( mediaWiki, jQuery, blueSpice );
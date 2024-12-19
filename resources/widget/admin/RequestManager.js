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

	this.store = new OOJSPlus.ui.data.store.RemoteStore( {
		action: 'bs-privacy-get-requests',
		pageSize: 25,
		sorter: {
			daysAgo: { direction: 'desc' }
		},
		filter: {
			status: {
				operator: 'eq',
				value: 1,
				type: 'number'
			},
			isOpen: {
				operator: 'eq',
				value: true,
				type: 'boolean'
			}
		}
	} );

	var grid = new OOJSPlus.ui.data.GridWidget( {
		columns: {
			userName: {
				headerText: mw.message( 'bs-privacy-admin-request-grid-column-user' ).text(),
				filter: { type: 'user' },
				sortable: true
			},
			module: {
				headerText: mw.message( 'bs-privacy-admin-request-grid-column-action' ).text(),
				width: 150,
				valueParser: function( value ) {
					return mw.msg( 'bs-privacy-module-name-' + value );
				},
				filter: {
					type: 'list',
					list: [
						{ data: 'anonymization', label: mw.msg( 'bs-privacy-module-name-anonymization' ) },
						{ data: 'deletion', label: mw.msg( 'bs-privacy-module-name-deletion' ) },
					]
				},
				sortable: true
			},
			timestampWithDaysAgo: {
				headerText: mw.message( 'bs-privacy-admin-request-grid-column-timestamp' ).text(),
				width: 280,
				valueRenderer: function( value, row ) {
					this.renderTS( value, row );
				}.bind( this )
			},
			comment: {
				headerText: mw.message( 'bs-privacy-admin-request-grid-column-comment' ).text()
			},
			approveAction: {
				type: 'action',
				label: mw.message( 'bs-privacy-admin-request-grid-action-approve' ).text(),
				actionId: 'approve',
				icon: 'check'
			},
			denyAction: {
				type: 'action',
				label: mw.message( 'bs-privacy-admin-request-grid-action-deny' ).text(),
				actionId: 'deny',
				icon: 'close'
			}
		},
		store: this.store
	} );

	grid.connect( this, {
		action: 'onGridAction'
	} );

	this.layout.$element.append( grid.$element );
};

bs.privacy.widget.RequestManager.prototype.renderTS = function( value, row ) {
	// Reached or passed the deadline
	var deadline = mw.config.get( 'bsPrivacyRequestDeadline' );
	if( record.get( 'daysAgo' ) >= deadline ) {
		return new OO.ui.HtmlSnippet( $('<div>').append( $('<span>' )
			.addClass( 'bs-privacy-request-overdue' ).html( value ) )
			.html() );
	}

	// Near a deadline
	var untilDeadline = deadline - record.get( 'daysAgo' );
	if( untilDeadline < 3 ) {
		return new OO.ui.HtmlSnippet( $('<div>').append( $('<span>' )
			.addClass( 'bs-privacy-request-near' ).html( value ) )
			.html() );
	}

	// Far from deadline
	return new OO.ui.HtmlSnippet( $('<div>').append( $('<span>' ).html( value ) ).html() );
};

bs.privacy.widget.RequestManager.prototype.onGridAction = function( action, row ) {
	if( action === 'approve' ) {
		this.onApprove( row );
	} else if( action === 'deny' ) {
		this.onDeny( row );
	}
};

bs.privacy.widget.RequestManager.prototype.onApprove = function( row ) {
	OO.ui.confirm( mw.message( 'bs-privacy-admin-approve-final-prompt' ).text() )
		.done( function( confirmed ) {
			if( confirmed ) {
				this.executeRequestAction( row.requestId, 'approveRequest', row.module );
			}
		}.bind( this ) );
};

bs.privacy.widget.RequestManager.prototype.onDeny = function( row ) {
	OO.ui.prompt( mw.message( 'bs-privacy-admin-deny-prompt' ).text(), {
		textInput: {
			placeholder: mw.message( 'bs-privacy-admin-deny-comment-placeholder' ).text()
		}
	} ).done( function ( result ) {
		if ( result !== null ) {
			this.executeRequestAction( row.requestId, 'denyRequest', row.module, { comment: result } );
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
			return this.store.reload();
		}
		this.displayError();
	}.bind( this ) ).fail( function() {
		this.displayError();
	}.bind( this ) );
};

bs.privacy.widget.RequestManager.prototype.displayError = function( message ) {
	this.$element.find( ".bs-privacy-error" ).remove();

	this.$element.append( new OO.ui.LabelWidget( {
		label: message || mw.message( 'bs-privacy-admin-request-action-failed' ).text(),
		classes:  [ "bs-privacy-error" ]
	} ).$element );
};

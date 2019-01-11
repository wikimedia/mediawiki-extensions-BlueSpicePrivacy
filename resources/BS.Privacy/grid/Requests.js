Ext.define( "BS.Privacy.grid.Requests", {
	extend: 'Ext.grid.Panel',
	requires: [ 'BS.Privacy.store.Request' ],
	requestManager: null,

	initComponent: function() {
		this.store = new BS.Privacy.store.Request();

		this.columns = [
			{
				text: mw.message( 'bs-privacy-admin-request-grid-column-user' ).text(),
				dataIndex: 'userName'
			},
			{
				text: mw.message( 'bs-privacy-admin-request-grid-column-action' ).text(),
				width: 150,
				dataIndex: 'module'
			},
			{
				text: mw.message( 'bs-privacy-admin-request-grid-column-timestamp' ).text(),
				width: 280,
				dataIndex: 'timestampWithDaysAgo',
				renderer: this.renderTS
			},
			{
				text: mw.message( 'bs-privacy-admin-request-grid-column-comment' ).text(),
				flex: 1,
				dataIndex: 'comment'
			},
			{
				xtype:'actioncolumn',
				width:50,
				items: [{
					iconCls: 'icon-approve',
					tooltip: mw.message( 'bs-privacy-admin-request-grid-action-approve' ).text(),
					handler: this.requestManager.onApprove.bind( this.requestManager )
				},{
					iconCls: 'icon-deny',
					tooltip: mw.message( 'bs-privacy-admin-request-grid-action-deny' ).text(),
					handler: this.requestManager.onDeny.bind( this.requestManager )
				}]
			}
		];

		this.dockedItems = [ {
			xtype: 'pagingtoolbar',
			store: this.store,
			dock: 'bottom',
			displayInfo: true
		} ];

		return this.callParent( arguments );
	},

	renderTS: function( value, meta, record ) {
		// Reached or passed the deadline
		var deadline = mw.config.get( 'bsPrivacyRequestDeadline' );
		if( record.get( 'daysAgo' ) >= deadline ) {
			return $('<div>').append( $('<span>' )
				.addClass( 'bs-privacy-request-overdue' ).html( value ) )
				.html();
		}

		// Near a deadline
		var untilDeadline = deadline - record.get( 'daysAgo' );
		if( untilDeadline < 3 ) {
			return $('<div>').append( $('<span>' )
				.addClass( 'bs-privacy-request-near' ).html( value ) )
				.html();
		}

		// Far from deadline
		return $('<div>').append( $('<span>' ).html( value ) ).html();
	}
} );
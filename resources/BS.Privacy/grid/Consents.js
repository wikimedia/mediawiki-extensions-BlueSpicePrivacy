Ext.define( 'BS.Privacy.grid.Consents', {
	extend: 'Ext.grid.Panel',
	requires: [ 'BS.Privacy.store.Consent' ],
	consentTypes: {},

	initComponent: function() {
		this.store = new BS.Privacy.store.Consent();

		this.columns = [
			{
				text: mw.message( 'bs-privacy-admin-consent-grid-column-user' ).text(),
				dataIndex: 'userName',
				width: 'flex',
				minWidth: 400,
				filter: {
					type: 'string'
				}
			}
		];

		for( var name in this.consentTypes ) {
			var msg = mw.message( 'bs-privacy-grid-column-' + name );
			var header = name;
			if( msg.exists() ) {
				header = msg.text();
			}

			this.columns.push( {
				text: header,
				width: 150,
				tdCls: 'bs-privacy-bool-column',
				dataIndex: name,
				renderer: this.renderBool,
				filter: {
					type: 'boolean'
				}
			} );
		}

		this.dockedItems = [ {
			xtype: 'pagingtoolbar',
			store: this.store,
			dock: 'bottom',
			displayInfo: true
		} ];

		this.plugins = 'gridfilters';

		return this.callParent( arguments );
	},

	renderBool: function( value ) {
		if( value ) {
			return $( "<div>" ).append( $( "<span>" ).addClass( 'bs-privacy-admin-consent-column-bool-true' ) ).html();
		}
		return $( "<div>" ).append( $( "<span>" ).addClass( 'bs-privacy-admin-consent-column-bool-false' ) ).html();
	}
} );
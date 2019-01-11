Ext.define( 'BS.Privacy.store.Consent', {
	extend: 'BS.store.BSApi',
	apiAction: 'bs-privacy-get-all-consents',
	sorters: [ {
		property: 'userName',
		direction: 'ASC'
	} ],
	proxy:{
		extraParams: {
			limit: 25
		}
	},
	pageSize: 25
} );
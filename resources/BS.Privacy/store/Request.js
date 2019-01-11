Ext.define( 'BS.Privacy.store.Request', {
	extend: "BS.store.BSApi",
	requires: [ 'BS.Privacy.model.Request' ],

	apiAction: 'bs-privacy-get-requests',
	model: 'BS.Privacy.model.Request',
	sorters: [{
		property: 'daysAgo',
		direction: 'DESC'
	}],
	filters: [ {
		property: 'status',
		type: 'numeric',
		comparison: 'eq',
		value: 1
	}, {
		property: 'isOpen',
		type: 'boolean',
		comparison: 'eq',
		value: true
	} ],
	proxy:{
		extraParams: {
			limit: 25
		}
	},
	pageSize: 25
} );
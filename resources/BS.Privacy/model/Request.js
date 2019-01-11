Ext.define( 'BS.Privacy.model.Request', {
	extend: 'Ext.data.Model',
	fields: [
		{ name: 'requestId', type: 'int' },
		{ name: 'userName', type: 'string' },
		{ name: 'module', type: 'string' },
		{ name: 'timestampWithDaysAgo', type: 'string' },
		{ name: 'comment', type: 'string' },
	]
} );
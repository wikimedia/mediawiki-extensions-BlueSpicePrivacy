window.bs.privacy = bs.privacy || {};
bs.privacy.widget = bs.privacy.widget || {};

bs.privacy.widget.ConsentOverview = function( cfg ) {
	cfg.title = 'bs-privacy-admin-consent-overview-title';
	cfg.subtitle = 'bs-privacy-admin-consent-overview-help';

	bs.privacy.widget.ConsentOverview.parent.call( this, cfg );

	this.consentTypes = cfg.consentTypes;
};

OO.inheritClass( bs.privacy.widget.ConsentOverview, bs.privacy.widget.AdminWidget );

bs.privacy.widget.ConsentOverview.prototype.makeForm = function() {

	var store = new OOJSPlus.ui.data.store.RemoteStore( {
		action: 'bs-privacy-get-all-consents',
		pageSize: 25,
		sorter: {
			userName: { direction: 'asc' }
		}
	} );
	var columns = {
		userName: {
			type: 'text',
			headerText: mw.message( 'bs-privacy-admin-consent-grid-column-user' ).text(),
			filter: { type: 'user' },
			sortable: true
		}
	};

	for ( var name in this.consentTypes ) {
		var msg = mw.message( 'bs-privacy-consent-type-' + name + '-short' );
		var header = name;
		if( msg.exists() ) {
			header = msg.text();
		}

		columns[name] = {
			type: 'boolean',
			headerText: header,
			filter: {
				type: 'boolean'
			},
			sortable: true,
			width: 100
		};
	}
	var grid = new OOJSPlus.ui.data.GridWidget( {
		store: store,
		columns: columns,
		pageSize: 25
	} );

	this.layout.$element.append( grid.$element );
};

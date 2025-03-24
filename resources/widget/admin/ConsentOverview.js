window.bs.privacy = bs.privacy || {};
bs.privacy.widget = bs.privacy.widget || {};

bs.privacy.widget.ConsentOverview = function ( cfg ) {
	cfg.title = 'bs-privacy-admin-consent-overview-title';
	cfg.subtitle = 'bs-privacy-admin-consent-overview-help';

	bs.privacy.widget.ConsentOverview.parent.call( this, cfg );

	this.consentTypes = cfg.consentTypes;
};

OO.inheritClass( bs.privacy.widget.ConsentOverview, bs.privacy.widget.AdminWidget );

bs.privacy.widget.ConsentOverview.prototype.makeForm = function () {

	const store = new OOJSPlus.ui.data.store.RemoteRestStore( {
		path: 'bs/privacy/v1/all-consents',
		pageSize: 25,
		sorter: {
			user_name: { direction: 'asc' } // eslint-disable-line camelcase
		}
	} );
	const columns = {
		user_name: { // eslint-disable-line camelcase
			type: 'user',
			headerText: mw.message( 'bs-privacy-admin-consent-grid-column-user' ).text(),
			filter: { type: 'user' },
			showImage: true,
			sortable: true
		}
	};

	for ( const name in this.consentTypes ) {
		// The following messages are used here:
		// * bs-privacy-consent-type-privacy-policy-short
		// * bs-privacy-consent-type-terms-of-service-short
		const msg = mw.message( 'bs-privacy-consent-type-' + name + '-short' );
		let header = name;
		if ( msg.exists() ) {
			header = msg.text();
		}

		columns[ name ] = {
			type: 'boolean',
			headerText: header,
			filter: {
				type: 'boolean'
			},
			sortable: true,
			width: 100
		};
	}
	const grid = new OOJSPlus.ui.data.GridWidget( {
		store: store,
		columns: columns,
		pageSize: 25
	} );

	this.layout.$element.append( grid.$element );
};

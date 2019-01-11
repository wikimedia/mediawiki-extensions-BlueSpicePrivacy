( function( mw, $, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.widget = bs.privacy.widget || {};

	bs.privacy.widget.ConsentOverview = function( cfg ) {
		cfg.title = 'bs-privacy-admin-consent-overview-title';
		cfg.subtitle = 'bs-privacy-admin-consent-overview-help';

		bs.privacy.widget.ConsentOverview.parent.call( this, cfg );
	};

	OO.inheritClass( bs.privacy.widget.ConsentOverview, bs.privacy.widget.AdminWidget );

	bs.privacy.widget.ConsentOverview.prototype.makeForm = function() {
		var $gridContainer = $( '<div>' ).attr( 'id', 'bs-privacy-extjs-consents' );
		this.$element.append( $gridContainer );

		Ext.create( 'BS.Privacy.grid.Consents', {
			consentTypes: mw.config.get( 'bsPrivacyConsentTypes' ),
			renderTo: 'bs-privacy-extjs-consents'
		} );
	};

} )( mediaWiki, jQuery, blueSpice );
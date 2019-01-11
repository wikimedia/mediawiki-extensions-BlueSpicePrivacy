( function( mw, $, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.widget = bs.privacy.widget || {};

	bs.privacy.widget.AdminWidget = function( cfg ) {
		this.$element = cfg.$element || $( '<div>' );

		this.title = cfg.title;
		this.subtitle = cfg.subtitle;

		this.api = new mw.Api();
		bs.privacy.widget.AdminWidget.parent.call( this, cfg );

		this.$element.addClass( 'bs-privacy-admin-widget' );
	};

	OO.inheritClass( bs.privacy.widget.AdminWidget, OO.ui.Widget );

	bs.privacy.widget.AdminWidget.prototype.init = function() {
		var helpLabel = new OO.ui.LabelWidget( {
			label: mw.message( this.subtitle ).text(),
			classes: [ "bs-privacy-subtitle" ]
		} );

		this.layout = new OO.ui.FieldsetLayout( {
			label: mw.message( this.title ).text(),
			items: [
				helpLabel
			]
		} );

		this.$element.append( this.layout.$element );

		this.makeForm();
	};

	bs.privacy.widget.AdminWidget.prototype.makeForm = function() {
		// Stub
	};
} )( mediaWiki, jQuery, blueSpice );
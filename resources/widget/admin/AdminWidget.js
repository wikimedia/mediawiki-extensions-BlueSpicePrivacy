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
	this.layout = new OO.ui.FieldsetLayout( {
		items: [
			new OOJSPlus.ui.widget.HeadingLabel( {
				label: mw.message( this.title ).text(),
				subtitle: mw.message( this.subtitle ).text()
			} )
		]
	} );

	this.$element.append( this.layout.$element );

	this.makeForm();
};

bs.privacy.widget.AdminWidget.prototype.makeForm = function() {
	// Stub
};

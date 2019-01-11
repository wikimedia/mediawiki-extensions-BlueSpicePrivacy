( function( mw, $, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.widget = bs.privacy.widget || {};

	bs.privacy.widget.Privacy = function( cfg ) {
		cfg = cfg || {};

		this.api = new mw.Api();
		this.$element = cfg.$element || $( '<div>' );
		this.title = cfg.title;
		this.subtitle = cfg.subtitle;

		bs.privacy.widget.Privacy.parent.call( this, cfg );
	};

	OO.inheritClass( bs.privacy.widget.Privacy, OO.ui.Widget );

	bs.privacy.widget.Privacy.prototype.init = function() {
		this.helpLabel = new OO.ui.LabelWidget( {
			label: this.subtitle,
			classes: [ "bs-privacy-subtitle" ]
		} );

		this.layout = new OO.ui.FieldsetLayout( {
			label: this.title,
			items: [
				this.helpLabel
			]
		} );

		this.makeForm();

		this.$element.append( this.layout.$element );
	};

	bs.privacy.widget.Privacy.prototype.makeForm = function() {
		// Stub
	};

	bs.privacy.widget.Privacy.prototype.makeApiCall = function( module, data ) {
		data = $.extend( {
			module: module,
			action: 'bs-privacy'
		}, data );
		return this.api.post( data );
	};

	bs.privacy.widget.Privacy.prototype.displayMessage = function( classes, message ) {
		if( $.isArray( classes ) === false ) {
			classes = [ classes ];
		}

		this.setLoading( false );
		for( var idx in classes ) {
			this.$element.find( "." + classes[idx] ).remove();
		}

		this.$element.append( new OO.ui.LabelWidget( {
			label: message,
			classes: classes
		} ).$element );
	};

	bs.privacy.widget.Privacy.prototype.setLoading = function( value ) {
		if( !this.loading ) {
			this.loading = new OO.ui.ProgressBarWidget( {
				progress: false
			} );
			this.$element.append( this.loading.$element );
			this.loading.$element.hide();
		}

		if( value ) {
			this.loading.$element.show();
			this.form.$element.hide();
		} else {
			this.loading.$element.hide();
			this.form.$element.show();
		}
	};

	bs.privacy.widget.Privacy.prototype.displayError = function( message ) {
		this.displayMessage( "bs-privacy-error", message );
	};

	bs.privacy.widget.Privacy.prototype.displaySuccess = function( message ) {
		this.displayMessage( "bs-privacy-success", message );
	};

} )( mediaWiki, jQuery, blueSpice );
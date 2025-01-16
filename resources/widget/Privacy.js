( function( mw, $, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.widget = bs.privacy.widget || {};

	bs.privacy.widget.Privacy = function( cfg ) {
		cfg = cfg || {};
		cfg.framed = true;
		cfg.expanded = false;

		this.api = new mw.Api();
		this.$element = cfg.$element || $( '<div>' );
		this.title = cfg.title;
		this.subtitle = cfg.subtitle;

		bs.privacy.widget.Privacy.parent.call( this, cfg );
	};

	OO.inheritClass( bs.privacy.widget.Privacy, OO.ui.PanelLayout );

	bs.privacy.widget.Privacy.prototype.init = function() {
		const heading = new OOJSPlus.ui.widget.HeadingLabel( {
			label: this.title,
			subtitle: this.subtitle
		} );
		heading.$element.css( 'margin-bottom', '10px' );
		this.layout = new OO.ui.FieldsetLayout( {
			items: [ heading ]
		} );

		this.makeForm();

		this.loader = new OO.ui.ProgressBarWidget( { progress: false } );
		this.loader.$element.hide();
		this.message = new OO.ui.MessageWidget( { inline: true } );
		this.message.$element.addClass( 'visually-hidden' );
		this.message.$element.attr( 'aria-live', 'assertive' );

		this.$element.append( this.layout.$element );
		this.$element.append( this.loader.$element );
		this.$element.append( this.message.$element );
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

	bs.privacy.widget.Privacy.prototype.displayMessage = function( type, message ) {
		// B/C
		if( Array.isArray( type ) ) {
			if ( type.indexOf( 'bs-privacy-error' ) !== -1 ) {
				type = 'bs-privacy-error';
			}
			if ( type.indexOf( 'bs-privacy-success' ) !== -1 ) {
				type = 'bs-privacy-success';
			}
		}
		type = type === 'bs-privacy-error' ? 'error' : type === 'bs-privacy-success' ? 'success' : 'notice';

		this.setLoading( false );
		if ( this.form instanceof OO.ui.FormLayout || this.form instanceof OO.ui.FieldLayout ) {
			if ( type === 'error' ) {
				this.form.setErrors( [ message ] );
			}
			if ( type === 'success' ) {
				this.form.setSuccess( message );
			}
		} else {
			this.message.$element.removeClass( 'visually-hidden' );
			this.message.setType( type );
			this.message.setLabel( message );
		}
	};

	bs.privacy.widget.Privacy.prototype.setLoading = function( loading ) {
		this.clearErrors();
		if ( loading ) {
			this.loader.$element.show();
			if ( this.form instanceof OO.ui.Element ) {
				this.form.$element.hide();
			}
			return;
		}
		this.loader.$element.hide();
		if ( this.form instanceof OO.ui.Element ) {
			this.form.$element.show();
		}
	};

	bs.privacy.widget.Privacy.prototype.clearErrors = function() {
		this.message.$element.addClass( 'visually-hidden' );
		this.message.setLabel( '' );
		if ( this.form instanceof OO.ui.FormLayout || this.form instanceof OO.ui.FieldLayout ) {
			this.form.setErrors( [] );
		}
	};

	bs.privacy.widget.Privacy.prototype.displayError = function( message ) {
		this.displayMessage( "bs-privacy-error", message );
	};

	bs.privacy.widget.Privacy.prototype.displaySuccess = function( message ) {
		this.displayMessage( "bs-privacy-success", message );
	};

} )( mediaWiki, jQuery, blueSpice );

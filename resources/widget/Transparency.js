( function ( mw, $, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.widget = bs.privacy.widget || {};

	bs.privacy.widget.Transparency = function ( cfg ) {
		cfg = cfg || {};

		cfg.title = cfg.title || mw.message( 'bs-privacy-transparency-layout-label' ).text();
		cfg.subtitle = cfg.subtitle || mw.message( 'bs-privacy-transparency-layout-help' ).text();
		bs.privacy.widget.Transparency.parent.call( this, cfg );

		this.api = new mw.Api( {
			ajax: {
				timeout: 300 * 1000 // 5min
			}
		} );
	};

	OO.inheritClass( bs.privacy.widget.Transparency, bs.privacy.widget.Privacy );

	bs.privacy.widget.Transparency.prototype.init = function () {
		bs.privacy.widget.Transparency.parent.prototype.init.call( this );
		this.loader.$element.remove();
		this.loaders = {
			data: new OO.ui.MessageWidget( { type: 'notice' } ),
			export: new OO.ui.MessageWidget( { type: 'notice' } )
		};
		this.loaders.data.$element.insertAfter( this.viewDataButton.$element );
		this.loaders.data.$element.addClass( 'visually-hidden' );
		this.loaders.data.$element.attr( 'aria-live', 'assertive' );
		this.loaders.export.$element.insertAfter( this.exportLayoutBody.$element );
		this.loaders.export.$element.addClass( 'visually-hidden' );
		this.loaders.export.$element.attr( 'aria-live', 'assertive' );

	};

	bs.privacy.widget.Transparency.prototype.makeForm = function () {
		this.viewDataButton = new OO.ui.ButtonWidget( {
			label: mw.message( 'bs-privacy-transparency-show-all-data-button' ).text(),
			flags: [
				'primary',
				'progressive'
			]
		} );
		this.viewDataButton.on( 'click', this.viewData.bind( this ) );

		this.makeExportLayout();

		this.form = new OO.ui.FieldsetLayout( {
			items: [
				this.viewDataButton,
				this.exportLayout
			]
		} );

		this.layout.addItems( [ this.form ] );
	};

	bs.privacy.widget.Transparency.prototype.exportData = function () {
		this.setLoading( true, 'export' );

		const selectedFormat = this.formatSelector.findSelectedItem();
		const data = {
			types: this.typeSelector.getValue(),
			export_format: selectedFormat.getData() // eslint-disable-line camelcase
		};

		if ( data.types.length === 0 ) {
			return;
		}

		this.getDataApi( data ).done( ( response ) => {
			this.setLoading( false, 'export' );
			if ( response.success === 0 ) {
				return this.displayError( mw.message( 'bs-privacy-request-failed' ).text() );
			}

			const anchor = document.createElement( 'a' );
			anchor.download = response.data.filename;
			if ( response.data.format === 'html' ) {
				anchor.href = 'data:text/html;charset=utf-8,' + encodeURIComponent( response.data.contents );
			} else if ( response.data.format === 'csv' ) {
				anchor.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent( response.data.contents );
			}

			if ( window.navigator.msSaveOrOpenBlob ) {
				// Special treatment for IE/Edge, as usual
				window.navigator.msSaveBlob( new Blob(
					[ response.data.contents ],
					{ type: 'text/html' }
				), anchor.download );
			} else {
				const e = document.createEvent( 'MouseEvents' );
				e.initEvent( 'click', true, true );
				anchor.dispatchEvent( e );
				return true;
			}

		} ).fail( () => {
			this.displayError( mw.message( 'bs-privacy-request-failed' ).text() );
			this.setLoading( false, 'export' );
		} );
	};

	bs.privacy.widget.Transparency.prototype.getDataApi = function ( data ) {
		data = data || {};
		const apiData = {
			action: 'bs-privacy',
			module: 'transparency',
			func: 'getData',
			data: JSON.stringify( data )
		};

		return this.api.post( apiData );
	};

	bs.privacy.widget.Transparency.prototype.viewData = function () {
		this.setLoading( true, 'data' );
		this.getDataApi().done( ( response ) => {
			this.setLoading( false, 'data' );
			if ( response.success === 0 ) {
				return this.displayError( mw.message( 'bs-privacy-request-failed' ).text() );
			}
			const windowManager = OO.ui.getWindowManager();
			const cfg = {
				data: response.data,
				size: 'larger'
			};
			const dialog = new bs.privacy.dialog.ViewDataDialog( cfg );
			windowManager.addWindows( [ dialog ] );
			windowManager.openWindow( dialog );
		} ).fail( () => {
			this.displayError( mw.message( 'bs-privacy-request-failed' ).text() );
			this.setLoading( false, 'data' );
		} );
	};

	bs.privacy.widget.Transparency.prototype.setLoading = function ( value, type ) {
		const loader = this.loaders[ type ];
		if ( !loader ) {
			return;
		}
		const element = type === 'export' ? this.exportLayoutBody : this.viewDataButton;
		if ( value ) {
			element.$element.hide();
			loader.$element.removeClass( 'visually-hidden' );
			loader.setLabel( mw.message( 'bs-privacy-transparency-loading-message' ).text() );
		} else {
			loader.setLabel( '' );
			loader.$element.addClass( 'visually-hidden' );
			element.$element.show();
		}
	};

	bs.privacy.widget.Transparency.prototype.makeExportLayout = function () {
		this.typeSelector = new OO.ui.CheckboxMultiselectInputWidget( {
			value: [
				'personal',
				'working',
				'actions',
				'content'
			],
			options: [
				{
					data: 'personal',
					label: mw.message( 'bs-privacy-transparency-type-selector-personal' ).text()
				},
				{
					data: 'working',
					label: mw.message( 'bs-privacy-transparency-type-selector-working' ).text()
				},
				{
					data: 'actions',
					label: mw.message( 'bs-privacy-transparency-type-selector-actions' ).text()
				},
				{
					data: 'content',
					label: mw.message( 'bs-privacy-transparency-type-selector-content' ).text()
				}
			]
		} );

		this.formatSelector = new OO.ui.RadioSelectWidget( {
			items: [
				new OO.ui.RadioOptionWidget( {
					data: 'html',
					label: mw.message( 'bs-privacy-transparency-format-html' ).text()
				} ),
				new OO.ui.RadioOptionWidget( {
					data: 'csv',
					label: mw.message( 'bs-privacy-transparency-format-csv' ).text()
				} )
			]
		} );
		this.formatSelector.selectItemByData( 'html' );

		this.exportButton = new OO.ui.ButtonWidget( {
			label: mw.message( 'bs-privacy-transparency-export-data-button' ).text(),
			flags: [
				'primary',
				'progressive'
			]
		} );
		this.exportButton.on( 'click', this.exportData.bind( this ) );

		this.exportLayoutBody = new OO.ui.FieldsetLayout( {
			items: [
				new OO.ui.HorizontalLayout( {
					items: [
						new OO.ui.FieldLayout( this.typeSelector, {
							label: mw.message( 'bs-privacy-transparency-export-types-of-data-label' ).text(),
							align: 'top'
						} ),
						new OO.ui.FieldLayout( this.formatSelector, {
							label: mw.message( 'bs-privacy-transparency-export-export-format-label' ).text(),
							align: 'top'
						} )
					]
				} ),
				this.exportButton
			]
		} );

		this.exportLayout = new OO.ui.FieldsetLayout( {
			label: mw.message( 'bs-privacy-transparency-export-layout-title' ).text(),
			classes: [ 'bs-privacy-transparency-export' ],
			items: [
				this.exportLayoutBody
			]
		} );
	};

}( mediaWiki, jQuery, blueSpice ) );

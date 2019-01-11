( function( mw, $, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.widget = bs.privacy.widget || {};

	bs.privacy.widget.Transparency = function( cfg ) {
		cfg = cfg || {};

		cfg.title = cfg.title || mw.message( 'bs-privacy-transparency-layout-label' ).text();
		cfg.subtitle = cfg.subtitle || mw.message( 'bs-privacy-transparency-layout-help' ).text();
		bs.privacy.widget.Transparency.parent.call( this, cfg );
	};

	OO.inheritClass( bs.privacy.widget.Transparency, bs.privacy.widget.Privacy );

	bs.privacy.widget.Transparency.prototype.makeForm = function() {
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

	bs.privacy.widget.Transparency.prototype.exportData = function() {
		this.setLoading( true, this.exportLayoutBody );

		var data = {
			types: this.typeSelector.getValue(),
			export_format: this.formatSelector.getValue()
		};

		if( data.types.length === 0 ) {
			return;
		}

		this.getDataApi( data ).done( function( response ) {
			this.setLoading( false );
			if( response.success === 0 ) {
				return this.displayError( mw.message( "bs-privacy-request-failed" ).text() );
			}

			var anchor = document.createElement( 'a' );
			anchor.download = response.data.filename;
			if( response.data.format === 'html' ) {
				anchor.href = 'data:text/html;charset=utf-8,' + encodeURIComponent( response.data.contents );
			} else if ( response.data.format === 'csv' ) {
				anchor.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent( response.data.contents );
			}

			if( window.navigator.msSaveOrOpenBlob ) {
				// Special treatment for IE/Edge, as usual
				window.navigator.msSaveBlob( new Blob(
					[ response.data.contents ],
					{ type: 'text/html' }
				), anchor.download );
			} else {
				var e = document.createEvent( 'MouseEvents' );
				e.initEvent( 'click', true, true );
				anchor.dispatchEvent( e );
				return true;
			}

		}.bind( this ) ).fail( function( response ) {
			this.displayError( mw.message( "bs-privacy-request-failed" ).text() );
			this.setLoading( false );
		}.bind( this ) );
	};

	bs.privacy.widget.Transparency.prototype.getDataApi = function( data ) {
		data = data || {};
		var apiData = {
			action: 'bs-privacy',
			module: 'transparency',
			func: 'getData',
			data: JSON.stringify( data )
		};

		return this.api.post( apiData );
	};

	bs.privacy.widget.Transparency.prototype.viewData = function() {
		this.setLoading( true, this.viewDataButton );
		this.getDataApi().done( function( response ) {
			this.setLoading( false );
			if( response.success === 0 ) {
				return this.displayError( mw.message( "bs-privacy-request-failed" ).text() );
			}
			var windowManager = OO.ui.getWindowManager();
			var cfg = {
				data: response.data,
				size: 'larger'
			};
			var dialog = new bs.privacy.dialog.ViewDataDialog( cfg );
			windowManager.addWindows( [ dialog ] );
			windowManager.openWindow( dialog );
		}.bind( this ) ).fail( function() {
			this.displayError( mw.message( "bs-privacy-request-failed" ).text() );
			this.setLoading( false );
		}.bind( this ) );
	};

	bs.privacy.widget.Transparency.prototype.setLoading = function( value, element ) {
		if( this.loading ) {
			this.loading.$element.remove();
			this.loadingElement.$element.show();
		}

		if( value ) {
			this.loading = new OO.ui.HorizontalLayout( {
				items: [
					new OO.ui.LabelWidget( {
						label: mw.message( 'bs-privacy-transparency-loading-message' ).text()
					} )
				]
			} );
			this.loadingElement = element;
			this.loading.$element.insertAfter( this.loadingElement.$element );
			this.loadingElement.$element.hide();
		}
	};

	bs.privacy.widget.Transparency.prototype.makeExportLayout = function() {
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

		this.formatSelector = new OO.ui.RadioSelectInputWidget( {
			value: 'html',
			options: [
				{
					data: 'html',
					label: mw.message( 'bs-privacy-transparency-format-html' ).text()
				},
				{
					data: 'csv',
					label: mw.message( 'bs-privacy-transparency-format-csv' ).text()
				}
			]
		} );

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
							label: 'Types of data',
							align: 'top'
						} ),
						new OO.ui.FieldLayout( this.formatSelector, {
							label: 'Export format',
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

} )( mediaWiki, jQuery, blueSpice );
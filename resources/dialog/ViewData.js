( function ( mw, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.dialog = bs.privacy.dialog || {};

	bs.privacy.dialog.ViewDataDialog = function ( cfg ) {
		cfg = cfg || {};

		this.data = cfg.data;
		bs.privacy.dialog.ViewDataDialog.super.call( this, cfg );
	};

	OO.inheritClass( bs.privacy.dialog.ViewDataDialog, OO.ui.ProcessDialog );

	bs.privacy.dialog.ViewDataDialog.static.name = 'viewDataDialog';

	bs.privacy.dialog.ViewDataDialog.static.title = mw.message( 'bs-privacy-transparency-view-data-dialog-title' ).text();

	bs.privacy.dialog.ViewDataDialog.static.actions = [
		{
			label: mw.message( 'bs-privacy-transparency-view-data-dialog-close' ).text(),
			flags: 'safe'
		}

	];

	bs.privacy.dialog.ViewDataDialog.prototype.initialize = function () {
		bs.privacy.dialog.ViewDataDialog.super.prototype.initialize.call( this );

		this.indexLayout = new OO.ui.IndexLayout( {
			expanded: true
		} );

		for ( const tab in this.data ) {
			const content = this.data[ tab ];

			const tabPanel = new OO.ui.TabPanelLayout( tab, {
				// The following messages are used here:
				// * bs-privacy-transparency-type-title-content
				// * bs-privacy-transparency-type-title-working
				// * bs-privacy-transparency-type-title-personal
				// * bs-privacy-transparency-type-title-actions
				label: mw.message( 'bs-privacy-transparency-type-title-' + tab ).text()
			} );

			if ( content.length === 0 ) {
				tabPanel.$element.append( this.getEmptyTab().$element );
			} else {
				for ( const idx in content ) {
					const item = content[ idx ];
					tabPanel.$element.append( new OO.ui.LabelWidget( {
						label: item,
						classes: [ 'bs-privacy-transparency-tab-line' ]
					} ).$element );
				}
			}

			this.indexLayout.addTabPanels( [ tabPanel ] );

		}

		this.layout = new OO.ui.PanelLayout( {
			expanded: true,
			framed: false,
			content: [
				this.indexLayout
			]
		} );

		this.$body.append( this.layout.$element );
	};

	bs.privacy.dialog.ViewDataDialog.prototype.getBodyHeight = function () {
		return this.layout.$element.outerHeight() + 500;
	};

	bs.privacy.dialog.ViewDataDialog.prototype.getEmptyTab = function () {
		return new OO.ui.LabelWidget( {
			label: mw.message( 'bs-privacy-transparency-no-data' ).text(),
			classes: [ 'bs-privacy-transparency-no-data' ]
		} );
	};

}( mediaWiki, blueSpice ) );

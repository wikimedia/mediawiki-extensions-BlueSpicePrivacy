( function( mw, $, bs, d, undefined ){
	window.bs.privacy = bs.privacy || {};
	bs.privacy.dialog = bs.privacy.dialog || {};

	bs.privacy.dialog.ViewDataDialog = function( cfg ) {
		cfg = cfg || {};

		this.data = cfg.data;
		bs.privacy.dialog.ViewDataDialog.super.call( this, cfg );
	}

	OO.inheritClass( bs.privacy.dialog.ViewDataDialog, OO.ui.ProcessDialog );

	bs.privacy.dialog.ViewDataDialog.static.name = 'viewDataDialog';

	bs.privacy.dialog.ViewDataDialog.static.title = mw.message( 'bs-privacy-transparency-view-data-dialog-title' ).plain();

	bs.privacy.dialog.ViewDataDialog.static.actions = [
		{
			label: mw.message( 'bs-privacy-transparency-view-data-dialog-close' ).plain(),
			flags: 'safe'
		}

	];

	bs.privacy.dialog.ViewDataDialog.prototype.initialize = function() {
		bs.privacy.dialog.ViewDataDialog.super.prototype.initialize.call( this );

		this.indexLayout = new OO.ui.IndexLayout( {
			expanded: true
		} );


		for ( var tab in this.data ) {
			var content = this.data[tab];

			var tabPanel = new OO.ui.TabPanelLayout( tab, {
				label: mw.message( 'bs-privacy-transparency-type-title-' + tab ).text()
			} );

			if( content.length === 0 ) {
				tabPanel.$element.append( this.getEmptyTab().$element );
			} else {
				for( var idx in content ) {
					var item = content[idx];
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
	}

	bs.privacy.dialog.ViewDataDialog.prototype.getBodyHeight = function () {
		return this.layout.$element.outerHeight() + 500;
	};

	bs.privacy.dialog.ViewDataDialog.prototype.getEmptyTab = function () {
		return new OO.ui.LabelWidget( {
			label: mw.message( 'bs-privacy-transparency-no-data' ).text(),
			classes: [ 'bs-privacy-transparency-no-data' ]
		} );
	};

} )( mediaWiki, jQuery, blueSpice, document );

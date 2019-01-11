( function( mw, $, bs ) {
	window.bs.privacy = bs.privacy || {};
	bs.privacy.widget = bs.privacy.widget || {};

	var anonymizeWidget = new bs.privacy.widget.Anonymize( {
		$element: $( '.section-anonymization' ),
		userName: mw.config.get( 'wgUserName' )
	} );

	var deleteWidget = new bs.privacy.widget.Delete( {
		$element: $( '.section-deletion' )
	} );

	var transparencyWidget = new bs.privacy.widget.Transparency( {
		$element: $( '.section-transparency' )
	} );

	var consentWidget = new bs.privacy.widget.Consent( {
		$element: $( '.section-consent' )
	} );

	anonymizeWidget.init();
	deleteWidget.init();
	transparencyWidget.init();
	consentWidget.init();

} )( mediaWiki, jQuery, blueSpice );
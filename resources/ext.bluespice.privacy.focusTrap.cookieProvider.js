( function ( mw, $, d ) {

	$( d ).on( 'keydown', ( e ) => {
		if ( e.key && e.key.toLowerCase() === 'tab' &&
			$( '.bs-privacy-cookie-consent-mw-provider-bar' ).length ) {
			const $targetEl = $( e.target );
			const $tabChildren = $( '.bs-privacy-cookie-consent-mw-provider-bar ' ).children();

			if ( e.shiftKey ) {
				if ( $targetEl.is( $tabChildren.first() ) ) {
					e.preventDefault();
					$tabChildren.last().children().trigger( 'focus' );
				}
			} else {
				if ( $targetEl.is( $tabChildren.last().children() ) ) {
					e.preventDefault();
					$tabChildren.first().trigger( 'focus' );
				}
			}
		}
	} );

}( mediaWiki, jQuery, document ) );

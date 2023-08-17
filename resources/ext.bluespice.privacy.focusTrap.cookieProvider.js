( function( mw, $, d ){

	$( d ).keydown( function(e) {
		if( e.key && e.key.toLowerCase() === 'tab' &&
			$( '.bs-privacy-cookie-consent-mw-provider-bar' ).length ) {
			var $targetEl = $( e.target );
			var $tabChildren = $( ".bs-privacy-cookie-consent-mw-provider-bar ").children();

			if( e.shiftKey ) {
				if ( $targetEl.is( $tabChildren.first() ) ) {
					e.preventDefault();
					$tabChildren.last().children().focus();
				}
			} else {
				if ( $targetEl.is( $tabChildren.last().children() ) ) {
					e.preventDefault();
					$tabChildren.first().focus();
				}
			}
		}
	})

} )( mediaWiki, jQuery, document );

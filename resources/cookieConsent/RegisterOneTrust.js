var handlerConfig = mw.config.get( "bsPrivacyCookieConsentHandlerConfig" );
var nonce = mw.config.get( 'wgCSPNonce' );
if ( nonce ) {
	var script = document.createElement( "script" );
	script.src = handlerConfig['scriptURL'];
	script.nonce = nonce;
	script.type = "text/javascript";
	document.head.appendChild( script );
} else {
	mw.loader.load( handlerConfig['scriptURL'], "text/javascript" );
}


function OptanonWrapper() {}

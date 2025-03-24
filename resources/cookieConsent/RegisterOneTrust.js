const handlerConfig = mw.config.get( 'bsPrivacyCookieConsentHandlerConfig' );
const nonce = mw.config.get( 'wgCSPNonce' );
if ( nonce ) {
	const script = document.createElement( 'script' );
	script.src = handlerConfig.scriptURL;
	script.nonce = nonce;
	script.type = 'text/javascript';
	document.head.appendChild( script );
} else {
	mw.loader.load( handlerConfig.scriptURL, 'text/javascript' );
}

function OptanonWrapper() {} // eslint-disable-line no-implicit-globals, no-unused-vars

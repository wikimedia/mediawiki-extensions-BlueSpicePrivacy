<?php

namespace BlueSpice\Privacy\Hook\BeforePageDisplay;

use BlueSpice\Hook\BeforePageDisplay;
use BlueSpice\Privacy\CookieConsentProviderRegistry;
use BlueSpice\Privacy\ICookieConsentProvider;

class AddCookieConsent extends BeforePageDisplay {

	protected function doProcess() {
		$providerRegistry = new CookieConsentProviderRegistry();
		$provider = $providerRegistry->getProvider();

		if ( $provider instanceof ICookieConsentProvider === false ) {
			return true;
		}

		$this->out->addModules( [
			$provider->getRLRegistrationModule(),
			$provider->getRLHandlerModule()
		] );

		// Add cookie handling
		$this->out->addModules( 'ext.bs.privacy.cookieconsent' );
		$this->out->addJsConfigVars( 'bsPrivacyCookieConsentHandlerConfig', array_merge( [
			"class" => $provider->getHandlerClass(),
			"map" => $provider->getGroupMapping(),
			"cookieName" => $provider->getCookieName()
		], $provider->getHandlerConfig() ) );

		return true;
	}
}

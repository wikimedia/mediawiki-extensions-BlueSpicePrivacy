<?php

namespace BlueSpice\Privacy\HookHandler;

use BlueSpice\Privacy\CookieConsentProviderRegistry;
use BlueSpice\Privacy\ICookieConsentProvider;
use Config;
use ConfigFactory;
use MediaWiki\Hook\BeforePageDisplayHook;

class AddCookieConsent implements BeforePageDisplayHook {
	/** @var bool */
	private $acceptMandatory;
	/** @var string */
	private $cookiePrefix;

	/**
	 * @param Config $mainConfig
	 * @param ConfigFactory $configFactory
	 */
	public function __construct( Config $mainConfig, ConfigFactory $configFactory ) {
		$this->acceptMandatory = (bool)$configFactory->makeConfig( 'bsg' )->get( 'PrivacyCookieAcceptMandatory' );
		$this->cookiePrefix = $mainConfig->get( 'CookiePrefix' );
	}

	/**
	 * @inheritDoc
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$providerRegistry = new CookieConsentProviderRegistry();
		$provider = $providerRegistry->getProvider();

		if ( $provider instanceof ICookieConsentProvider === false ) {
			return;
		}
		if ( $out->getUser()->getName() === 'NoConsentWikiSysop' ) {
			// User does not require consent
			return;
		}

		$out->addModules( [
			$provider->getRLRegistrationModule()
		] );

		// Add cookie handling
		$out->addModules( 'ext.bs.privacy.cookieconsent' );
		$out->addJsConfigVars( 'bsPrivacyCookieConsentHandlerConfig', array_merge( [
			"class" => $provider->getHandlerClass(),
			"map" => $provider->getGroupMapping(),
			"cookieName" => $provider->getCookieName(),
			"cookiePrefix" => $this->cookiePrefix,
			"RLModule" => $provider->getRLHandlerModule(),
			"acceptMandatory" => $this->acceptMandatory
		], $provider->getHandlerConfig() ) );
	}
}

<?php

namespace BlueSpice\Privacy\HookHandler;

use BlueSpice\Privacy\CookieConsentProviderRegistry;
use BlueSpice\Privacy\ICookieConsentProvider;
use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Output\Hook\BeforePageDisplayHook;

class AddCookieConsent implements BeforePageDisplayHook {
	/**
	 * @var Config
	 */
	protected $mainConfig;

	/**
	 * @var Config
	 */
	protected $bsgConfig;

	/**
	 * @param Config $mainConfig
	 * @param ConfigFactory $configFactory
	 */
	public function __construct( Config $mainConfig, ConfigFactory $configFactory ) {
		$this->mainConfig = $mainConfig;
		$this->bsgConfig = $configFactory->makeConfig( 'bsg' );
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
			"cookiePrefix" => $this->mainConfig->get( 'CookiePrefix' ),
			"cookiePath" => $this->mainConfig->get( 'CookiePath' ),
			"RLModule" => $provider->getRLHandlerModule(),
			"acceptMandatory" => (bool)$this->bsgConfig->get( 'PrivacyCookieAcceptMandatory' )
		], $provider->getHandlerConfig() ) );
	}
}

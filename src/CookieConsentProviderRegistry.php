<?php

namespace BlueSpice\Privacy;

use BlueSpice\ExtensionAttributeBasedRegistry;
use BlueSpice\Privacy\CookieConsentProvider\Base as CookieConsentBase;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;

class CookieConsentProviderRegistry extends ExtensionAttributeBasedRegistry {
	/** @var ICookieConsentProvider|null */
	protected $provider = null;

	public function __construct() {
		parent::__construct( "BlueSpicePrivacyCookieConsentProviders" );
	}

	/**
	 * Gets configured CookieConsentProvider
	 *
	 * @return ICookieConsentProvider|null
	 */
	public function getProvider() {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		$selectedProvider = $config->get( 'PrivacyCookieConsentProvider' );

		$providerConfig = [];
		if ( is_array( $selectedProvider ) ) {
			$providerConfig = $selectedProvider['config'];
			$selectedProvider = $selectedProvider['name'];
		}
		if ( !$selectedProvider ) {
			return null;
		}

		if ( $this->provider === null ) {
			$allProviders = $this->getAllKeys();
			if ( !in_array( $selectedProvider, $allProviders ) ) {
				return null;
			}

			$this->provider = $this->instantiate( $this->getValue( $selectedProvider ), $providerConfig );
		}

		return $this->provider;
	}

	/**
	 *
	 * @param string $callback
	 * @param array $config
	 * @return CookieConsentBase
	 */
	protected function instantiate( $callback, $config ) {
		if ( !is_callable( $callback ) ) {
			return null;
		}

		$providerConfig = new \HashConfig( $config );

		return call_user_func_array( $callback, [
			MediaWikiServices::getInstance()->getMainConfig(),
			RequestContext::getMain()->getRequest(),
			$providerConfig
		] );
	}
}

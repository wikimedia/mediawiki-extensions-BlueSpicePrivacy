<?php

namespace BlueSpice\Privacy\CookieConsentProvider;

use BlueSpice\Privacy\ICookieConsentProvider;
use Config;
use ConfigException;
use Exception;
use HashConfig;
use Hooks;
use WebRequest;

abstract class Base implements ICookieConsentProvider {
	/**
	 * @var Config
	 */
	protected $config;

	/**
	 * @var WebRequest
	 */
	protected $request;

	/**
	 * @var array
	 */
	protected $cookieGroups = [];

	/**
	 * NativeMW constructor.
	 *
	 * @param Config $config
	 * @param WebRequest $request
	 * @param array $cookieGroups
	 */
	public function __construct( $config, $request, $cookieGroups ) {
		$this->config  = $config;
		$this->request = $request;

		$provider = $this;
		Hooks::run(
			'BlueSpicePrivacyCookieConsentProviderGetGroups',
			[ $provider, &$cookieGroups ],
			'3.2'
		);
		$this->cookieGroups = $cookieGroups;
	}

	/**
	 * @param Config $config
	 * @param WebRequest $request
	 * @param HashConfig $providerConfig
	 * @return ICookieConsentProvider
	 * @throws Exception
	 */
	public static function factory( $config, $request, $providerConfig ) {
		if ( !$providerConfig->has( 'groups' ) ) {
			throw new \Exception( "Provider configuration must provider \"groups\" parameter" );
		}
		return new static(
			$config,
			$request,
			$providerConfig->get( 'groups' )
		);
	}

	/**
	 * @return array
	 * @throws ConfigException
	 */
	public function getGroupMapping() {
		$mapping = [];
		foreach ( $this->cookieGroups as $key => $conf ) {
			if ( !isset( $conf['cookies'] ) ) {
				$mapping[$key] = [];
				continue;
			}

			$cookies = [];
			foreach ( $conf['cookies'] as $cookie ) {
				$addPrefix = isset( $cookie['addPrefix'] ) ? $cookie['addPrefix'] : false;
				$cookies[] = [
					"type" => $cookie['type'],
					"name" => $addPrefix ? $this->config->get( 'CookiePrefix' ) . $cookie['name'] : $cookie['name']
				];
			}

			$mapping[$key] = $cookies;
		}

		return $mapping;
	}

	/**
	 * @return string
	 * @throws ConfigException
	 */
	protected function getCookiePrefix() {
		return $this->config->get( 'CookiePrefix' ) . '_';
	}
}

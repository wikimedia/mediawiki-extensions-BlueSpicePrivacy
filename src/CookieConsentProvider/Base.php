<?php

namespace BlueSpice\Privacy\CookieConsentProvider;

use BlueSpice\Privacy\ICookieConsentProvider;

abstract class Base implements ICookieConsentProvider {
	/**
	 * @var \Config
	 */
	protected $config;

	/**
	 * @var \WebRequest
	 */
	protected $request;

	/**
	 * @var array
	 */
	protected $cookieGroups = [];

	/**
	 * NativeMW constructor.
	 *
	 * @param \Config $config
	 * @param \WebRequest $request
	 * @param array $cookieGroups
	 */
	public function __construct( $config, $request, $cookieGroups ) {
		$this->config  = $config;
		$this->request = $request;
		$this->cookieGroups = $cookieGroups;
	}

	/**
	 * @param \Config $config
	 * @param \WebRequest $request
	 * @param \HashConfig $providerConfig
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
}

<?php

namespace BlueSpice\Privacy\CookieConsentProvider;

use MediaWiki\Config\Config;
use MediaWiki\Config\HashConfig;
use MediaWiki\Request\WebRequest;

class OneTrust extends Base {

	/**
	 * @var string
	 */
	protected $scriptURL = '';

	/**
	 * @var array
	 */
	protected $userGroupPreference = [];

	/**
	 * OneTrust constructor.
	 *
	 * @param Config $config
	 * @param WebRequest $request
	 * @param array $groups
	 * @param string $scriptURL
	 */
	public function __construct( $config, $request, $groups, $scriptURL ) {
		parent::__construct( $config, $request, $groups );

		$this->scriptURL = $scriptURL;
	}

	/**
	 * @param Config $config
	 * @param WebRequest $request
	 * @param HashConfig $providerConfig
	 * @return OneTrust
	 * @throws \Exception
	 */
	public static function factory( $config, $request, $providerConfig ) {
		if ( !$providerConfig->has( 'groups' ) ) {
			throw new \Exception( "Provider configuration must provider \"groups\" parameter" );
		}
		if ( !$providerConfig->has( 'ScriptURL' ) ) {
			throw new \Exception( "Provider configuration must provider \"ScriptURL\" parameter" );
		}

		return new self(
			$config,
			$request,
			$providerConfig->get( 'groups' ),
			$providerConfig->get( 'ScriptURL' )
		);
	}

	/**
	 * @return string
	 */
	public function getRLRegistrationModule() {
		return "ext.bs.privacy.cookieconsent.onetrust.register";
	}

	/**
	 * Get consent groups based on user preferences
	 *
	 * @return array
	 */
	public function getGroups() {
		if ( empty( $this->userGroupPreference ) ) {
			$rawCookie = $this->request->getCookie( $this->getCookieName(), '' );

			if ( !$rawCookie ) {
				return [];
			}

			$cookie = [];
			$cookieCrumbs = explode( '&', $rawCookie );
			foreach ( $cookieCrumbs as $crumb ) {
				$crumbBits = explode( '=', $crumb );
				$cookie[array_shift( $crumbBits )] = array_shift( $crumbBits );
			}

			if ( !isset( $cookie['groups'] ) ) {
				return [];
			}
			$groups = $cookie['groups'];

			$parsedGroups = [];
			$joinedGroups = explode( ',', $groups );
			foreach ( $joinedGroups as $joinedGroup ) {
				$groupBits = explode( ':', $joinedGroup );
				$parsedGroups[array_shift( $groupBits )] = array_shift( $groupBits ) === "1" ? true : false;
			}

			$this->userGroupPreference = $parsedGroups;
		}

		return $this->userGroupPreference;
	}

	/**
	 * @return string
	 */
	public function getCookieName() {
		return "OptanonConsent";
	}

	/**
	 * @return string
	 */
	public function getHandlerClass() {
		return "bs.privacy.cookieConsent.OneTrust";
	}

	/**
	 * @return string
	 */
	public function getRLHandlerModule() {
		return "ext.bs.privacy.cookieconsent.onetrust.handler";
	}

	/**
	 * @return array
	 */
	public function getHandlerConfig() {
		return [
			"scriptURL" => $this->scriptURL
		];
	}
}

<?php

namespace BlueSpice\Privacy\CookieConsentProvider;

use BlueSpice\Privacy\ICookieConsentProvider;
use Exception;
use ExtensionRegistry;
use HashConfig;
use MediaWiki\Config\Config;
use MediaWiki\Json\FormatJson;
use MediaWiki\Message\Message;
use MediaWiki\Request\WebRequest;
use RawMessage;

class NativeMW extends Base {
	/**
	 * @param Config $config
	 * @param WebRequest $request
	 * @param HashConfig $providerConfig
	 * @return ICookieConsentProvider
	 * @throws Exception
	 */
	public static function factory( $config, $request, $providerConfig ) {
		return new static(
			$config,
			$request,
			static::getGroupsFromAttribute()
		);
	}

	/**
	 * Parse the attribute and generate cookie consent group config
	 *
	 * @return array
	 */
	protected static function getGroupsFromAttribute() {
		$cookieGroupsAttribute = ExtensionRegistry::getInstance()->getAttribute(
			'BlueSpicePrivacyCookieConsentNativeMWCookieGroups'
		);
		$cookiesAttribute = ExtensionRegistry::getInstance()->getAttribute(
			'BlueSpicePrivacyCookieConsentNativeMWCookies'
		);

		$ret = [];
		foreach ( $cookieGroupsAttribute as $groupId => $groupConfig ) {
			if ( !is_array( $groupConfig ) ) {
				continue;
			}
			$groupConfig['cookies'] = [];
			$ret[$groupId] = $groupConfig;
		}

		foreach ( $cookiesAttribute as $cookieId => $cookie ) {
			if (
				!is_array( $cookie ) || !isset( $cookie['group'] ) || !isset( $ret[$cookie['group']] )
			) {
				continue;
			}

			$ret[$cookie['group']]['cookies'][] = [
				'type' => 'exact',
				'name' => $cookieId,
				'addPrefix' => isset( $cookie['addPrefix'] ) && $cookie['addPrefix'] === true
			];
		}

		return $ret;
	}

	/**
	 * @var array
	 */
	protected $userGroupPreference = [];

	/**
	 * RL module that is loaded as high up in <head> as possible
	 *
	 * @return string
	 */
	public function getRLRegistrationModule() {
		return "ext.bs.privacy.cookieconsent.nativemw.register";
	}

	/**
	 * Get consent groups based on user preferences
	 *
	 * @return array
	 */
	public function getGroups() {
		if ( empty( $this->userGroupPreference ) ) {
			$rawCookie = $this->request->getCookie( $this->getCookieName(), $this->getCookiePrefix() );

			if ( !$rawCookie ) {
				return [];
			}

			$parsed = FormatJson::decode( $rawCookie, true );
			if ( isset( $parsed['groups'] ) ) {
				$this->userGroupPreference = $parsed['groups'];
			}
		}

		return $this->userGroupPreference;
	}

	/**
	 * Name of the cookie where cookie preferences are set
	 *
	 * @return string
	 */
	public function getCookieName() {
		return "MWCookieConsent";
	}

	/**
	 * RL module used to handle cookies
	 *
	 * @return string
	 */
	public function getRLHandlerModule() {
		return "ext.bs.privacy.cookieconsent.nativemw.handler";
	}

	/**
	 * @return string
	 */
	public function getHandlerClass() {
		return "bs.privacy.cookieConsent.MWProvider";
	}

	/**
	 * Config to be passed to the client
	 *
	 * @return array
	 */
	public function getHandlerConfig() {
		$groupConfigs = [];
		foreach ( $this->cookieGroups as $key => $conf ) {
			$conf['desc'] = $this->generateDescription( $conf );
			if ( isset( $conf['cookies'] ) ) {
				unset( $conf['cookies'] );
			}

			$groupConfigs[$key] = $conf;
		}
		return [
			"cookieGroups" => $groupConfigs
		];
	}

	/**
	 * Get group description and cookie list
	 *
	 * @param array $conf
	 * @return string
	 */
	private function generateDescription( $conf ) {
		$desc = '';
		if ( isset( $conf['desc'] ) ) {
			$desc = Message::newFromKey( $conf['desc'] )->text();
		}

		$desc .= "\n\n" . Message::newFromKey(
			'bs-privacy-cookie-consent-cookie-list-label'
			)->text();

		foreach ( $conf['cookies'] as $cookieSpec ) {
			$cookieName = $cookieSpec['name'];
			$descMessage = Message::newFromKey(
				'bs-privacy-cookie-consent-cookie-desc-' . strtolower( $cookieName )
			);
			$desc .= "\n \n'''{$cookieName}'''<br>{$descMessage->text()}";
		}

		return ( new RawMessage( $desc ) )->parseAsBlock();
	}
}

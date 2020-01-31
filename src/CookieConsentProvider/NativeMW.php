<?php

namespace BlueSpice\Privacy\CookieConsentProvider;

class NativeMW extends Base {
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

			$parsed = \FormatJson::decode( $rawCookie, true );
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
			if ( isset( $conf['cookies'] ) ) {
				unset( $conf['cookies'] );
			}
			$groupConfigs[$key] = $conf;
		}
		return [
			"cookieGroups" => $groupConfigs
		];
	}
}

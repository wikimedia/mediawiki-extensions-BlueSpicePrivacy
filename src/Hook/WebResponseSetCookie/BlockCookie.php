<?php

namespace BlueSpice\Privacy\Hook\WebResponseSetCookie;

use BlueSpice\Hook\WebResponseSetCookie;
use BlueSpice\Privacy\CookieConsentProviderRegistry;
use BlueSpice\Privacy\ICookieConsentProvider;

class BlockCookie extends WebResponseSetCookie {

	/**
	 * Blocks all cookies marked by the user as undesired
	 * from being set
	 *
	 * @return bool
	 */
	protected function doProcess() {
		$providerRegistry = new CookieConsentProviderRegistry();
		$provider = $providerRegistry->getProvider();

		if ( $provider instanceof ICookieConsentProvider === false ) {
			return true;
		}

		$groups = $provider->getGroups();

		$cookieGroup = $this->getCookieGroup( $provider->getGroupMapping() );
		if ( !$cookieGroup ) {
			// Allow all un-categorized cookies - blocking unknown cookies
			// can lead to unexpected results
			return true;
		}
		if ( isset( $groups[$cookieGroup] ) && $groups[$cookieGroup] === false ) {
			return false;
		}

		return true;
	}

	/**
	 *
	 * @param array $mapping
	 * @return string
	 */
	protected function getCookieGroup( $mapping ) {
		$prefixedName = $this->getConfig()->get( 'CookiePrefix' ) . $this->name;
		foreach ( $mapping as $group => $cookies ) {
			foreach ( $cookies as $cookie ) {
				if ( !isset( $cookie['type'] ) || $cookie['type'] === "exact" ) {
					if ( $prefixedName === $cookie['name'] ) {
						return $group;
					}
				}
				if ( $cookie['type'] === 'regex' ) {
					if ( preg_match( "/{$cookie['name']}/", $prefixedName ) ) {
						return $group;
					}
				}
			}

		}
		return '';
	}
}

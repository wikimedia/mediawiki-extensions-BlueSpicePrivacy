<?php

namespace BlueSpice\Privacy;

class Extension extends \BlueSpice\Extension {
	public static function onCallback() {
		if ( $GLOBALS['bsgPrivacyPrivacyPolicyMandatory'] ) {
			$GLOBALS[ 'wgAuthManagerAutoConfig' ][ 'secondaryauth' ]
			[ Auth\Provider\ConsentSecondaryAuthenticationProvider::class ] = [
				'class' => Auth\Provider\ConsentSecondaryAuthenticationProvider::class
			];
		}

		$GLOBALS['wgLogRestrictions']['bs-privacy'] = 'bs-privacy-admin';

		$GLOBALS['wgDefaultUserOptions']['echo-subscriptions-email-bs-privacy-cat'] = 1;
	}
}

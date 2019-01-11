<?php

namespace BlueSpice\Privacy;

class Extension extends \BlueSpice\Extension {
	public static function onCallback() {
		$GLOBALS[ 'wgAuthManagerAutoConfig' ][ 'primaryauth' ]
			[ Auth\Provider\SignupPrimaryAuthenticationProvider::class ] = [
			'class' => Auth\Provider\SignupPrimaryAuthenticationProvider::class
		];

		$GLOBALS['wgLogRestrictions']['bs-privacy'] = 'bs-privacy-admin';

		$GLOBALS['wgDefaultUserOptions']['echo-subscriptions-email-bs-privacy-cat'] = 1;
	}
}

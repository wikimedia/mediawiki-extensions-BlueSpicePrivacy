<?php

namespace BlueSpice\Privacy\Hook\SpecialPageBeforeExecute;

use BlueSpice\Privacy\Module\Consent;
use BlueSpice\Privacy\Special\PrivacyConsent;
use SpecialPage;
use SpecialPageFactory;
use SpecialUserLogin;
use SpecialUserLogout;

class RedirectToConsent {

	/**
	 *
	 * @var array
	 */
	private static $exceptions = [
		SpecialUserLogin::class,
		SpecialUserLogout::class,
		PrivacyConsent::class,
	];

	/**
	 * @param SpecialPage $sp
	 * @param string $subPage
	 * @return bool
	 */
	public static function callback( SpecialPage $sp, $subPage ) {
		foreach ( static::$exceptions as $class ) {
			if ( $sp instanceof $class ) {
				return true;
			}
		}

		if ( !$sp->getUser()->isRegistered() ) {
			// Only applies to logged in users
			return true;
		}

		$module = new Consent( $sp->getContext() );
		if ( !$module->isPrivacyPolicyConsentMandatory() ) {
			return true;
		}
		if ( $module->hasUserConsented( $sp->getUser() ) ) {
			return true;
		}

		$sp->getOutput()->redirect(
			SpecialPageFactory::getPage( 'PrivacyConsent' )->getPageTitle()->getFullURL( [
				'returnto' => $sp->getPageTitle( $subPage )->getPrefixedDBkey()
			] )
		);

		return true;
	}

}

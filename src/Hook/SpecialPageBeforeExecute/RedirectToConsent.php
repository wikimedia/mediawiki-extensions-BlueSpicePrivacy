<?php

namespace BlueSpice\Privacy\Hook\SpecialPageBeforeExecute;

use BlueSpice\Privacy\Module\Consent;
use BlueSpice\Privacy\Special\PrivacyConsent;
use MediaWiki\MediaWikiServices;
use SpecialPage;

class RedirectToConsent {
	/**
	 * @param SpecialPage $sp
	 * @param string $subPage
	 * @return bool
	 */
	public static function callback( SpecialPage $sp, $subPage ) {
		if ( $sp instanceof PrivacyConsent ) {
			return true;
		}

		$module = new Consent( $sp->getContext() );
		if ( !$module->isPrivacyPolicyConsentMandatory() ) {
			return true;
		}
		if ( $module->hasUserConsented( $sp->getUser() ) ) {
			return true;
		}

		$specialPageFactory = MediaWikiServices::getInstance()->getSpecialPageFactory();
		$sp->getOutput()->redirect(
			$specialPageFactory->getPage( 'PrivacyConsent' )->getPageTitle()->getFullURL( [
				'returnto' => $sp->getPageTitle( $subPage )->getPrefixedDBkey()
			] )
		);

		return true;
	}

}

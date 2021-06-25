<?php

namespace BlueSpice\Privacy\Hook\OutputPageParserOutput;

use BlueSpice\Hook\OutputPageParserOutput;
use BlueSpice\Privacy\Module\Consent;
use ConfigException;
use SpecialPageFactory;

class RedirectToConsent extends OutputPageParserOutput {
	protected function doProcess() {
		$this->out->redirect(
			SpecialPageFactory::getPage( 'PrivacyConsent' )->getPageTitle()->getFullURL( [
				'returnto' => $this->out->getTitle()->getPrefixedDBkey()
			] )
		);
	}

	/**
	 * @return bool
	 * @throws ConfigException
	 */
	protected function skipProcessing() {
		if ( $this->getConfig()->get( 'PrivacyPrivacyPolicyMandatory' ) === false ) {
			return true;
		}
		if ( $this->out->getTitle() === null ) {
			return true;
		}
		if ( !$this->out->getUser()->isRegistered() ) {
			return true;
		}
		$sp = SpecialPageFactory::getTitleForAlias( 'PrivacyConsent' );
		if ( !$sp ) {
			return true;
		}
		if ( $this->out->getTitle()->equals( $sp ) ) {
			return true;
		}

		if ( $this->getModule()->hasUserConsented( $this->out->getUser() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @return Consent
	 */
	protected function getModule() {
		return new Consent( $this->getContext() );
	}
}

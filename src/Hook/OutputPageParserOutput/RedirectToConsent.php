<?php

namespace BlueSpice\Privacy\Hook\OutputPageParserOutput;

use BlueSpice\Hook\OutputPageParserOutput;
use BlueSpice\Privacy\Module\Consent;
use ConfigException;
use SpecialPageFactory;
use Title;

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

		if ( $this->getModule()->hasUserConsented( $this->out->getUser() ) ) {
			return true;
		}

		$exceptions = [
			$this->getContext()->msg( 'Privacypage' )->plain(),
			$this->getContext()->msg( 'Termsofservicepage' )->plain(),
		];
		$action = $this->getContext()->getRequest()->getText( 'action', 'view' );
		foreach ( $exceptions as $exceptionPage ) {
			$exceptionTitle = Title::newFromText( $exceptionPage );
			if (
				$exceptionTitle instanceof Title &&
				$this->out->getTitle()->equals( $exceptionTitle ) &&
				$action === 'view'
			) {
				return true;
			}
		}
		$sp = SpecialPageFactory::getTitleForAlias( 'PrivacyConsent' );
		if ( !$sp ) {
			return true;
		}
		if ( $this->out->getTitle()->equals( $sp ) ) {
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

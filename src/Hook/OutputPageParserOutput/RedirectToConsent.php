<?php

namespace BlueSpice\Privacy\Hook\OutputPageParserOutput;

use BlueSpice\Privacy\Module;
use BlueSpice\Privacy\Module\Consent;
use Config;
use ConfigFactory;
use IContextSource;
use MediaWiki\Hook\OutputPageParserOutputHook;
use MediaWiki\SpecialPage\SpecialPageFactory;
use OutputPage;
use Title;
use TitleFactory;

class RedirectToConsent implements OutputPageParserOutputHook {

	/** @var SpecialPageFactory */
	private $specialPageFactory;

	/** @var ConfigFactory */
	private $configFactory;

	/** @var TitleFactory */
	private $titleFactory;

	/**
	 * @param SpecialPageFactory $specialPageFactory
	 * @param ConfigFactory $configFactory
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		SpecialPageFactory $specialPageFactory, ConfigFactory $configFactory, TitleFactory $titleFactory
	) {
		$this->specialPageFactory = $specialPageFactory;
		$this->configFactory = $configFactory;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function onOutputPageParserOutput( $outputPage, $parserOutput ): void {
		if ( $this->skipProcessing( $outputPage ) ) {
			return;
		}
		$outputPage->redirect(
			$this->specialPageFactory->getPage( 'PrivacyConsent' )->getPageTitle()->getFullURL( [
				'returnto' => $outputPage->getTitle()->getPrefixedDBkey()
			] )
		);
	}

	/**
	 * @param OutputPage $outputPage
	 *
	 * @return bool
	 */
	protected function skipProcessing( OutputPage $outputPage ): bool {
		if ( $this->getConfig()->get( 'PrivacyPrivacyPolicyMandatory' ) === false ) {
			return true;
		}
		if ( $outputPage->getTitle() === null ) {
			return true;
		}
		if ( !$outputPage->getUser()->isRegistered() ) {
			return true;
		}

		if ( $this->getModule( $outputPage->getContext() )->hasUserConsented( $outputPage->getUser() ) ) {
			return true;
		}

		$exceptions = [
			$outputPage->getContext()->msg( 'Privacypage' )->plain(),
			$outputPage->getContext()->msg( 'Termsofservicepage' )->plain(),
		];
		$action = $outputPage->getContext()->getRequest()->getText( 'action', 'view' );
		foreach ( $exceptions as $exceptionPage ) {
			$exceptionTitle = $this->titleFactory->newFromText( $exceptionPage );
			if (
				$exceptionTitle instanceof Title &&
				$outputPage->getTitle()->equals( $exceptionTitle ) &&
				$action === 'view'
			) {
				return true;
			}
		}
		$sp = $this->specialPageFactory->getTitleForAlias( 'PrivacyConsent' );
		if ( !$sp ) {
			return true;
		}
		if ( $outputPage->getTitle()->equals( $sp ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param IContextSource $context
	 *
	 * @return Consent
	 */
	private function getModule( IContextSource $context ): Module {
		return new Consent( $context );
	}

	/**
	 * @return Config
	 */
	private function getConfig(): Config {
		return $this->configFactory->makeConfig( 'bsg' );
	}
}

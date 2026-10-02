<?php

namespace BlueSpice\Privacy\HookHandler;

use BlueSpice\Privacy\Module\Consent;
use BlueSpice\Privacy\ModuleRegistry;
use BlueSpice\Privacy\Special\PrivacyConsent;
use BlueSpice\Privacy\Util\PrivacyPageProvider;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Output\Hook\OutputPageParserOutputHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\SpecialPage\Hook\SpecialPageBeforeExecuteHook;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\Specials\SpecialUserLogin;
use MediaWiki\Specials\SpecialUserLogout;
use MediaWiki\Title\TitleFactory;

class RedirectToConsent implements SpecialPageBeforeExecuteHook, OutputPageParserOutputHook {

	/**
	 * @var ModuleRegistry
	 */
	private $moduleRegistry;

	/**
	 * @var SpecialPageFactory
	 */
	private $specialPageFactory;

	/**
	 * @var ConfigFactory
	 */
	private $configFactory;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/** @var PrivacyPageProvider */
	private PrivacyPageProvider $pageProvider;

	/*
	 * @param SpecialPageFactory $specialPageFactory
	 * @param ConfigFactory $configFactory
	 * @param TitleFactory $titleFactory
	 * @param ModuleRegistry $moduleRegistry
	 * @param PrivacyPageProvider $pageProvider
	 */
	public function __construct(
		SpecialPageFactory $specialPageFactory, ConfigFactory $configFactory,
		TitleFactory $titleFactory, ModuleRegistry $moduleRegistry, PrivacyPageProvider $pageProvider
	) {
		$this->moduleRegistry = $moduleRegistry;
		$this->specialPageFactory = $specialPageFactory;
		$this->configFactory = $configFactory;
		$this->titleFactory = $titleFactory;
		$this->pageProvider = $pageProvider;
	}

	/**
	 * @var array
	 */
	private static $exceptions = [
		SpecialUserLogin::class,
		SpecialUserLogout::class,
		PrivacyConsent::class,
	];

	/**
	 * @inheritDoc
	 */
	public function onSpecialPageBeforeExecute( $special, $subPage ) {
		$special->getOutput()->addModuleStyles( 'ext.bs.privacy.login.styles' );
		foreach ( static::$exceptions as $class ) {
			if ( $special instanceof $class ) {
				return true;
			}
		}

		if ( !$special->getUser()->isRegistered() ) {
			// Only applies to logged in users
			return true;
		}

		$module = $this->moduleRegistry->getModuleByKey( 'consent' );
		if ( !( $module instanceof Consent ) ) {
			return true;
		}
		if ( !$module->isPrivacyPolicyConsentMandatory() ) {
			return true;
		}
		if ( $module->hasUserConsented( $special->getUser() ) ) {
			return true;
		}

		$consentSpecial = $this->specialPageFactory->getPage( 'PrivacyConsent' );
		if ( !$consentSpecial ) {
			return true;
		}
		$special->getOutput()->redirect(
			$consentSpecial->getPageTitle()->getFullURL( [
				'returnto' => $special->getPageTitle( $subPage )->getPrefixedDBkey()
			] )
		);

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function onOutputPageParserOutput( $outputPage, $parserOutput ): void {
		if ( $this->shouldRedirectOnNonSpecial( $outputPage ) ) {
			return;
		}
		$consentSpecial = $this->specialPageFactory->getPage( 'PrivacyConsent' );
		if ( !$consentSpecial ) {
			return;
		}
		$outputPage->redirect(
			$consentSpecial->getPageTitle()->getFullURL( [
				'returnto' => $outputPage->getTitle()->getPrefixedDBkey()
			] )
		);
	}

	/**
	 * @param OutputPage $outputPage
	 * @return bool
	 */
	private function shouldRedirectOnNonSpecial( OutputPage $outputPage ): bool {
		if ( $this->configFactory->makeConfig( 'bsg' )->get( 'PrivacyPrivacyPolicyMandatory' ) === false ) {
			return true;
		}
		if ( $outputPage->getTitle() === null ) {
			return true;
		}
		if ( !$outputPage->getUser()->isRegistered() ) {
			return true;
		}

		$module = $this->moduleRegistry->getModuleByKey( 'consent' );
		if ( !( $module instanceof Consent ) ) {
			return true;
		}
		if ( $module->hasUserConsented( $outputPage->getUser() ) ) {
			return true;
		}

		$action = $outputPage->getContext()->getRequest()->getText( 'action', 'view' );
		if ( $action === 'view' ) {
			foreach ( $this->pageProvider->getNames() as $name ) {
				$privacyTitle = $this->pageProvider->getPrivacyTitle( $name );
				if ( !$privacyTitle ) {
					continue;
				}
				if ( $outputPage->getTitle()->equals( $privacyTitle ) ) {
					return true;
				}
			}
		}
		return $outputPage->getTitle() && $outputPage->getTitle()->isSpecial( 'PrivacyConsent' );
	}

}

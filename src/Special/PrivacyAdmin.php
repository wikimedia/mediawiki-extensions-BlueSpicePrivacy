<?php
namespace BlueSpice\Privacy\Special;

use MediaWiki\MediaWikiServices;

class PrivacyAdmin extends \SpecialPage {

	public function __construct() {
		parent::__construct( 'PrivacyAdmin', 'bs-privacy-admin' );
	}

	/**
	 *
	 * @param string $sub
	 */
	public function execute( $sub ) {
		parent::execute( $sub );

		$this->setUp();
		$this->output();
	}

	/**
	 *
	 * @return string
	 */
	protected function getGroupName() {
		return 'bluespice';
	}

	protected function setUp() {
		$this->getOutput()->addModuleStyles( 'ext.bluespice.privacy.styles' );
		$this->getOutput()->enableOOUI();

		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		$this->getOutput()->addJsConfigVars(
			'bsPrivacyRequestDeadline',
			$config->get( 'PrivacyRequestDeadline' )
		);
		$this->getOutput()->addJsConfigVars(
			'bsPrivacyEnableRequests',
			$config->get( 'PrivacyEnableRequests' )
		);

		// TODO: This kinda breaks "independent module" design
		$this->getOutput()->addJsConfigVars(
			'bsPrivacyConsentTypes',
			$config->get( 'PrivacyConsentTypes' )
		);

		$this->getOutput()->addModules( 'ext.bluespice.privacy.admin' );
	}

	protected function output() {
		$html = \Html::openElement( 'div', [
			'class' => 'bs-privacy-admin-container'
		] );

		$html .= \Html::element( 'div', [
			'id' => 'bs-privacy-admin-requests'
		] );

		$html .= \Html::element( 'div', [
			'id' => 'bs-privacy-admin-consents'
		] );

		$html .= \Html::closeElement( 'div' );

		$this->getOutput()->addHTML( $html );
	}
}

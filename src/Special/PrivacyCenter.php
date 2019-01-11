<?php

namespace BlueSpice\Privacy\Special;

use BlueSpice\Privacy\ModuleRegistry;

class PrivacyCenter extends \SpecialPage {

	public function __construct() {
		parent::__construct( 'PrivacyCenter' );
	}

	/**
	 *
	 * @param \User $user
	 * @return bool
	 */
	public function userCanExecute( \User $user ) {
		return $user->isLoggedIn();
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

	protected function getGroupName() {
		return 'bluespice';
	}

	protected function setUp() {
		$this->getOutput()->addModuleStyles( 'ext.bluespice.privacy.styles' );
		$this->getOutput()->enableOOUI();

		$this->getOutput()->addModules( 'ext.bluespice.privacy.user' );
	}

	protected function output() {
		$this->getOutput()->addSubtitle( wfMessage( 'bs-privacy-privacy-center-subtitle' )->plain() );

		$moduleRegistry = new ModuleRegistry();
		$modules = $moduleRegistry->getAllKeys();
		foreach ( $modules as $key ) {
			$moduleClass = $moduleRegistry->getModuleClass( $key );
			if ( class_exists( $moduleClass ) ) {
				$module = new $moduleClass( $this->getContext() );
				$this->getOutput()->addHTML( \Html::element( 'div', [
					'class' => "bs-privacy-user-section section-{$module->getModuleName()}",
					'data-requestable' => $module->isRequestable() ? 1 : 0
				] ) );
			}
		}
	}
}

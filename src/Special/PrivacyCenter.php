<?php

namespace BlueSpice\Privacy\Special;

use BlueSpice\Privacy\Module;
use BlueSpice\Privacy\ModuleRegistry;

class PrivacyCenter extends \BlueSpice\SpecialPage {

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
				$rlModule = $module->getRLModule( Module::MODULE_UI_TYPE_USER );
				if ( $rlModule === null ) {
					continue;
				}
				$data = [
					'class' => "bs-privacy-user-section section-{$module->getModuleName()}",
					'data-requestable' => $module->isRequestable() ? 1 : 0,
					'data-rl-module' => $rlModule
				];
				$widgetData = $module->getUIWidget( Module::MODULE_UI_TYPE_USER );

				if ( is_string( $widgetData ) ) {
					$data['data-callback'] = $widgetData;
				} elseif ( is_array( $widgetData ) ) {
					$data['data-callback'] = $widgetData['callback'];
					if ( isset( $widgetData['data'] ) ) {
						$data['data-config'] = \FormatJson::encode( $widgetData['data'] );
					}
				}

				$this->getOutput()->addHTML( \Html::element( 'div', $data ) );
			}
		}
	}
}

<?php

namespace BlueSpice\Privacy\Special;

use BlueSpice\Privacy\Module;
use BlueSpice\Privacy\ModuleRegistry;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Html\Html;
use MediaWiki\Json\FormatJson;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\User;

class PrivacyCenter extends SpecialPage {

	/**
	 *
	 * @var ModuleRegistry
	 */
	protected $moduleRegistry;

	/**
	 *
	 * @var ConfigFactory
	 */
	protected $configFactory;

	/**
	 * @param ModuleRegistry $moduleRegistry
	 * @param ConfigFactory $configFactory
	 */
	public function __construct( ModuleRegistry $moduleRegistry, ConfigFactory $configFactory ) {
		parent::__construct( 'PrivacyCenter' );
		$this->moduleRegistry = $moduleRegistry;
		$this->configFactory = $configFactory;
	}

	/**
	 *
	 * @param User $user
	 * @return bool
	 */
	public function userCanExecute( User $user ) {
		return $user->isRegistered();
	}

	/**
	 *
	 * @param string $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->setUp();
		$this->output();
	}

	/**
	 * @return void
	 */
	private function setUp() {
		$this->getOutput()->addModuleStyles( 'ext.bluespice.privacy.styles' );
		$this->getOutput()->enableOOUI();

		$this->getOutput()->addModules( 'ext.bluespice.privacy.user' );
	}

	/**
	 * @return void
	 */
	private function output() {
		$this->getOutput()->addSubtitle( wfMessage( 'bs-privacy-privacy-center-subtitle' )->plain() );

		$modules = $this->moduleRegistry->getAllModules();

		foreach ( $modules as $instance ) {
			$rlModule = $instance->getRLModule( Module::MODULE_UI_TYPE_USER );
			if ( $rlModule === null ) {
				continue;
			}
			$data = [
				'class' => "bs-privacy-user-section section-{$instance->getModuleName()}",
				'data-requestable' => $instance->isRequestable() ? 1 : 0,
				'data-rl-module' => $rlModule
			];
			$widgetData = $instance->getUIWidget( Module::MODULE_UI_TYPE_USER );

			if ( is_string( $widgetData ) ) {
				$data['data-callback'] = $widgetData;
			} elseif ( is_array( $widgetData ) ) {
				$data['data-callback'] = $widgetData['callback'];
				if ( isset( $widgetData['data'] ) ) {
					$data['data-config'] = FormatJson::encode( $widgetData['data'] );
				}
			}

			$this->getOutput()->addHTML( Html::element( 'div', $data ) );
		}
	}
}

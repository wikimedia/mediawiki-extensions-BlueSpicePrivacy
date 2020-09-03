<?php
namespace BlueSpice\Privacy\Special;

use BlueSpice\Privacy\Module;
use BlueSpice\Privacy\ModuleRegistry;
use MediaWiki\MediaWikiServices;

class PrivacyAdmin extends \BlueSpice\SpecialPage {

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

		$this->getOutput()->addModules( 'ext.bluespice.privacy.admin' );
	}

	protected function output() {
		$html = \Html::openElement( 'div', [
			'class' => 'bs-privacy-admin-container'
		] );

		$html .= \Html::element( 'div', [
			'id' => 'bs-privacy-admin-requests'
		] );

		$moduleRegistry = new ModuleRegistry();
		$modules = $moduleRegistry->getAllKeys();

		foreach ( $modules as $key ) {
			$moduleClass = $moduleRegistry->getModuleClass( $key );
			if ( class_exists( $moduleClass ) ) {
				$module = new $moduleClass( $this->getContext() );
				$rlModule = $module->getRLModule( Module::MODULE_UI_TYPE_ADMIN );
				if ( $rlModule === null ) {
					continue;
				}
				$data = [
					'class' => "bs-privacy-admin-section section-{$module->getModuleName()}",
					'data-rl-module' => $rlModule
				];
				$widgetData = $module->getUIWidget( Module::MODULE_UI_TYPE_ADMIN );

				if ( is_string( $widgetData ) ) {
					$data['data-callback'] = $widgetData;
				} elseif ( is_array( $widgetData ) ) {
					$data['data-callback'] = $widgetData['callback'];
					if ( isset( $widgetData['data'] ) ) {
						$data['data-config'] = \FormatJson::encode( $widgetData['data'] );
					}
				}

				$html .= \Html::element( 'div', $data );
			}
		}

		$html .= \Html::closeElement( 'div' );

		$this->getOutput()->addHTML( $html );
	}
}

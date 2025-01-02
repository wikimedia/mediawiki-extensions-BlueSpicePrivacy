<?php
namespace BlueSpice\Privacy\Special;

use BlueSpice\Privacy\Module;
use BlueSpice\Privacy\ModuleRegistry;
use Html;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\SpecialPage\SpecialPage;

class PrivacyAdmin extends SpecialPage {

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
		parent::__construct( 'PrivacyAdmin', 'bs-privacy-admin' );
		$this->moduleRegistry = $moduleRegistry;
		$this->configFactory = $configFactory;
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

	protected function setUp() {
		$this->getOutput()->addModuleStyles( 'ext.bluespice.privacy.styles' );
		$this->getOutput()->enableOOUI();

		$config = $this->configFactory->makeConfig( 'bsg' );
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
		$html = Html::openElement( 'div', [
			'class' => 'bs-privacy-admin-container'
		] );

		$html .= Html::element( 'div', [
			'id' => 'bs-privacy-admin-requests'
		] );

		$modules = $this->moduleRegistry->getAllModules();

		foreach ( $modules as $instance ) {
			$rlModule = $instance->getRLModule( Module::MODULE_UI_TYPE_ADMIN );
			if ( $rlModule === null ) {
				continue;
			}
			$data = [
				'class' => "bs-privacy-admin-section section-{$instance->getModuleName()}",
				'data-rl-module' => $rlModule
			];
			$widgetData = $instance->getUIWidget( Module::MODULE_UI_TYPE_ADMIN );
			if ( is_string( $widgetData ) ) {
				$data['data-callback'] = $widgetData;
			} elseif ( is_array( $widgetData ) ) {
				$data['data-callback'] = $widgetData['callback'];
				if ( isset( $widgetData['data'] ) ) {
					$data['data-config'] = \FormatJson::encode( $widgetData['data'] );
				}
			}

			$html .= Html::element( 'div', $data );
		}

		$html .= Html::closeElement( 'div' );

		$this->getOutput()->addHTML( $html );
	}
}

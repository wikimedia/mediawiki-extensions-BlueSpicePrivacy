<?php
namespace BlueSpice\Privacy\Special;

use BlueSpice\Privacy\Module;
use BlueSpice\Privacy\ModuleRegistry;
use MediaWiki\Permissions\PermissionStatus;
use MediaWiki\SpecialPage\FormSpecialPage;
use MediaWiki\Title\Title;
use OOUI\MessageWidget;

class PrivacyConsent extends FormSpecialPage {

	/** @var Module\Consent */
	private $module;

	/** @var ModuleRegistry */
	private $moduleRegistry;

	/**
	 * @param ModuleRegistry $moduleRegistry
	 */
	public function __construct( ModuleRegistry $moduleRegistry ) {
		parent::__construct( 'PrivacyConsent', '', false );
		$this->moduleRegistry = $moduleRegistry;
	}

	/**
	 *
	 * @param string $par
	 */
	public function execute( $par ) {
		$this->setHeaders();
		$this->getOutput()->enableOOUI();

		$this->module = $this->moduleRegistry->getModuleByKey( 'consent' );
		if ( !$this->module ) {
			$this->getOutput()->addHTML( new MessageWidget( [
				'type' => 'error',
				'label' => $this->msg( 'bs-privacy-api-error-missing-module', 'consent' )->parse()
			] ) );
			return;
		}

		if ( !$this->getUser()->isRegistered() ) {
			$this->getOutput()->showPermissionStatus(
				PermissionStatus::newFatal( 'apierror-mustbeloggedin-generic' )
			);
			return;
		}
		if ( $this->module->hasUserConsented( $this->getUser() ) ) {
			$this->getOutput()->addHTML( new MessageWidget( [
				'type' => 'info',
				'label' => $this->msg( 'bs-privacy-module-consent-accepted' )->parse()
			] ) );
			return;
		}

		$form = $this->getForm();
		$this->getOutput()->addWikiMsg( 'bs-privacy-consent-auth-step' );
		if ( $form->show() ) {
			$this->onSuccess();
		}
	}

	/**
	 * @return string
	 */
	protected function getDisplayFormat() {
		return 'ooui';
	}

	/**
	 * @return array
	 */
	protected function getFormFields() {
		return [
			'returnto' => [
				'type' => 'hidden',
				'default' => $this->getRequest()->getText( 'returnto', '' ),
				'name' => 'returnto'
			]
		] + $this->module->getAuthFormDescriptors( 'check' );
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function onSubmit( array $data ) {
		$status = $this->module->call( 'setConsent', [ 'consents' => $data ] );
		if ( $status->isOK() ) {
			$redir = $this->getRequest()->getText( 'returnto' );
			if ( !$redir ) {
				$redirTitle = Title::newMainPage();
			} else {
				$redirTitle = Title::newFromText( $redir );
			}
			if ( $redirTitle instanceof Title ) {
				$this->getOutput()->redirect( $redirTitle->getFullURL() );
				return;
			}
			$this->getOutput()->addHTML(
				new MessageWidget( [
					'type' => 'success',
					'label' => $this->msg( 'bs-privacy-module-consent-set-success' )->parse()
				] )
			);
		} else {
			$this->getOutput()->addHTML(
				new MessageWidget( [
					'type' => 'error',
					'label' => $this->msg( 'bs-privacy-module-consent-set-fail' )->parse()
				] )
			);
		}
	}
}

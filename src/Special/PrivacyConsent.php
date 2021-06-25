<?php
namespace BlueSpice\Privacy\Special;

use BlueSpice\Privacy\Module;
use Title;

class PrivacyConsent extends \FormSpecialPage {
	/** @var Module\Consent */
	private $module;

	public function __construct() {
		parent::__construct( 'PrivacyConsent', '', false );
		$this->module = new Module\Consent( $this->getContext() );
	}

	/**
	 *
	 * @param string $sub
	 */
	public function execute( $sub ) {
		$this->setHeaders();

		if ( !$this->getUser()->isRegistered() ) {
			$this->getOutput()->showPermissionsErrorPage( [
				'apierror-mustbeloggedin-generic'
			] );
			return;
		}
		if ( $this->module->hasUserConsented( $this->getUser() ) ) {
			$this->getOutput()->addWikiMsg( 'bs-privacy-module-consent-accepted' );
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
			// Sanity - but should never get here
			$this->getOutput()->addWikiMsg( 'bs-privacy-module-consent-set-success' );
		} else {
			$this->getOutput()->addWikiMsg( 'bs-privacy-module-consent-set-fail' );
		}
	}
}

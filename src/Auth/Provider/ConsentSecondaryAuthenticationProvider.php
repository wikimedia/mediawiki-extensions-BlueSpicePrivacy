<?php
namespace BlueSpice\Privacy\Auth\Provider;

use BlueSpice\Privacy\Auth\Request\ConsentAuthenticationRequest;
use BlueSpice\Privacy\Module\Consent;
use MediaWiki\Auth\AbstractSecondaryAuthenticationProvider;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\TemporaryPasswordAuthenticationRequest;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\User\Options\UserOptionsManager;
use MediaWiki\User\User;

class ConsentSecondaryAuthenticationProvider extends AbstractSecondaryAuthenticationProvider {
	/**
	 *
	 * @param string $action
	 * @param array $options
	 * @return array
	 */
	public function getAuthenticationRequests( $action, array $options ) {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function beginSecondaryAuthentication( $user, array $reqs ) {
		// If false, accepting of privacy policy will not be included in the login process
		if ( !$GLOBALS['bsgPrivacyPrivacyPolicyOnLogin'] ) {
			return AuthenticationResponse::newAbstain();
		}

		// Skip for pre-confirmed user
		if ( $user->getName() === 'NoConsentWikiSysop' ) {
			foreach ( $this->getModule()->getOptions() as $prefName ) {
				$this->getOptionsManager()->setOption( $user, $prefName, true );
			}
			$user->saveSettings();
			return AuthenticationResponse::newAbstain();
		}
		if ( !$this->getModule()->hasUserConsented( $user ) ) {
			return $this->returnUI();
		}

		return AuthenticationResponse::newAbstain();
	}

	/**
	 * @inheritDoc
	 */
	public function continueSecondaryAuthentication( $user, array $reqs ) {
		return $this->saveUserPrefs( $user, $reqs );
	}

	/**
	 * @param User $user
	 * @param User $creator
	 * @param array $reqs
	 * @return AuthenticationResponse
	 */
	public function beginSecondaryAccountCreation( $user, $creator, array $reqs ) {
		$temporary = AuthenticationRequest::getRequestByClass(
			$reqs, TemporaryPasswordAuthenticationRequest::class
		);
		if ( $temporary || !$creator->isAnon() ) {
			// If someone else creates account, or used temporary password
			// we don't want that user to be able to accept for the target user
			return AuthenticationResponse::newAbstain();
		}
		if ( !$this->getModule()->hasUserConsented( $user ) ) {
			return $this->returnUI();
		}

		return AuthenticationResponse::newAbstain();
	}

	/**
	 * @param User $user
	 * @param User $creator
	 * @param array $reqs
	 * @return AuthenticationResponse|void
	 */
	public function continueSecondaryAccountCreation( $user, $creator, array $reqs ) {
		return $this->saveUserPrefs( $user, $reqs );
	}

	/**
	 * @param User $user
	 * @param array $reqs
	 * @return AuthenticationResponse
	 */
	protected function saveUserPrefs( User $user, array $reqs ) {
		/** @var ConsentAuthenticationRequest $request */
		$request = AuthenticationRequest::getRequestByClass(
			$reqs,
			ConsentAuthenticationRequest::class
		);

		if ( !$request ) {
			return AuthenticationResponse::newAbstain();
		}

		$module = $this->getModule();
		foreach ( $module->getOptions() as $name => $prefName ) {
			if ( $request->$name === false ) {
				return AuthenticationResponse::newFail(
					Message::newFromKey( 'bs-privacy-consent-auth-fail' )
				);
			}
			$this->getOptionsManager()->setOption( $user, $prefName, true );
		}

		$user->saveSettings();
		return AuthenticationResponse::newPass( $user->getName() );
	}

	/**
	 * @return UserOptionsManager
	 */
	protected function getOptionsManager() {
		return MediaWikiServices::getInstance()->getUserOptionsManager();
	}

	/**
	 * @return Consent
	 */
	protected function getModule() {
		$registry = MediaWikiServices::getInstance()->getService( 'BlueSpicePrivacy.ModuleRegistry' );
		return $registry->getModuleByKey( 'consent' );
	}

	/**
	 * @return AuthenticationResponse
	 */
	protected function returnUI() {
		$request = new ConsentAuthenticationRequest();
		return AuthenticationResponse::newUI( [ $request ],
			Message::newFromKey( 'bs-privacy-consent-auth-step' ) );
	}
}

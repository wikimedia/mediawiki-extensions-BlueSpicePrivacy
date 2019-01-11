<?php

namespace BlueSpice\Privacy\Module;

use BlueSpice\Privacy\ModuleRequestable;
use BlueSpice\Privacy\Notifications\AnonymizationDone;
use BlueSpice\Privacy\Notifications\RequestAnonymizationDenied;

class Anonymization extends ModuleRequestable {

	/**
	 *
	 * @param string $func
	 * @param array $data
	 * @return \Status
	 */
	public function call( $func, $data ) {
		if ( !$this->verifyUser() ) {
			\Status::newFatal( wfMessage( 'bs-privacy-invalid-user' ) );
		}

		switch ( $func ) {
			case "getUsername":
				return $this->getAlternativeUsername();
				break;
			case "checkUsername":
				if ( !isset( $data['username'] ) ) {
					return \Status::newFatal( wfMessage( 'bs-privacy-missing-param', "username" ) );
				}
				return $this->checkUsername( $data['username'] );
				break;
			case "anonymize":
				if ( !isset( $data['username'] ) ) {
					return \Status::newFatal( wfMessage( 'bs-privacy-missing-param', "username" ) );
				}
				if ( !isset( $data['oldUsername'] ) ) {
					return \Status::newFatal( wfMessage( 'bs-privacy-missing-param', "oldUsername" ) );
				}
				return $this->runAnonymization( $data['oldUsername'], $data['username'] );
			default:
				return parent::call( $func, $data );
		}
	}

	protected function getAlternativeUsername() {
		do {
			$username = $this->getRandomUsername();
		} while ( $this->checkUsernameSimple( $username ) === false );

		return \Status::newGood( [
			'username' => $username
		] );
	}

	protected function checkUsername( $username ) {
		$username = $this->context->getLanguage()->ucfirst( $username );
		$user = \User::newFromName( $username );
		$invalid = !$user instanceof \User;

		if ( \User::isCreatableName( $username ) === false ) {
			$invalid = true;
		}

		$exists = false;
		if ( !$invalid ) {
			$exists = $user->getId() > 1;
		}

		return \Status::newGood( [
			'invalid' => $invalid ? 1 : 0,
			'exists' => $exists ? 1 : 0,
			'username' => $username
		] );
	}

	protected function runAnonymization( $oldUsername, $username ) {
		$username = $this->context->getLanguage()->ucfirst( $username );
		if ( $this->checkUsernameSimple( $username ) === false ) {
			return \Status::newFatal( wfMessage( 'bs-privacy-anonymization-api-invalid-username' ) );
		}

		$executingUser = $this->context->getUser();
		if ( !$this->isRequestable() && $executingUser->getName() !== $oldUsername ) {
			return \Status::newFatal( wfMessage( 'bs-privacy-api-username-mismatch' ) );
		}

		$status = $this->runHandlers( 'anonymize', [
			$oldUsername,
			$username
		] );

		if ( $status->isOK() ) {
			$this->logAction( [
				'oldUsername' => $oldUsername,
				'newUsername' => $username
			] );

			$notification = new AnonymizationDone(
				$this->context->getUser(),
				\Title::newMainPage(),
				$oldUsername,
				$username
			);
			$this->notify( $notification );
		}

		return $status;
	}

	/**
	 *
	 * @return string
	 */
	public function getModuleName() {
		return "anonymization";
	}

	/**
	 * Convenience function to retrieve simple bool
	 * value of whether the username is valid
	 *
	 * @param string $username
	 * @return bool
	 */
	protected function checkUsernameSimple( $username ) {
		$status = $this->checkUsername( $username );
		if ( $status->getValue()['invalid'] || $status->getValue()['exists'] ) {
			return false;
		}
		return true;
	}

	protected function getRandomUsername() {
		return "anon" . rand( 101, 99999 );
	}

	protected function submitRequest( $data ) {
		if ( !isset( $data['username'] ) || empty( $data['username'] ) ) {
			return \Status::newFatal( wfMessage( 'bs-privacy-missing-param', "username" ) );
		}
		$comment = wfMessage( 'bs-privacy-anonymization-request-comment', $data['username'] )->plain();
		$data['comment'] = $comment;

		return parent::submitRequest( $data );
	}

	protected function approveRequest( $requestId ) {
		if ( !$this->checkAdminPermissions() ) {
			return \Status::newFatal( 'bs-privacy-admin-access-denied' );
		}

		$request = $this->getRequestById( $requestId );
		if ( !$request ) {
			return \Status::newFatal( 'bs-privacy-admin-invalid-request' );
		}

		$data = unserialize( $request->pr_data );
		$status = $this->runAnonymization( $data['oldUsername'], $data['username'] );

		if ( !$status->isOK() ) {
			return $status;
		}

		parent::approveRequest( $requestId );
		return $status;
	}

	/**
	 *
	 * @param \stdClass $request
	 * @param string $comment
	 * @return RequestAnonymizationDenied
	 */
	public function getRequestDeniedNotification( $request, $comment ) {
		$requestData = unserialize( $request->pr_data );

		return new RequestAnonymizationDenied(
			$this->context->getUser(),
			\Title::newMainPage(),
			$requestData['oldUsername'],
			$requestData['username'],
			$comment
		);
	}
}

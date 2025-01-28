<?php

namespace BlueSpice\Privacy\Module;

use BlueSpice\Privacy\Event\AnonymizationDone;
use BlueSpice\Privacy\Event\AnonymizationRejected;
use BlueSpice\Privacy\ModuleRequestable;
use Exception;
use MediaWiki\MediaWikiServices;
use MediaWiki\Status\Status;
use MediaWiki\User\User;
use MWStake\MediaWiki\Component\Events\NotificationEvent;

class Anonymization extends ModuleRequestable {

	/**
	 *
	 * @param string $func
	 * @param array $data
	 * @return Status
	 * @throws Exception
	 */
	public function call( $func, $data ) {
		if ( !$this->verifyUser() ) {
			Status::newFatal( wfMessage( 'bs-privacy-invalid-user' ) );
		}

		switch ( $func ) {
			case "getUsername":
				return $this->getAlternativeUsername();
			case "checkUsername":
				if ( !isset( $data['username'] ) ) {
					return Status::newFatal( wfMessage( 'bs-privacy-missing-param', "username" ) );
				}
				return $this->checkUsername( $data['username'] );
			case "anonymize":
				if ( !isset( $data['username'] ) ) {
					return Status::newFatal( wfMessage( 'bs-privacy-missing-param', "username" ) );
				}
				if ( !isset( $data['oldUsername'] ) ) {
					return Status::newFatal( wfMessage( 'bs-privacy-missing-param', "oldUsername" ) );
				}
				return $this->runAnonymization( $data['oldUsername'], $data['username'] );
			default:
				return parent::call( $func, $data );
		}
	}

	/**
	 *
	 * @return Status
	 */
	protected function getAlternativeUsername() {
		do {
			$username = $this->getRandomUsername();
		} while ( $this->checkUsernameSimple( $username ) === false );

		return Status::newGood( [
			'username' => $username
		] );
	}

	/**
	 *
	 * @param string $username
	 * @return Status
	 */
	protected function checkUsername( $username ) {
		$services = MediaWikiServices::getInstance();
		$username = $this->language->ucfirst( $username );
		$user = $services->getUserFactory()->newFromName( (string)$username );
		$invalid = !$user instanceof User;

		if ( $services->getUserNameUtils()->isCreatable( $username ) === false ) {
			$invalid = true;
		}

		$exists = false;
		if ( !$invalid ) {
			$exists = $user->getId() > 1;
		}

		return Status::newGood( [
			'invalid' => $invalid ? 1 : 0,
			'exists' => $exists ? 1 : 0,
			'username' => $username
		] );
	}

	/**
	 *
	 * @param string $oldUsername
	 * @param string $username
	 * @return Status
	 * @throws Exception
	 */
	protected function runAnonymization( $oldUsername, $username ) {
		$username = $this->language->ucfirst( $username );
		if ( $this->checkUsernameSimple( $username ) === false ) {
			return Status::newFatal( wfMessage( 'bs-privacy-anonymization-api-invalid-username' ) );
		}

		if ( !$this->isRequestable() && $this->user->getName() !== $oldUsername ) {
			return Status::newFatal( wfMessage( 'bs-privacy-api-username-mismatch' ) );
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

			$user = $this->userFactory->newFromName( $username );
			if ( $user ) {
				$event = new AnonymizationDone( $this->user, $oldUsername, $user );
				$this->notify( $event );
			}

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

	/**
	 *
	 * @return string
	 */
	protected function getRandomUsername() {
		return "anon" . rand( 101, 99999 );
	}

	/**
	 *
	 * @param array $data
	 * @return Status
	 */
	protected function submitRequest( $data ) {
		if ( !isset( $data['username'] ) || empty( $data['username'] ) ) {
			return Status::newFatal( wfMessage( 'bs-privacy-missing-param', "username" ) );
		}
		$comment = wfMessage( 'bs-privacy-anonymization-request-comment', $data['username'] )->plain();
		$data['comment'] = $comment;

		return parent::submitRequest( $data );
	}

	/**
	 *
	 * @param int $requestId
	 * @return Status
	 * @throws Exception
	 */
	protected function approveRequest( $requestId ) {
		if ( !$this->checkAdminPermissions() ) {
			return Status::newFatal( 'bs-privacy-admin-access-denied' );
		}

		$request = $this->getRequestById( $requestId );
		if ( !$request ) {
			return Status::newFatal( 'bs-privacy-admin-invalid-request' );
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
	 * @return NotificationEvent
	 */
	public function getRequestDeniedNotification( $request, $comment ) {
		$requestData = unserialize( $request->pr_data );

		$user = $this->userFactory->newFromName( $requestData['oldUsername'] );
		if ( !$user ) {
			$user = $this->userFactory->newAnonymous();
		}
		return new AnonymizationRejected(
			$this->user,
			$user,
			$requestData['username'],
			$comment
		);
	}

	/**
	 * Get RL modules required to run this module
	 * @param string $type
	 * @return string
	 */
	public function getRLModule( $type ) {
		if ( $type === static::MODULE_UI_TYPE_USER ) {
			return "ext.bs.privacy.module.anonymization.user";
		}

		return null;
	}

	/**
	 * @param string $type
	 * @return array|null
	 */
	public function getUIWidget( $type ) {
		if ( $type === static::MODULE_UI_TYPE_USER ) {
			return [
				"callback" => "bs.privacy.widget.Anonymize",
				"data" => [
					"userName" => $this->user->getName()
				]
			];
		}

		return null;
	}
}

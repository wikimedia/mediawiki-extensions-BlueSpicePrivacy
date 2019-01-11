<?php

namespace BlueSpice\Privacy\Module;

use BlueSpice\Privacy\ModuleRequestable;
use BlueSpice\Privacy\Notifications\RequestDeletionApproved;
use BlueSpice\Privacy\Notifications\RequestDeletionDenied;

class Deletion extends ModuleRequestable {

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

		if ( $func === 'delete' ) {
			if ( !isset( $data['username'] ) ) {
				return \Status::newFatal( wfMessage( 'bs-privacy-missing-param', "username" ) );
			}
			return $this->delete( $data['username'] );
		}

		return parent::call( $func, $data );
	}

	/**
	 *
	 * @param string $username
	 * @return \Status
	 */
	protected function delete( $username ) {
		$executingUser = $this->context->getUser();

		$user = \User::newFromName( $username );
		if ( $user->getId() === 0 ) {
			return \Status::newFatal( 'bs-privacy-invalid-user' );
		}

		if ( $this->requestsEnabled === false && $executingUser->getName() !== $user->getName() ) {
			return \Status::newFatal( 'bs-privacy-api-username-mismatch' );
		}

		$deletedUser = $this->assertDeletedUser();
		if ( !$deletedUser || $deletedUser->getId() === 0 ) {
			return \Status::newFatal( 'bs-privacy-cannot-assert-deleted-user' );
		}

		$status = $this->runHandlers( 'delete', [
			$user,
			$deletedUser
		] );

		if ( $status->isOK() ) {
			$this->logAction( [
				'username' => $username
			] );
		} else {
			// Notify user if deleting their account failed
			$notification = new RequestDeletionApproved(
				$this->context->getUser(),
				\Title::newMainPage(),
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
		return "deletion";
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

		// Send notification while user still exists that deletion process is being carried out
		$notification = new RequestDeletionApproved(
			$this->context->getUser(),
			\Title::newMainPage(),
			$data['username']
		);
		$this->notify( $notification );

		$status = $this->delete( $data['username'] );

		if ( !$status->isOK() ) {
			return $status;
		}

		parent::approveRequest( $requestId );
		return $status;
	}

	/**
	 * Makes sure aggregate "Deleted user" is
	 * created and exits.
	 *
	 * @return \User|null if Deleted user cannot be created
	 * @throws \ConfigException
	 * @throws \MWException
	 */
	protected function assertDeletedUser() {
		$services = \MediaWiki\MediaWikiServices::getInstance();
		$deletedUsername = $services->getConfigFactory()->makeConfig( 'bsg' )->get(
			'PrivacyDeleteUsername'
		);

		$deletedUser = \User::newFromName( $deletedUsername );
		if ( !$deletedUser ) {
			return null;
		}

		if ( $deletedUser->getId() === 0 ) {
			$status = $deletedUser->addToDatabase();
			if ( !$status->isOK() ) {
				return null;
			}

			// Block user
			$block = new \Block();
			$block->setTarget( $deletedUser );
			$block->setBlocker( $this->context->getUser() );
			$block->mExpiry = 'infinity';
			$block->insert();
		}
		return $deletedUser;
	}

	/**
	 *
	 * @param \stdClass $request
	 * @param string $comment
	 * @return RequestDeletionDenied
	 */
	public function getRequestDeniedNotification( $request, $comment ) {
		$requestData = unserialize( $request->pr_data );

		return new RequestDeletionDenied(
			$this->context->getUser(),
			\Title::newMainPage(),
			$requestData['username'],
			$comment
		);
	}
}

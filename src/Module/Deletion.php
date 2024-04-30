<?php

namespace BlueSpice\Privacy\Module;

use BlueSpice\Privacy\Event\DeletionDone;
use BlueSpice\Privacy\Event\DeletionFailed;
use BlueSpice\Privacy\Event\DeletionRejected;
use BlueSpice\Privacy\ModuleRequestable;
use MediaWiki\Block\DatabaseBlock;
use MWStake\MediaWiki\Component\Events\NotificationEvent;

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

		$user = $this->services->getUserFactory()->newFromName( $username );
		if ( !$user || !$user->isRegistered() ) {
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
			$this->notify( new DeletionFailed( $user ) );
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

	/**
	 *
	 * @param int $requestId
	 * @return \Status
	 */
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
		$user = $this->services->getUserFactory()->newFromName( $data['username'] );
		if ( $user ) {
			$event = new DeletionDone( $this->context->getUser(), $user );
			$this->notify( $event );
		}

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
		$deletedUsername = $this->services->getConfigFactory()->makeConfig( 'bsg' )->get(
			'PrivacyDeleteUsername'
		);

		$deletedUser = $this->services->getUserFactory()->newFromName( $deletedUsername );
		if ( !$deletedUser ) {
			return null;
		}

		if ( $deletedUser->getId() === 0 ) {
			$status = $deletedUser->addToDatabase();
			if ( !$status->isOK() ) {
				return null;
			}

			// Block user
			$block = new DatabaseBlock();
			$block->setTarget( $deletedUser );
			$block->setBlocker( $this->context->getUser() );
			$block->setExpiry( 'infinity' );
			$this->services->getDatabaseBlockStore()->insertBlock( $block );
		}
		return $deletedUser;
	}

	/**
	 *
	 * @param \stdClass $request
	 * @param string $comment
	 * @return NotificationEvent
	 */
	public function getRequestDeniedNotification( $request, $comment ) {
		$requestData = unserialize( $request->pr_data );

		$user = $this->services->getUserFactory()->newFromName( $requestData['username'] );
		if ( !$user ) {
			$user = $this->services->getUserFactory()->newAnonymous();
		}
		return new DeletionRejected( $this->context->getUser(), $user, $comment );
	}

	/**
	 * Get RL modules required to run this module
	 * @param string $type
	 * @return string|null
	 */
	public function getRLModule( $type ) {
		if ( $type === static::MODULE_UI_TYPE_USER ) {
			return "ext.bs.privacy.module.deletion.user";
		}

		return null;
	}

	/**
	 * @param string $type
	 * @return string|array|null
	 */
	public function getUIWidget( $type ) {
		if ( $type === static::MODULE_UI_TYPE_USER ) {
			return "bs.privacy.widget.Delete";
		}

		return null;
	}
}

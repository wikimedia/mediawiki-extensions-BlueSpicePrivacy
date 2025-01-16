<?php

namespace BlueSpice\Privacy\Module;

use BlueSpice\Privacy\Event\DeletionFailed;
use BlueSpice\Privacy\Event\DeletionRejected;
use BlueSpice\Privacy\ModuleRequestable;
use Config;
use ConfigException;
use Exception;
use MailAddress;
use MediaWiki\Block\DatabaseBlock;
use MediaWiki\Block\DatabaseBlockStore;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Extension\NotifyMe\EventFactory;
use MediaWiki\Language\Language;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\User\UserFactory;
use Message;
use MWStake\MediaWiki\Component\Events\NotificationEvent;
use MWStake\MediaWiki\Component\Events\Notifier;
use Status;
use Throwable;
use User;
use UserMailer;
use Wikimedia\Rdbms\ILoadBalancer;

class Deletion extends ModuleRequestable {

	/**
	 * @var DatabaseBlockStore
	 */
	protected $databaseBlockStore;

	/**
	 * @var Config
	 */
	protected $mainConfig;

	/**
	 * @param ILoadBalancer $lb
	 * @param Notifier $notifier
	 * @param PermissionManager $permissionManager
	 * @param ConfigFactory $configFactory
	 * @param UserFactory $userFactory
	 * @param Language $language
	 * @param EventFactory $eventFactory
	 * @param DatabaseBlockStore $databaseBlockStore
	 * @param Config $mainConfig
	 */
	public function __construct(
		ILoadBalancer $lb, Notifier $notifier, PermissionManager $permissionManager, ConfigFactory $configFactory,
		UserFactory $userFactory, Language $language, EventFactory $eventFactory,
		DatabaseBlockStore $databaseBlockStore, Config $mainConfig
	) {
		parent::__construct(
			$lb, $notifier, $permissionManager, $configFactory, $userFactory, $language, $eventFactory
		);
		$this->databaseBlockStore = $databaseBlockStore;
		$this->mainConfig = $mainConfig;
	}

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

		if ( $func === 'delete' ) {
			if ( !isset( $data['username'] ) ) {
				return Status::newFatal( wfMessage( 'bs-privacy-missing-param', "username" ) );
			}
			return $this->delete( $data['username'] );
		}

		return parent::call( $func, $data );
	}

	/**
	 *
	 * @param string $username
	 * @return Status
	 * @throws Exception
	 */
	protected function delete( $username ) {
		$user = $this->userFactory->newFromName( $username );
		if ( !$user || !$user->isRegistered() ) {
			return Status::newFatal( 'bs-privacy-invalid-user' );
		}

		if ( !$this->isRequestable() && $this->user->getName() !== $user->getName() ) {
			return Status::newFatal( 'bs-privacy-api-username-mismatch' );
		}

		$deletedUser = $this->assertDeletedUser();
		if ( !$deletedUser || $deletedUser->getId() === 0 ) {
			return Status::newFatal( 'bs-privacy-cannot-assert-deleted-user' );
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
	 * @return Status
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

		// Send notification while user still exists that deletion process is being carried out
		$user = $this->userFactory->newFromName( $data['username'] );
		if ( $user && $user->canReceiveEmail() ) {
			try {
				$this->sendDeletionConfirmation( $user );
			} catch ( Throwable $ex ) {
				// Do nothing, failing to send mail does not fail deletion
			}
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
	 * @return User|null if Deleted user cannot be created
	 * @throws ConfigException
	 */
	protected function assertDeletedUser() {
		$deletedUsername = $this->configFactory->makeConfig( 'bsg' )->get(
			'PrivacyDeleteUsername'
		);

		$deletedUser = $this->userFactory->newFromName( $deletedUsername );
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
			$block->setBlocker( $this->user );
			$block->setExpiry( 'infinity' );
			$this->databaseBlockStore->insertBlock( $block );
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

		$user = $this->userFactory->newFromName( $requestData['username'] );
		if ( !$user ) {
			$user = $this->userFactory->newAnonymous();
		}
		return new DeletionRejected( $this->user, $user, $comment );
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

	/**
	 * @param User $user
	 * @return void
	 */
	private function sendDeletionConfirmation( User $user ) {
		$msg = Message::newFromKey( 'bs-privacy-event-deletion-done' )->params( $this->user->getName() );
		UserMailer::send(
			MailAddress::newFromUser( $user ),
			new MailAddress(
				$this->mainConfig->get( 'PasswordSender' ),
				Message::newFromKey( 'emailsender' )->text()
			),
			Message::newFromKey( 'bs-privacy-event-deletion-done-desc' )->text(),
			$msg->text()
		);
	}

}

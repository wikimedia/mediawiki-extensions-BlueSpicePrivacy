<?php

namespace BlueSpice\Privacy;

use Exception;
use ManualLogEntry;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use MWStake\MediaWiki\Component\Events\Notifier;
use Status;
use Title;
use Wikimedia\Rdbms\ILoadBalancer;

abstract class Module implements IModule {
	public const MODULE_UI_TYPE_ADMIN = 'admin';
	public const MODULE_UI_TYPE_USER = 'user';

	/** @var ILoadBalancer */
	protected ILoadBalancer $lb;

	/**
	 * @var UserIdentity
	 */
	protected UserIdentity $user;

	/**
	 * @var Notifier
	 */
	protected Notifier $notifier;

	/**
	 * @var PermissionManager
	 */
	protected PermissionManager $permissionManager;

	/**
	 * @param ILoadBalancer $lb
	 * @param Notifier $notifier
	 * @param PermissionManager $permissionManager
	 */
	public function __construct( ILoadBalancer $lb, Notifier $notifier, PermissionManager $permissionManager ) {
		$this->lb = $lb;
		$this->notifier = $notifier;
		$this->permissionManager = $permissionManager;
	}

	/**
	 * @param UserIdentity $user
	 * @return void
	 */
	public function setUser( UserIdentity $user ) {
		$this->user = $user;
	}

	/**
	 * Run all handlers for this module
	 *
	 * @param string $action
	 * @param array $data
	 *
	 * @return Status
	 */
	public function runHandlers( $action, $data ) {
		$status = Status::newGood();
		$dbw = $this->lb->getConnection( DB_PRIMARY );
		$dbw->startAtomic( __METHOD__ );

		foreach ( $this->getHandlers() as $handler ) {
			if ( class_exists( $handler ) ) {
				$handlerObject = new $handler( $dbw );
				$result = call_user_func_array( [ $handlerObject, $action ], $data );

				if ( $result instanceof Status && $result->isOk() === false ) {
					$status = $result;
					break;
				}
				if ( $result === false ) {
					// An error occurred
					$status = Status::newFatal( wfMessage( 'bs-privacy-handler-error', $handler ) );
					break;
				}
			}
		}

		$dbw->endAtomic( __METHOD__ );

		return $status;
	}

	/**
	 *
	 * @return array
	 */
	public function getHandlers() {
		$handlerRegistry = new HandlerRegistry();
		return $handlerRegistry->getAllHandlers();
	}

	/**
	 *
	 * @return false
	 */
	public function isRequestable() {
		return false;
	}

	/**
	 *
	 * @return bool
	 */
	protected function verifyUser() {
		return $this->user->isRegistered();
	}

	/**
	 *
	 * @return bool
	 */
	protected function checkAdminPermissions() {
		return $this->permissionManager->userHasRight(
			$this->user,
			'bs-privacy-admin'
		);
	}

	/**
	 *
	 * @param array $params
	 */
	protected function logAction( $params = [] ) {
		$entry = new ManualLogEntry( 'bs-privacy', $this->getModuleName() );

		$title = Title::newMainPage();
		$entry->setTarget( $title );
		$entry->setParameters( $this->buildLogParams( $params ) );
		$entry->setPerformer( $this->user );
		$entry->insert();
	}

	/**
	 *
	 * @param array $params
	 * @return array
	 */
	protected function buildLogParams( $params ) {
		$logParams = [];
		$cnt = 4;
		foreach ( $params as $name => $value ) {
			$logParams["$cnt::$name"] = $value;
			$cnt++;
		}
		return $logParams;
	}

	/**
	 *
	 * @param INotificationEvent $event
	 * @throws Exception
	 */
	protected function notify( INotificationEvent $event ) {
		$this->notifier->emit( $event );
	}

	/**
	 * @return string
	 */
	abstract public function getModuleName();
}

<?php

namespace BlueSpice\Privacy;

use BlueSpice\BaseNotification;
use MediaWiki\MediaWikiServices;

abstract class Module implements IModule {
	const MODULE_UI_TYPE_ADMIN = 'admin';
	const MODULE_UI_TYPE_USER = 'user';

	/**
	 * @var \IContextSource
	 */
	protected $context;

	/**
	 *
	 * @param \IContextSource $context
	 */
	public function __construct( $context ) {
		$this->context = $context;
	}

	/**
	 * Run all handlers for this module
	 *
	 * @param string $action
	 * @param array $data
	 *
	 * @return \Status
	 */
	public function runHandlers( $action, $data ) {
		$status = \Status::newGood();
		$db = wfGetDB( DB_MASTER );
		$db->startAtomic( __METHOD__ );

		foreach ( $this->getHandlers() as $handler ) {
			if ( class_exists( $handler ) ) {
				$handlerObject = new $handler( $db );
				$result = call_user_func_array( [ $handlerObject, $action ], $data );

				if ( $result instanceof \Status && $result->isOk() === false ) {
					$status = $result;
					break;
				}
				if ( $result === false ) {
					// An error occurred
					$status = \Status::newFatal( wfMessage( 'bs-privacy-handler-error', $handler ) );
					break;
				}
			}
		}

		$db->endAtomic( __METHOD__ );

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
		return $this->context->getUser()->getId() > 0;
	}

	/**
	 *
	 * @return bool
	 */
	protected function checkAdminPermissions() {
		return MediaWikiServices::getInstance()->getPermissionManager()->userHasRight(
			$this->context->getUser(),
			'bs-privacy-admin'
		);
	}

	/**
	 *
	 * @param array $params
	 */
	protected function logAction( $params = [] ) {
		$entry = new \ManualLogEntry( 'bs-privacy', $this->getModuleName() );

		$title = \Title::newMainPage();
		$entry->setTarget( $title );
		$entry->setParameters( $this->buildLogParams( $params ) );
		$entry->setPerformer( $this->context->getUser() );
		$entry->insert();
	}

	/**
	 *
	 * @param array $params
	 * @return arraY
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
	 * @param BaseNotification $notification
	 */
	protected function notify( $notification ) {
		$notificationsManager = MediaWikiServices::getInstance()
			->getService( 'BSNotificationManager' );
		$notifier = $notificationsManager->getNotifier();
		$notifier->notify( $notification );
	}

	/**
	 * @return string
	 */
	abstract public function getModuleName();
}

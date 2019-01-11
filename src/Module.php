<?php

namespace BlueSpice\Privacy;

use BlueSpice\Services;

abstract class Module implements IModule {

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

	protected function verifyUser() {
		return $this->context->getUser()->getId() > 0;
	}

	protected function checkAdminPermissions() {
		return $this->context->getUser()->isAllowed( 'bs-privacy-admin' ) !== false;
	}

	protected function logAction( $params = [] ) {
		$entry = new \ManualLogEntry( 'bs-privacy', $this->getModuleName() );

		$title = \Title::newMainPage();
		$entry->setTarget( $title );
		$entry->setParameters( $this->buildLogParams( $params ) );
		$entry->setPerformer( $this->context->getUser() );
		$entry->insert();
	}

	protected function buildLogParams( $params ) {
		$logParams = [];
		$cnt = 4;
		foreach ( $params as $name => $value ) {
			$logParams["$cnt::$name"] = $value;
			$cnt++;
		}
		return $logParams;
	}

	protected function notify( $notification ) {
		$notificationsManager = Services::getInstance()
			->getBSNotificationManager();
		$notifier = $notificationsManager->getNotifier();
		$notifier->notify( $notification );
	}

	/**
	 * @return string
	 */
	abstract public function getModuleName();
}

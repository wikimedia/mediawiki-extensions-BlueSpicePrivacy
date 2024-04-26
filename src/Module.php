<?php

namespace BlueSpice\Privacy;

use Exception;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\Events\NotificationEvent;
use MWStake\MediaWiki\Component\Events\Notifier;

abstract class Module implements IModule {
	public const MODULE_UI_TYPE_ADMIN = 'admin';
	public const MODULE_UI_TYPE_USER = 'user';

	/**
	 * @var \IContextSource
	 */
	protected $context;

	/** @var MediaWikiServices */
	protected $services = null;

	/**
	 *
	 * @param \IContextSource $context
	 */
	public function __construct( $context ) {
		$this->context = $context;
		$this->services = MediaWikiServices::getInstance();
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
		$dbw = $this->services->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->startAtomic( __METHOD__ );

		foreach ( $this->getHandlers() as $handler ) {
			if ( class_exists( $handler ) ) {
				$handlerObject = new $handler( $dbw );
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
		return $this->context->getUser()->getId() > 0;
	}

	/**
	 *
	 * @return bool
	 */
	protected function checkAdminPermissions() {
		return $this->services->getPermissionManager()->userHasRight(
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
	 * @param NotificationEvent $event
	 * @throws Exception
	 */
	protected function notify( $event ) {
		if ( !( $event instanceof NotificationEvent ) ) {
			// B/C
			return;
		}
		/** @var Notifier $notifier */
		$notifier = $this->services->getService( 'MWStake.Notifier' );
		$notifier->emit( $event );
	}

	/**
	 * @return string
	 */
	abstract public function getModuleName();
}

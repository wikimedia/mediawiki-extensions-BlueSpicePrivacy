<?php

namespace BlueSpice\Privacy;

use BlueSpice\INotification;
use BlueSpice\Privacy\Notifications\RequestSubmitted;
use MediaWiki\MediaWikiServices;

abstract class ModuleRequestable extends Module {
	const TABLE_NAME = 'bs_privacy_request';
	const REQUEST_STATUS_PENDING = 1;
	const REQUEST_STATUS_DENIED = 2;
	const REQUEST_STATUS_APPROVED = 3;

	const REQUEST_OPEN = 1;
	const REQUEST_CLOSED = 0;

	protected $database;
	protected $requestsEnabled;

	/**
	 *
	 * @param \IContextSource $context
	 */
	public function __construct( $context ) {
		parent::__construct( $context );

		$services = MediaWikiServices::getInstance();
		$this->database = $services->getDBLoadBalancer()->getConnection( DB_MASTER );
		$this->requestsEnabled = $services->getConfigFactory()
			->makeConfig( 'bsg' )->get( 'PrivacyEnableRequests' );
	}

	/**
	 *
	 * @param string $func
	 * @param array $data
	 * @return \Status
	 */
	public function call( $func, $data ) {
		switch ( $func ) {
			case "checkStatus":
				return $this->checkStatus();
				break;
			case "getRequests":
				return $this->getRequests();
				break;
			case "submitRequest":
				return $this->submitRequest( $data );
				break;
			case "cancelRequest":
				return $this->cancelRequest();
				break;
			case "closeRequest":
				return $this->closeRequest();
				break;
			case "approveRequest":
				if ( !isset( $data['requestId'] ) ) {
					return \Status::newFatal(
						wfMessage( 'bs-privacy-missing-param', "requestId" )
					);
				}
				return $this->approveRequest( $data['requestId'] );
				break;
			case "denyRequest":
				if ( !isset( $data['requestId'] ) ) {
					return \Status::newFatal(
						wfMessage( 'bs-privacy-missing-param', "requestId" )
					);
				}
				$comment = isset( $data['comment'] ) ? $data['comment'] : '';
				return $this->denyRequest( $data['requestId'], $comment );
				break;
			default:
				return \Status::newFatal(
					wfMessage( 'bs-privacy-module-no-function', $func )
				);
		}
	}

	/**
	 * If requests are enabled globally,
	 * every module deriving from this class
	 * will support them
	 *
	 * @return bool
	 */
	public function isRequestable() {
		return $this->requestsEnabled;
	}

	/**
	 * Gets status of the request
	 *
	 * @return \Status
	 */
	protected function checkStatus() {
		$row = $this->database->selectRow(
			static::TABLE_NAME,
			'*',
			[
				'pr_user' => $this->context->getUser()->getId(),
				'pr_module' => $this->getModuleName(),
				'pr_open' => static::REQUEST_OPEN
			]
		);

		$statusData = [
			'status' => 0
		];
		if ( $row ) {
			$statusData['status'] = $row->pr_status;
			$statusData['comment'] = $row->pr_admin_comment;
		}

		return \Status::newGood( $statusData );
	}

	/**
	 * Gets all requests
	 *
	 * @return \Status
	 */
	protected function getRequests() {
		if ( !$this->checkAdminPermissions() ) {
			return \Status::newFatal( 'bs-privacy-admin-access-denied' );
		}

		$res = $this->database->select(
			static::TABLE_NAME,
			'*',
			[ 'pr_module' => $this->getModuleName() ]
		);

		if ( !$res ) {
			return \Status::newFatal( 'bs-privacy-admin-get-requests-failed' );
		}

		$requests = [];
		foreach ( $res as $row ) {
			$user = \User::newFromId( $row->pr_user );

			// Get how many days ago request was made
			$ts = wfTimestamp( TS_UNIX, $row->pr_timestamp );
			$today = wfTimestamp( TS_UNIX );
			$diff = $today - $ts;
			$daysAgo = floor( $diff / ( 24 * 60 * 60 ) );

			if ( !$daysAgo ) {
				$tsWithDaysAgo = wfMessage(
					'bs-privacy-timestamp-with-days-ago-today',
					$this->context->getLanguage()->userTimeAndDate(
						$row->pr_timestamp,
						$this->context->getUser()
					)
				)->parse();
			} else {
				$tsWithDaysAgo = wfMessage(
					'bs-privacy-timestamp-with-days-ago',
					$this->context->getLanguage()->userTimeAndDate(
						$row->pr_timestamp,
						$this->context->getUser()
					),
					$daysAgo
				)->parse();
			}

			$requests[] = [
				'requestId' => $row->pr_id,
				'userId' => $user->getId(),
				'userName' => $user->getName(),
				'module' => $row->pr_module,
				'rawTimestamp' => $row->pr_timestamp,
				'daysAgo' => $daysAgo,
				'timestampWithDaysAgo' => $tsWithDaysAgo,
				'timestamp' => $this->context->getLanguage()->userTimeAndDate(
					$row->pr_timestamp,
					$this->context->getUser()
				),
				'comment' => $row->pr_comment,
				'adminComment' => $row->pr_admin_comment,
				'status' => $row->pr_status,
				'isOpen' => $row->pr_open == 1 ? true : false,
				'data' => unserialize( $row->pr_data )
			];
		}

		return \Status::newGood( $requests );
	}

	protected function getRequestById( $id ) {
		return $this->database->selectRow(
			static::TABLE_NAME,
			'*',
			[ 'pr_id' => $id ]
		);
	}

	/**
	 * Makes new request
	 *
	 * @param array $data
	 * @return \Status
	 */
	protected function submitRequest( $data ) {
		$comment = isset( $data['comment'] ) ? $data['comment'] : '';
		unset( $data['comment'] );

		$res = $this->database->insert(
			static::TABLE_NAME,
			[
				'pr_user' => $this->context->getUser()->getId(),
				'pr_module' => $this->getModuleName(),
				'pr_timestamp' => wfTimestamp( TS_MW ),
				'pr_comment' => $comment,
				'pr_data' => serialize( $data )
			]
		);

		if ( $res ) {
			$this->logAction( 'submit', [
				'comment' => $comment
			] );

			$notification = new RequestSubmitted(
				$this->context->getUser(),
				$comment,
				$this->getModuleName()
			);
			$this->notify( $notification );

			return \Status::newGood();
		}

		return \Status::newFatal( 'bs-privacy-request-submit-failed' );
	}

	/**
	 * Cancels existing request
	 *
	 * @return \Status
	 */
	protected function cancelRequest() {
		$res = $this->database->delete(
			static::TABLE_NAME,
			[
				'pr_user' => $this->context->getUser()->getId(),
				'pr_module' => $this->getModuleName(),
				'pr_open' => static::REQUEST_OPEN
			]
		);

		if ( $res ) {
			$this->logRequestAction( 'cancel' );
			return \Status::newGood();
		}

		return \Status::newFatal( 'bs-privacy-request-cancel-failed' );
	}

	protected function closeRequest( $userId = 0 ) {
		$userId = $userId > 0 ? $userId : $this->context->getUser()->getId();
		$user = \User::newFromId( $userId );

		$res = $this->database->update(
			static::TABLE_NAME,
			[ "pr_open" => static::REQUEST_CLOSED ],
			[
				'pr_user' => $userId,
				'pr_module' => $this->getModuleName(),
				"(pr_status =" . static::REQUEST_STATUS_DENIED .
				" OR pr_status = " . static::REQUEST_STATUS_APPROVED . ")"
			]
		);

		if ( $res ) {
			$this->logRequestAction( 'close', [
				'username' => $user->getName()
			] );
			return \Status::newGood();
		}

		return \Status::newFatal( 'bs-privacy-request-close-failed' );
	}

	protected function approveRequest( $requestId ) {
		if ( !$this->checkAdminPermissions() ) {
			return \Status::newFatal( 'bs-privacy-admin-access-denied' );
		}

		$request = $this->getRequestById( $requestId );
		$deletedUsername = unserialize( $request->pr_data )['username'];

		$this->database->update(
			static::TABLE_NAME,
			[
				'pr_status' => static::REQUEST_STATUS_APPROVED,
				'pr_open' => static::REQUEST_CLOSED
			],
			[ 'pr_id' => $requestId ]
		);

		$this->logRequestAction( 'approve', [
			'username' => $deletedUsername
		] );

		return \Status::newGood();
	}

	protected function denyRequest( $requestId, $comment ) {
		if ( !$this->checkAdminPermissions() ) {
			return \Status::newFatal( 'bs-privacy-admin-access-denied' );
		}

		$request = $this->getRequestById( $requestId );
		$subjectUser = \User::newFromId( $request->pr_user );

		$this->database->update(
			static::TABLE_NAME,
			[
				'pr_status' => static::REQUEST_STATUS_DENIED,
				'pr_admin_comment' => $comment
			],
			[ 'pr_id' => $requestId ]
		);

		$this->logRequestAction( 'deny', [
			'username' => $subjectUser->getName(),
			'comment' => $comment
		] );

		$notification = $this->getRequestDeniedNotification( $request, $comment );
		$this->notify( $notification );

		return \Status::newGood();
	}

	protected function logRequestAction( $action, $params = [], $user = null ) {
		if ( $user === null ) {
			$user = $this->context->getUser();
		}

		$entry = new \ManualLogEntry(
			'bs-privacy',
			"request-$action-{$this->getModuleName()}"
		);

		$title = \Title::newMainPage();
		$entry->setTarget( $title );
		$entry->setParameters( $this->buildLogParams( $params ) );
		$entry->setPerformer( $user );
		$entry->insert();
	}

	/**
	 * @param \stdClass $request
	 * @param string $comment
	 * @return INotification
	 */
	abstract public function getRequestDeniedNotification( $request, $comment );

}

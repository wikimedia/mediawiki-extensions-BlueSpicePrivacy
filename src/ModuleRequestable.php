<?php

namespace BlueSpice\Privacy;

use BlueSpice\Privacy\Event\RequestSubmitted;
use ManualLogEntry;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Language\Language;
use MediaWiki\Permissions\GroupPermissionsLookup;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MWStake\MediaWiki\Component\Events\NotificationEvent;
use MWStake\MediaWiki\Component\Events\Notifier;
use Wikimedia\Rdbms\ILoadBalancer;

abstract class ModuleRequestable extends Module {
	public const TABLE_NAME = 'bs_privacy_request';
	public const REQUEST_STATUS_PENDING = 1;
	public const REQUEST_STATUS_DENIED = 2;
	public const REQUEST_STATUS_APPROVED = 3;

	public const REQUEST_OPEN = 1;
	public const REQUEST_CLOSED = 0;

	/**
	 * @var ConfigFactory
	 */
	protected $configFactory;

	/**
	 * @var UserFactory
	 */
	protected $userFactory;

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @param ILoadBalancer $lb
	 * @param Notifier $notifier
	 * @param PermissionManager $permissionManager
	 * @param ConfigFactory $configFactory
	 * @param UserFactory $userFactory
	 * @param Language $language
	 * @param SpecialPageFactory $specialPageFactory
	 * @param GroupPermissionsLookup $groupPermissionLookup
	 */
	public function __construct(
		ILoadBalancer $lb, Notifier $notifier, PermissionManager $permissionManager,
		ConfigFactory $configFactory, UserFactory $userFactory, Language $language,
		private readonly SpecialPageFactory $specialPageFactory,
		private readonly GroupPermissionsLookup $groupPermissionLookup
	) {
		parent::__construct( $lb, $notifier, $permissionManager );
		$this->configFactory = $configFactory;
		$this->userFactory = $userFactory;
		$this->language = $language;
	}

	/**
	 *
	 * @param string $func
	 * @param array $data
	 * @return Status
	 */
	public function call( $func, $data ) {
		switch ( $func ) {
			case "checkStatus":
				return $this->checkStatus();
			case "getRequests":
				return $this->getRequests();
			case "submitRequest":
				return $this->submitRequest( $data );
			case "cancelRequest":
				return $this->cancelRequest();
			case "closeRequest":
				return $this->closeRequest();
			case "approveRequest":
				if ( !isset( $data['requestId'] ) ) {
					return Status::newFatal(
						wfMessage( 'bs-privacy-missing-param', "requestId" )
					);
				}
				return $this->approveRequest( $data['requestId'] );
			case "denyRequest":
				if ( !isset( $data['requestId'] ) ) {
					return Status::newFatal(
						wfMessage( 'bs-privacy-missing-param', "requestId" )
					);
				}
				$comment = isset( $data['comment'] ) ? $data['comment'] : '';
				return $this->denyRequest( $data['requestId'], $comment );
			default:
				return Status::newFatal(
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
		return $this->configFactory->makeConfig( 'bsg' )->get( 'PrivacyEnableRequests' );
	}

	/**
	 * Gets status of the request
	 *
	 * @return Status
	 */
	protected function checkStatus() {
		$row = $this->lb->getConnection( DB_REPLICA )->selectRow(
			static::TABLE_NAME,
			'*',
			[
				'pr_user' => $this->user->getId(),
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

		return Status::newGood( $statusData );
	}

	/**
	 * Gets all requests
	 *
	 * @return Status
	 */
	protected function getRequests() {
		if ( !$this->checkAdminPermissions() ) {
			return Status::newFatal( 'bs-privacy-admin-access-denied' );
		}

		$res = $this->lb->getConnection( DB_REPLICA )->select(
			static::TABLE_NAME,
			'*',
			[ 'pr_module' => $this->getModuleName() ],
			__METHOD__
		);

		if ( !$res ) {
			return Status::newFatal( 'bs-privacy-admin-get-requests-failed' );
		}

		$requests = [];
		foreach ( $res as $row ) {
			$user = $this->userFactory->newFromId( $row->pr_user );

			// Get how many days ago request was made
			$ts = wfTimestamp( TS_UNIX, $row->pr_timestamp );
			$today = wfTimestamp( TS_UNIX );
			$diff = $today - $ts;
			$daysAgo = floor( $diff / ( 24 * 60 * 60 ) );

			if ( !$daysAgo ) {
				$tsWithDaysAgo = wfMessage(
					'bs-privacy-timestamp-with-days-ago-today',
					$this->language->userTimeAndDate(
						$row->pr_timestamp,
						$this->user
					)
				)->parse();
			} else {
				$tsWithDaysAgo = wfMessage(
					'bs-privacy-timestamp-with-days-ago',
					$this->language->userTimeAndDate(
						$row->pr_timestamp,
						$this->user
					),
					$daysAgo
				)->parse();
			}

			$requests[] = [
				'requestId' => $row->pr_id,
				'userId' => $user->getId(),
				'userName' => $user->getName(),
				'userPageUrl' => $user->getUserPage()->getLocalURL(),
				'module' => $row->pr_module,
				'rawTimestamp' => $row->pr_timestamp,
				'daysAgo' => $daysAgo,
				'timestampWithDaysAgo' => $tsWithDaysAgo,
				'timestamp' => $this->language->userTimeAndDate(
					$row->pr_timestamp,
					$this->user
				),
				'comment' => $row->pr_comment,
				'adminComment' => $row->pr_admin_comment,
				'status' => $row->pr_status,
				'isOpen' => $row->pr_open == 1 ? true : false,
				'data' => unserialize( $row->pr_data )
			];
		}

		return Status::newGood( $requests );
	}

	/**
	 *
	 * @param int $id
	 * @return \stdClass|false
	 */
	protected function getRequestById( $id ) {
		return $this->lb->getConnection( DB_REPLICA )->selectRow(
			static::TABLE_NAME,
			'*',
			[ 'pr_id' => $id ]
		);
	}

	/**
	 * Makes new request
	 *
	 * @param array $data
	 * @return Status
	 * @throws \Exception
	 */
	protected function submitRequest( $data ) {
		$comment = isset( $data['comment'] ) ? $data['comment'] : '';
		unset( $data['comment'] );

		$res = $this->lb->getConnection( DB_PRIMARY )->insert(
			static::TABLE_NAME,
			[
				'pr_user' => $this->user->getId(),
				'pr_module' => $this->getModuleName(),
				'pr_timestamp' => wfTimestamp( TS_MW ),
				'pr_comment' => $comment,
				'pr_data' => serialize( $data )
			],
			__METHOD__
		);

		if ( $res ) {
			$this->logRequestAction( 'submit', [
				'comment' => $comment
			] );

			$event = new RequestSubmitted(
				$this->specialPageFactory->getPage( 'PrivacyAdmin' )?->getPageTitle(),
				$this->getAdminsToNotify(),
				$this->user,
				$comment,
				$this->getModuleName()
			);

			$this->notify( $event );

			return Status::newGood();
		}

		return Status::newFatal( 'bs-privacy-request-submit-failed' );
	}

	/**
	 * @return User[]
	 */
	private function getAdminsToNotify(): array {
		$groups = $this->groupPermissionLookup->getGroupsWithPermission( 'bs-privacy-admin' );
		$db = $this->lb->getConnection( DB_REPLICA );
		$res = $db->select(
			[ 'u' => 'user', 'ug' => 'user_groups' ],
			[ 'u.user_name' ],
			[ 'ug_group IN (' . $db->makeList( $groups ) . ')', 'u.user_id = ug.ug_user' ],
			__METHOD__
		);

		$users = [];
		foreach ( $res as $row ) {
			$users[] = $this->userFactory->newFromName( $row->user_name );
		}
		// Filter out nulls
		return array_filter( $users );
	}

	/**
	 * Cancels existing request
	 *
	 * @return Status
	 */
	protected function cancelRequest() {
		$res = $this->lb->getConnection( DB_PRIMARY )->delete(
			static::TABLE_NAME,
			[
				'pr_user' => $this->user->getId(),
				'pr_module' => $this->getModuleName(),
				'pr_open' => static::REQUEST_OPEN
			],
			__METHOD__
		);

		if ( $res ) {
			$this->logRequestAction( 'cancel' );
			return Status::newGood();
		}

		return Status::newFatal( 'bs-privacy-request-cancel-failed' );
	}

	/**
	 *
	 * @param int $userId
	 * @return Status
	 */
	protected function closeRequest( $userId = 0 ) {
		$userId = $userId > 0 ? $userId : $this->user->getId();
		$user = $this->userFactory->newFromId( $userId );

		$res = $this->lb->getConnection( DB_PRIMARY )->update(
			static::TABLE_NAME,
			[ "pr_open" => static::REQUEST_CLOSED ],
			[
				'pr_user' => $userId,
				'pr_module' => $this->getModuleName(),
				"(pr_status =" . static::REQUEST_STATUS_DENIED .
				" OR pr_status = " . static::REQUEST_STATUS_APPROVED . ")"
			],
			__METHOD__
		);

		if ( $res ) {
			$this->logRequestAction( 'close', [
				'username' => $user->getName()
			] );
			return Status::newGood();
		}

		return Status::newFatal( 'bs-privacy-request-close-failed' );
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
		$deletedUsername = unserialize( $request->pr_data )['username'];

		$this->lb->getConnection( DB_PRIMARY )->update(
			static::TABLE_NAME,
			[
				'pr_status' => static::REQUEST_STATUS_APPROVED,
				'pr_open' => static::REQUEST_CLOSED
			],
			[ 'pr_id' => $requestId ],
			__METHOD__
		);

		$this->logRequestAction( 'approve', [
			'username' => $deletedUsername
		] );

		return Status::newGood();
	}

	/**
	 *
	 * @param int $requestId
	 * @param string $comment
	 * @return Status
	 */
	protected function denyRequest( $requestId, $comment ) {
		if ( !$this->checkAdminPermissions() ) {
			return Status::newFatal( 'bs-privacy-admin-access-denied' );
		}

		$request = $this->getRequestById( $requestId );
		$subjectUser = $this->userFactory->newFromId( $request->pr_user );

		$this->lb->getConnection( DB_PRIMARY )->update(
			static::TABLE_NAME,
			[
				'pr_status' => static::REQUEST_STATUS_DENIED,
				'pr_admin_comment' => $comment
			],
			[ 'pr_id' => $requestId ],
			__METHOD__
		);

		$this->logRequestAction( 'deny', [
			'username' => $subjectUser->getName(),
			'comment' => $comment
		] );

		$notification = $this->getRequestDeniedNotification( $request, $comment );
		$this->notify( $notification );

		return Status::newGood();
	}

	/**
	 *
	 * @param string $action
	 * @param array $params
	 * @param User|null $user
	 */
	protected function logRequestAction( $action, $params = [], $user = null ) {
		$user = $user ?? $this->user;

		$entry = new ManualLogEntry(
			'bs-privacy',
			"request-$action-{$this->getModuleName()}"
		);

		$title = Title::newMainPage();
		$entry->setTarget( $title );
		$entry->setParameters( $this->buildLogParams( $params ) );
		$entry->setPerformer( $user );
		$entry->insert();
	}

	/**
	 * @param \stdClass $request
	 * @param string $comment
	 * @return NotificationEvent
	 */
	abstract public function getRequestDeniedNotification( $request, $comment );

}

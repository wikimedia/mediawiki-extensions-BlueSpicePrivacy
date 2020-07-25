<?php
namespace BlueSpice\Privacy\Notifications;

use BlueSpice\BaseNotification;
use BlueSpice\Privacy\IModule;
use BlueSpice\Services;
use MediaWiki\MediaWikiServices;

class RequestSubmitted extends BaseNotification {
	/**
	 *
	 * @var string
	 */
	protected $comment;
	/**
	 *
	 * @var IModule
	 */
	protected $module;
	/**
	 *
	 * @var \Wikimedia\Rdbms\Database
	 */
	protected $db;

	/**
	 *
	 * @param \User $agent
	 * @param string $comment
	 * @param IModule $module
	 */
	public function __construct( $agent, $comment, $module ) {
		parent::__construct( 'bs-privacy-request-submitted', $agent, \Title::newMainPage() );

		$this->db = Services::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$this->addAffectedUsers( $this->getPrivacyAdmins() );
		$this->comment = $comment;
		$this->module = $module;
	}

	/**
	 *
	 * @return array
	 */
	public function getParams() {
		return [
			'comment' => $this->comment,
			'module' => $this->module
		];
	}

	/**
	 *
	 * @return bool
	 */
	public function useJobQueue() {
		return true;
	}

	/**
	 * Decently slow. Always do this in a job
	 * @return array
	 */
	protected function getPrivacyAdmins() {
		$res = $this->db->select(
			'user',
			'*',
			'',
			__METHOD__
		);

		$users = [];
		$pm = MediaWikiServices::getInstance()->getPermissionManager();
		foreach ( $res as $row ) {
			$user = \User::newFromRow( $row );
			if ( !$user instanceof \User ) {
				continue;
			}
			if ( !$pm->userHasRight( $user, 'bs-privacy-admin' ) ) {
				continue;
			}
			$users[] = $user;
		}

		return $users;
	}
}

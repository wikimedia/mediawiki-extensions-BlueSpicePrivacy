<?php

namespace BlueSpice\Privacy\Notifications;

use BlueSpice\BaseNotification;
use MediaWiki\MediaWikiServices;

class RequestDeletionApproved extends BaseNotification {
	/** @var bool */
	protected $notifyAgent = false;

	/**
	 *
	 * @param \User $agent
	 * @param \Title $title
	 * @param string $username
	 */
	public function __construct( $agent, $title, $username ) {
		parent::__construct( 'bs-privacy-request-deletion-approved', $agent, $title );

		$user = MediaWikiServices::getInstance()->getUserFactory()->newFromName( $username );
		$this->addAffectedUsers( [ $user->getId() ] );

		// If user executed request himself, notify him
		if ( $agent->getId() === $user->getId() ) {
			$this->notifyAgent = true;
		}
	}

	/**
	 *
	 * @return array
	 */
	public function getParams() {
		return [
			'notifyAgent' => $this->notifyAgent
		];
	}

	public function useJobQueue() {
		return false;
	}
}

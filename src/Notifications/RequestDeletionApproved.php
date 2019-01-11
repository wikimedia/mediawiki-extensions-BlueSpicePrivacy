<?php

namespace BlueSpice\Privacy\Notifications;

use BlueSpice\BaseNotification;

class RequestDeletionApproved extends BaseNotification {
	protected $notifyAgent = false;

	/**
	 *
	 * @param \User $agent
	 * @param \Title $title
	 * @param string $username
	 */
	public function __construct( $agent, $title, $username ) {
		parent::__construct( 'bs-privacy-request-deletion-approved', $agent, $title );

		$user = \User::newFromName( $username );
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

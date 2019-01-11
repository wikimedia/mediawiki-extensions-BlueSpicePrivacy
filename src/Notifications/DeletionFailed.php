<?php

namespace BlueSpice\Privacy\Notifications;

use BlueSpice\BaseNotification;

class DeletionFailed extends BaseNotification {
	protected $user;
	protected $comment;
	protected $notifyAgent = false;

	/**
	 *
	 * @param \User $agent
	 * @param \Title $title
	 * @param \User $userToDelete
	 * @param string $comment
	 */
	public function __construct( $agent, $title, $userToDelete, $comment ) {
		parent::__construct( 'bs-privacy-deletion-failed', $agent, $title );

		$user = \User::newFromName( $userToDelete );
		$this->addAffectedUsers( [ $user->getId() ] );

		$this->comment = $comment;

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

	/**
	 *
	 * @return bool
	 */
	public function useJobQueue() {
		return false;
	}
}

<?php

namespace BlueSpice\Privacy\Notifications;

use BlueSpice\BaseNotification;
use MediaWiki\MediaWikiServices;

class RequestAnonymizationDenied extends BaseNotification {
	/** @var \User */
	protected $oldUsername;
	/** @var \User */
	protected $newUsername;
	/** @var string */
	protected $comment;
	/** @var bool */
	protected $notifyAgent = false;

	/**
	 *
	 * @param \User $agent
	 * @param \Title $title
	 * @param \User $oldUsername
	 * @param \User $newUsername
	 * @param string $comment
	 */
	public function __construct( $agent, $title, $oldUsername, $newUsername, $comment ) {
		parent::__construct( 'bs-privacy-request-anonymization-denied', $agent, $title );

		$user = MediaWikiServices::getInstance()->getUserFactory()->newFromName( $oldUsername );
		$this->addAffectedUsers( [ $user->getId() ] );

		$this->oldUsername = $oldUsername;
		$this->newUsername = $newUsername;
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
			'oldUsername' => $this->oldUsername,
			'newUsername' => $this->newUsername,
			'comment' => $this->comment,
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

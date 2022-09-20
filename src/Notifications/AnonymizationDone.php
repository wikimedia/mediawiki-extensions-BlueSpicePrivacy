<?php

namespace BlueSpice\Privacy\Notifications;

use BlueSpice\BaseNotification;
use MediaWiki\MediaWikiServices;

class AnonymizationDone extends BaseNotification {
	/** @var \User */
	protected $oldUsername;
	/** @var \User */
	protected $newUsername;
	/** @var bool */
	protected $notifyAgent = false;

	/**
	 *
	 * @param \User $agent
	 * @param \Title $title
	 * @param \User $oldUsername
	 * @param \User $newUsername
	 */
	public function __construct( $agent, $title, $oldUsername, $newUsername ) {
		parent::__construct( 'bs-privacy-anonymization-done', $agent, $title );

		$user = MediaWikiServices::getInstance()->getUserFactory()->newFromName( $newUsername );
		$this->addAffectedUsers( [ $user->getId() ] );

		$this->oldUsername = $oldUsername;
		$this->newUsername = $newUsername;

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

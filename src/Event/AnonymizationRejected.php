<?php

namespace BlueSpice\Privacy\Event;

use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserIdentity;
use Message;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\NotificationEvent;

class AnonymizationRejected extends NotificationEvent {

	/** @var string */
	protected $user;
	/** @var string */
	protected $newUsername;
	/** @var string */
	protected $comment;

	/**
	 * @param UserIdentity $agent
	 * @param UserIdentity $user
	 * @param string $newUsername
	 * @param string $comment
	 */
	public function __construct( UserIdentity $agent, UserIdentity $user, string $newUsername, string $comment ) {
		parent::__construct( $agent );
		$this->user = $user;
		$this->newUsername = $newUsername;
		$this->comment = $comment;
	}

	/**
	 * @return Message
	 */
	public function getKeyMessage(): Message {
		return Message::newFromKey( 'bs-privacy-event-anonymization-rejected-desc' );
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage( IChannel $forChannel ): Message {
		return Message::newFromKey( 'bs-privacy-event-anonymization-rejected' )->params(
			$this->getAgent()->getName(),
			$this->newUsername,
			$this->comment
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getLinksIntroMessage( IChannel $forChannel ): ?Message {
		return null;
	}

	/**
	 * @return string
	 */
	public function getKey(): string {
		return 'bs-privacy-anonymization-rejected';
	}

	/**
	 * @return UserIdentity[]|string[]|null
	 */
	public function getPresetSubscribers(): ?array {
		return [ $this->user ];
	}

	/**
	 * @param UserIdentity $agent
	 * @param MediaWikiServices $services
	 * @param array $extra
	 * @return array
	 */
	public static function getArgsForTesting(
		UserIdentity $agent, MediaWikiServices $services, array $extra = []
	): array {
		return [
			$agent,
			$extra['targetUser'] ?? $services->getUserFactory()->newFromName( 'WikiSysop' ),
			'NEW USERNAME',
			'Username is invalid'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getLinks( IChannel $forChannel ): array {
		return [];
	}
}

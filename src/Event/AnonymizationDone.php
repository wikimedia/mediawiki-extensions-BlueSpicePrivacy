<?php

namespace BlueSpice\Privacy\Event;

use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\NotificationEvent;
use MWStake\MediaWiki\Component\Events\PriorityEvent;

class AnonymizationDone extends NotificationEvent implements PriorityEvent {

	/** @var string */
	protected $oldUsername;
	/** @var UserIdentity */
	protected $newUser;

	/**
	 * @param UserIdentity $agent
	 * @param string $oldUsername
	 * @param UserIdentity $newUser
	 */
	public function __construct( UserIdentity $agent, string $oldUsername, UserIdentity $newUser ) {
		parent::__construct( $agent );
		$this->oldUsername = $oldUsername;
		$this->newUser = $newUser;
	}

	/**
	 * @return Message
	 */
	public function getKeyMessage(): Message {
		return Message::newFromKey( 'bs-privacy-event-anonymization-done-desc' );
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage( IChannel $forChannel ): Message {
		return Message::newFromKey( 'bs-privacy-event-anonymization-done' )->params(
			$this->getAgent()->getName(),
			$this->newUser->getName()
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
		return 'bs-privacy-anonymization-done';
	}

	/**
	 * @return UserIdentity[]|null
	 */
	public function getPresetSubscribers(): ?array {
		return [ $this->newUser ];
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
			'OLD USERNAME',
			$extra['targetUser'] ?? $services->getUserFactory()->newFromName( 'WikiSysop' ),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getLinks( IChannel $forChannel ): array {
		return [];
	}
}

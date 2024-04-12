<?php

namespace BlueSpice\Privacy\Event;

use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserIdentity;
use Message;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\NotificationEvent;

class DeletionDone extends NotificationEvent {

	/** @var UserIdentity */
	protected $user;

	/**
	 * @param UserIdentity $agent
	 * @param UserIdentity $user
	 */
	public function __construct( UserIdentity $agent, UserIdentity $user ) {
		parent::__construct( $agent );
		$this->user = $user;
	}

	/**
	 * @return Message
	 */
	public function getKeyMessage(): Message {
		return Message::newFromKey( 'bs-privacy-event-deletion-done-desc' );
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage( IChannel $forChannel ): Message {
		return Message::newFromKey( 'bs-privacy-event-deletion-done' )->params( $this->getAgent()->getName() );
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
		return 'bs-privacy-deletion-done';
	}

	/**
	 * @return UserIdentity[]|null
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
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getLinks( IChannel $forChannel ): array {
		return [];
	}
}

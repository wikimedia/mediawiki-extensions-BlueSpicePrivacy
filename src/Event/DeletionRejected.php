<?php

namespace BlueSpice\Privacy\Event;

use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserIdentity;
use Message;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\NotificationEvent;

class DeletionRejected extends NotificationEvent {

	/** @var UserIdentity */
	protected $user;

	/**
	 * @param UserIdentity $agent
	 * @param UserIdentity $user
	 * @param string $comment
	 */
	public function __construct( UserIdentity $agent, UserIdentity $user, string $comment ) {
		parent::__construct( $agent );
		$this->user = $user;
		$this->comment = $comment;
	}

	/**
	 * @return Message
	 */
	public function getKeyMessage(): Message {
		return Message::newFromKey( 'bs-privacy-event-deletion-rejected-desc' );
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage( IChannel $forChannel ): Message {
		return Message::newFromKey( 'bs-privacy-event-deletion-rejected' )->params(
			$this->getAgent()->getName(),
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
		return 'bs-privacy-deletion-rejected';
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
			'This account is not suitable for deletion'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getLinks( IChannel $forChannel ): array {
		return [];
	}
}

<?php

namespace BlueSpice\Privacy\Event;

use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\EventLink;
use MWStake\MediaWiki\Component\Events\NotificationEvent;
use MWStake\MediaWiki\Component\Events\PriorityEvent;

class RequestSubmitted extends NotificationEvent implements PriorityEvent {
	/** @var Title|null */
	private ?Title $adminPage;
	/** @var UserIdentity[] */
	private array $admins;
	/** @var string */
	private $comment;
	/** @var string */
	private $module;

	/**
	 * @param Title|null $adminPage
	 * @param array $admins
	 * @param UserIdentity $agent
	 * @param string $comment
	 * @param string $module
	 */
	public function __construct(
		?Title $adminPage, array $admins, UserIdentity $agent, string $comment, string $module
	) {
		parent::__construct( $agent );

		$this->adminPage = $adminPage;
		$this->admins = $admins;
		$this->comment = $comment;
		$this->module = $module;
	}

	/**
	 * @return Message
	 */
	public function getKeyMessage(): Message {
		return Message::newFromKey( "bs-privacy-event-request-submitted-$this->module-desc" )->params(
			$this->agent->getName()
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage( IChannel $forChannel ): Message {
		return Message::newFromKey( "bs-privacy-event-request-submitted-$this->module" )->params(
			$this->agent->getName(),
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
		return 'bs-privacy-request-submitted';
	}

	/**
	 * @return UserIdentity[]|null
	 */
	public function getPresetSubscribers(): ?array {
		return $this->admins;
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
			$services->getSpecialPageFactory()->getPage( 'PrivacyAdmin' )->getPageTitle(),
			[ $extra['targetUser'] ?? $services->getUserFactory()->newFromName( 'WikiSysop' ) ],
			$agent,
			'Please accept my request',
			'anonymization'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getLinks( IChannel $forChannel ): array {
		if ( !$this->adminPage ) {
			return [];
		}
		return [
			new EventLink(
				$this->adminPage->getFullURL(),
				Message::newFromKey( 'bs-privacy-event-request-submitted-link' )
			)
		];
	}
}

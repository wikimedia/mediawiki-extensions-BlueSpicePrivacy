<?php

namespace BlueSpice\Privacy\Event;

use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\GroupPermissionsLookup;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use Message;
use MWStake\MediaWiki\Component\Events\Delivery\IChannel;
use MWStake\MediaWiki\Component\Events\EventLink;
use MWStake\MediaWiki\Component\Events\NotificationEvent;
use MWStake\MediaWiki\Component\Events\PriorityEvent;
use Wikimedia\Rdbms\ILoadBalancer;

class RequestSubmitted extends NotificationEvent implements PriorityEvent {
	/** @var SpecialPageFactory */
	private $specialPageFactory;
	/** @var GroupPermissionsLookup */
	private $groupPermissionsLookup;
	/** @var UserFactory */
	private $userFactory;
	/** @var ILoadBalancer */
	private $loadBalancer;
	/** @var string */
	private $comment;
	/** @var string */
	private $module;

	/**
	 * @param SpecialPageFactory $spf
	 * @param GroupPermissionsLookup $gpl
	 * @param ILoadBalancer $loadBalancer
	 * @param UserFactory $userFactory
	 * @param UserIdentity $agent
	 * @param string $comment
	 * @param string $module
	 */
	public function __construct(
		SpecialPageFactory $spf, GroupPermissionsLookup $gpl, ILoadBalancer $loadBalancer, UserFactory $userFactory,
		UserIdentity $agent, string $comment, string $module
	) {
		parent::__construct( $agent );

		$this->specialPageFactory = $spf;
		$this->groupPermissionsLookup = $gpl;
		$this->loadBalancer = $loadBalancer;
		$this->userFactory = $userFactory;
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
		$groups = $this->groupPermissionsLookup->getGroupsWithPermission( 'bs-privacy-admin' );
		$db = $this->loadBalancer->getConnection( DB_REPLICA );
		$res = $db->select(
			[ 'u' => 'user', 'ug' => 'user_groups' ],
			[ 'u.user_name' ],
			[ 'ug_group IN (' . $db->makeList( $groups ) . ')', 'u.user_id = ug.ug_user' ],
			__METHOD__
		);

		$users = [];
		foreach ( $res as $row ) {
			$users[] = $this->userFactory->newFromName( $row->user_name );
		}
		// Filter out nulls
		return array_filter( $users );
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
			'Please accept my request',
			'anonymization'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getLinks( IChannel $forChannel ): array {
		$privacyAdminPage = $this->specialPageFactory->getPage( 'PrivacyAdmin' );
		if ( !$privacyAdminPage ) {
			return [];
		}
		return [
			new EventLink(
				$privacyAdminPage->getPageTitle()->getFullURL(),
				Message::newFromKey( 'bs-privacy-event-request-submitted-link' )
			)
		];
	}
}

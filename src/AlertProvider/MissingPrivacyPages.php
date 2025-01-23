<?php

namespace BlueSpice\Privacy\AlertProvider;

use MediaWiki\Config\Config;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserGroupManager;
use MWStake\MediaWiki\Component\AlertBanners\AlertProviderBase;
use MWStake\MediaWiki\Component\AlertBanners\IAlertProvider;
use Skin;
use Wikimedia\Rdbms\LoadBalancer;

class MissingPrivacyPages extends AlertProviderBase {

	/**
	 *
	 * @var Message
	 */
	protected $alertMessage = null;

	/** @var UserGroupManager */
	private $groupManager = null;

	/** @var TitleFactory */
	private $titleFactory = null;

	/** @var LinkRenderer */
	private $linkRenderer = null;

	/** @var bool */
	private $isInitialized = false;

	/**
	 * @param Skin $skin
	 * @param LoadBalancer $loadBalancer
	 * @param Config $config
	 * @param UserGroupManager $groupManager
	 * @param TitleFactory $titleFactory
	 * @param LinkRenderer $linkRenderer
	 */
	public function __construct( $skin, LoadBalancer $loadBalancer, Config $config,
	$groupManager, $titleFactory, $linkRenderer ) {
		parent::__construct( $skin, $loadBalancer, $config );
		$this->groupManager = $groupManager;
		$this->titleFactory = $titleFactory;
		$this->linkRenderer = $linkRenderer;
	}

	/**
	 *
	 * @return string
	 */
	public function getHTML() {
		$this->initFromContext();
		if ( $this->alertMessage instanceof Message ) {
			return $this->alertMessage->parse();
		}
		return '';
	}

	/**
	 *
	 * @return string
	 */
	public function getType() {
		return IAlertProvider::TYPE_WARNING;
	}

	/**
	 *
	 * @inheritDoc
	 */
	public static function factory( $skin = null ) {
		$services = MediaWikiServices::getInstance();
		$loadBalancer = $services->getDBLoadBalancer();
		$config = $services->getConfigFactory()->makeConfig( 'bsg' );
		$groupManager = $services->getUserGroupManager();
		$titleFactory = $services->getTitleFactory();
		$linkRenderer = $services->getLinkRenderer();

		return new static(
			$skin,
			$loadBalancer,
			$config,
			$groupManager,
			$titleFactory,
			$linkRenderer
		);
	}

	private function initFromContext() {
		if ( $this->isInitialized ) {
			return;
		}

		$user = $this->skin->getUser();
		$groupMemberships = $this->groupManager->getUserGroupMemberships( $user );
		if ( !array_key_exists( 'sysop', $groupMemberships ) ) {
			$this->isInitialized = true;
			return;
		}

		$tosPage = $this->skin->msg( 'bs-privacy-termsofservicepage' )->inContentLanguage()->text();
		$ppPage = $this->skin->msg( 'bs-privacy-privacypage' )->inContentLanguage()->text();

		$tosTitle = $this->titleFactory->newFromText( $tosPage );
		$ppTitle = $this->titleFactory->newFromText( $ppPage );

		$titles = [];
		if ( empty( $this->getConfig()->get( "PrivacyTermsOfServiceLink" ) ) ) {
			$titles[] = $tosTitle;
		}
		if ( empty( $this->getConfig()->get( "PrivacyPrivacyPolicyLink" ) ) ) {
			$titles[] = $ppTitle;
		}
		if ( empty( $titles ) ) {
			$this->isInitialized = true;
			return;
		}
		$redLinks = [];
		foreach ( $titles as $title ) {
			if ( !$title || $title->exists() ) {
				continue;
			}
			$redLinks[] = $this->linkRenderer->makeLink( $title );
		}
		if ( empty( $redLinks ) ) {
			$this->isInitialized = true;
			return;
		}
		$redLinkList = $this->skin->getLanguage()->listToText( $redLinks );
		$redlinkCount = count( $redLinks );

		$this->alertMessage = $this->skin->msg( 'bs-privacy-alert-page-not-exist' )
			->rawParams( $redlinkCount, $redLinkList );

		$this->isInitialized = true;
	}
}

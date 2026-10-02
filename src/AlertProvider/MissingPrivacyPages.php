<?php

namespace BlueSpice\Privacy\AlertProvider;

use BlueSpice\Privacy\Util\PrivacyPageProvider;
use MediaWiki\Config\Config;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Skin\Skin;
use MWStake\MediaWiki\Component\AlertBanners\AlertProviderBase;
use MWStake\MediaWiki\Component\AlertBanners\IAlertProvider;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LoadBalancer;

class MissingPrivacyPages extends AlertProviderBase {

	/**
	 * @var Message
	 */
	protected $alertMessage = null;

	/** @var bool */
	private $isInitialized = false;

	/**
	 * @param Skin $skin
	 * @param LoadBalancer $loadBalancer
	 * @param Config $config
	 * @param PrivacyPageProvider $pageProvider
	 * @param PermissionManager $permissionManager
	 * @param HookContainer $hookContainer
	 */
	public function __construct(
		$skin,
		ILoadBalancer $loadBalancer,
		Config $config,
		private readonly PrivacyPageProvider $pageProvider,
		private readonly PermissionManager $permissionManager,
		private readonly HookContainer $hookContainer
	) {
		parent::__construct( $skin, $loadBalancer, $config );
	}

	/**
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
	 * @return string
	 */
	public function getType() {
		return IAlertProvider::TYPE_WARNING;
	}

	/**
	 * @inheritDoc
	 */
	public static function factory( $skin = null ) {
		$services = MediaWikiServices::getInstance();
		$loadBalancer = $services->getDBLoadBalancer();
		$config = $services->getConfigFactory()->makeConfig( 'bsg' );

		return new static(
			$skin,
			$loadBalancer,
			$config,
			$services->getService( 'BlueSpicePrivacy.PrivacyPageProvider' ),
			$services->getPermissionManager(),
			$services->getHookContainer()
		);
	}

	private function initFromContext() {
		if ( $this->isInitialized ) {
			return;
		}

		$shouldRender = true;
		$this->hookContainer->run( 'BlueSpicePrivacyShowShowMissingPageAlert', [ $this->skin, &$shouldRender ] );

		if ( !$shouldRender ) {
			return;
		}
		$user = $this->skin->getUser();
		$pages = [];
		foreach ( $this->pageProvider->getNames() as $name ) {
			$title = $this->pageProvider->getPrivacyTitle( $name );
			if ( !$title || $title->exists() || $title->isSpecialPage() ) {
				continue;
			}
			if ( !$this->permissionManager->quickUserCan( 'createpage', $user, $title ) ) {
				continue;
			}
			$pages[] = $this->pageProvider->getLinkHtml( $name );
		}
		$this->isInitialized = true;

		if ( empty( $pages ) ) {
			return;
		}
		$this->alertMessage = $this->skin->msg( 'bs-privacy-alert-page-not-exist' )
			->rawParams(
				count( $pages ),
				$this->skin->getLanguage()->listToText( $pages )
			);
	}
}

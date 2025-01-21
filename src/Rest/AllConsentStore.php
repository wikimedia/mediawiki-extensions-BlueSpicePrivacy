<?php

namespace BlueSpice\Privacy\Rest;

use BlueSpice\Privacy\Data\Consents\Store;
use BlueSpice\Privacy\ModuleRegistry;
use MediaWiki\Config\GlobalVarConfig;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserOptionsLookup;
use MWStake\MediaWiki\Component\CommonWebAPIs\Rest\UserQueryStore;
use MWStake\MediaWiki\Component\DataStore\IStore;
use Wikimedia\Rdbms\ILoadBalancer;

class AllConsentStore extends UserQueryStore {
	/** @var Store */
	private $consentStore;

	/**
	 * @param HookContainer $hookContainer
	 * @param ILoadBalancer $lb
	 * @param UserFactory $userFactory
	 * @param LinkRenderer $linkRenderer
	 * @param TitleFactory $titleFactory
	 * @param GlobalVarConfig $mwsgConfig
	 * @param ModuleRegistry $moduleRegistry
	 * @param UserOptionsLookup $userOptionsLookup
	 */
	public function __construct(
		HookContainer $hookContainer, ILoadBalancer $lb, UserFactory $userFactory,
		LinkRenderer $linkRenderer, TitleFactory $titleFactory,
		GlobalVarConfig $mwsgConfig, ModuleRegistry $moduleRegistry, UserOptionsLookup $userOptionsLookup
	) {
		parent::__construct( $hookContainer, $lb, $userFactory, $linkRenderer, $titleFactory, $mwsgConfig );
		$this->consentStore = new Store(
			$lb, $userFactory, $linkRenderer, $titleFactory, $mwsgConfig, $userOptionsLookup, $moduleRegistry
		);
	}

	/**
	 * @return IStore
	 */
	protected function getStore(): IStore {
		return $this->consentStore;
	}
}

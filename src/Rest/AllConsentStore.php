<?php

namespace BlueSpice\Privacy\Rest;

use BlueSpice\Privacy\Data\Consents\Store;
use BlueSpice\Privacy\ModuleRegistry;
use MediaWiki\Config\GlobalVarConfig;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\UserFactory;
use MWStake\MediaWiki\Component\CommonWebAPIs\Rest\UserQueryStore;
use MWStake\MediaWiki\Component\DataStore\IStore;
use MWStake\MediaWiki\Component\Utils\UtilityFactory;
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
	 * @param UtilityFactory $utilityFactory
	 */
	public function __construct(
		HookContainer $hookContainer, ILoadBalancer $lb, UserFactory $userFactory,
		LinkRenderer $linkRenderer, TitleFactory $titleFactory, GlobalVarConfig $mwsgConfig,
		ModuleRegistry $moduleRegistry, UserOptionsLookup $userOptionsLookup, UtilityFactory $utilityFactory
	) {
		parent::__construct(
			$hookContainer, $lb, $userFactory, $linkRenderer, $titleFactory, $mwsgConfig, $utilityFactory
		);
		$this->consentStore = new Store(
			$lb, $userFactory, $linkRenderer, $titleFactory, $mwsgConfig, $utilityFactory,
			$userOptionsLookup, $moduleRegistry
		);
	}

	/**
	 * @return IStore
	 */
	protected function getStore(): IStore {
		return $this->consentStore;
	}
}

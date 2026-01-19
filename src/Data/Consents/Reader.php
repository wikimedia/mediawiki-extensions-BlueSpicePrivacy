<?php

namespace BlueSpice\Privacy\Data\Consents;

use BlueSpice\Privacy\ModuleRegistry;
use MediaWiki\Config\GlobalVarConfig;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\UserFactory;
use MWStake\MediaWiki\Component\Utils\UtilityFactory;
use Wikimedia\Rdbms\ILoadBalancer;

class Reader extends \MWStake\MediaWiki\Component\CommonWebAPIs\Data\UserQueryStore\Reader {

	/**
	 * @var ModuleRegistry
	 */
	protected $moduleRegistry;

	/**
	 * @var UserOptionsLookup
	 */
	protected $userOptionsLookup;

	/**
	 * @param ILoadBalancer $lb
	 * @param UserFactory $userFactory
	 * @param LinkRenderer $linkRenderer
	 * @param TitleFactory $titleFactory
	 * @param GlobalVarConfig $mwsgConfig
	 * @param UtilityFactory $utilityFactory
	 * @param UserOptionsLookup $userOptionsLookup
	 * @param ModuleRegistry $moduleRegistry
	 */
	public function __construct(
		ILoadBalancer $lb, UserFactory $userFactory, LinkRenderer $linkRenderer, TitleFactory $titleFactory,
		GlobalVarConfig $mwsgConfig, UtilityFactory $utilityFactory,
		UserOptionsLookup $userOptionsLookup, ModuleRegistry $moduleRegistry
	) {
		parent::__construct( $lb, $userFactory, $linkRenderer, $titleFactory, $mwsgConfig, $utilityFactory );
		$this->moduleRegistry = $moduleRegistry;
		$this->userOptionsLookup = $userOptionsLookup;
	}

	public function makeSecondaryDataProvider() {
		return new SecondaryDataProvider(
			$this->userFactory, $this->linkRenderer,
			$this->titleFactory, $this->userOptionsLookup, $this->moduleRegistry
		);
	}
}

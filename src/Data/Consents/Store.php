<?php

namespace BlueSpice\Privacy\Data\Consents;

use BlueSpice\Privacy\ModuleRegistry;
use MediaWiki\Config\GlobalVarConfig;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\UserFactory;
use Wikimedia\Rdbms\ILoadBalancer;

class Store extends \MWStake\MediaWiki\Component\CommonWebAPIs\Data\UserQueryStore\Store {

	/**
	 * @var ModuleRegistry
	 */
	protected $moduleRegistry;
	/** @var UserOptionsLookup */
	private $userOptionsLookup;

	/**
	 * @param ILoadBalancer $lb
	 * @param UserFactory $userFactory
	 * @param LinkRenderer $linkRenderer
	 * @param TitleFactory $titleFactory
	 * @param GlobalVarConfig $mwsgConfig
	 * @param UserOptionsLookup $userOptionsLookup
	 * @param ModuleRegistry $moduleRegistry
	 */
	public function __construct(
		ILoadBalancer $lb, UserFactory $userFactory, LinkRenderer $linkRenderer,
		TitleFactory $titleFactory, GlobalVarConfig $mwsgConfig,
		UserOptionsLookup $userOptionsLookup, ModuleRegistry $moduleRegistry
	) {
		parent::__construct( $lb, $userFactory, $linkRenderer, $titleFactory, $mwsgConfig );
		$this->moduleRegistry = $moduleRegistry;
		$this->userOptionsLookup = $userOptionsLookup;
	}

	/**
	 * @return Reader
	 */
	public function getReader() {
		return new Reader(
			$this->lb, $this->userFactory, $this->linkRenderer, $this->titleFactory,
			$this->mwsgConfig, $this->userOptionsLookup, $this->moduleRegistry
		);
	}
}

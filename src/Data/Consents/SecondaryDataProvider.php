<?php

namespace BlueSpice\Privacy\Data\Consents;

use BlueSpice\Privacy\ModuleRegistry;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\UserFactory;
use MWStake\MediaWiki\Component\CommonWebAPIs\Data\UserQueryStore\SecondaryDataProvider as UserSecondaryDataProvider;
use MWStake\MediaWiki\Component\CommonWebAPIs\Data\UserQueryStore\UserRecord;

class SecondaryDataProvider extends UserSecondaryDataProvider {

	/**
	 * @var UserOptionsLookup
	 */
	protected $userOptionsLookup;

	/**
	 * @var ModuleRegistry
	 */
	protected $moduleRegistry;

	public function __construct(
		UserFactory $userFactory, LinkRenderer $linkRenderer, TitleFactory $titleFactory,
		UserOptionsLookup $userOptionsLookup, ModuleRegistry $moduleRegistry
	) {
		parent::__construct( $userFactory, $linkRenderer, $titleFactory );
		$this->userOptionsLookup = $userOptionsLookup;
		$this->moduleRegistry = $moduleRegistry;
	}

	/**
	 * @inheritDoc
	 */
	public function extend( $dataSets ): array {
		$dataSets = parent::extend( $dataSets );
		$module = $this->moduleRegistry->getModuleByKey( 'consent' );
		if ( !$module ) {
			return $dataSets;
		}

		foreach ( $dataSets as $dataSet ) {
			$user = $this->userFactory->newFromName( $dataSet->get( UserRecord::USER_NAME ) );
			if ( !$user ) {
				continue;
			}
			$block = $user->getBlock( true );
			if ( $block && $block->appliesToRight( 'read' ) ) {
				continue;
			}

			foreach ( $module->getOptions() as $name => $prefName ) {
				$dataSet->set( $name, (bool)$this->userOptionsLookup->getOption( $user, $prefName ) );
			}
		}

		return $dataSets;
	}
}

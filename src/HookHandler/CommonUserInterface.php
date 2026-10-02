<?php

namespace BlueSpice\Privacy\HookHandler;

use BlueSpice\Privacy\EnhancedGlobalActionsAdministration;
use BlueSpice\Privacy\GlobalActionsAdministration;
use MediaWiki\Context\RequestContext;
use MediaWiki\Title\TitleFactory;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		private readonly TitleFactory $titleFactory
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$skin = RequestContext::getMain()->getSkin();
		$registry->register(
			'GlobalActionsAdministration',
			[
				'special-bluespice-privacy' => [
					'factory' => function () use ( $skin ) {
						if ( is_a( $skin, 'SkinBlueSpiceEclipseSkin', true ) ) {
							return new EnhancedGlobalActionsAdministration( $this->titleFactory );
						}
						return new GlobalActionsAdministration();
					}
				]
			]
		);
	}
}

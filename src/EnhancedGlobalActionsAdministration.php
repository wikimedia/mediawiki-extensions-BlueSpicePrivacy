<?php

namespace BlueSpice\Privacy;

use MediaWiki\Context\RequestContext;
use MediaWiki\Title\TitleFactory;

class EnhancedGlobalActionsAdministration extends GlobalActionsAdministration {

	/**
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		private readonly TitleFactory $titleFactory
	) {
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function getHref(): string {
		$title = $this->titleFactory->newFromText( 'w:Special:PrivacyAdmin' );
		if ( !defined( 'FARMER_IS_ROOT_WIKI_CALL' ) && !defined( FARMER_CALLED_INSTANCE ) ) {
			return $title->getFullURL();
		}
		if ( FARMER_IS_ROOT_WIKI_CALL ) {
			$title = $this->titleFactory->makeTitle( NS_SPECIAL, 'PrivacyAdmin' );
			return $title->getLocalURL();
		}

		$contextTitle = RequestContext::getMain()->getTitle();
		$instance = FARMER_CALLED_INSTANCE_OBJECT;
		$link = $instance->getInterwiki() . ':' . $contextTitle->getFullText();
		return $title->getLocalURL( 'backTo=' . $link );
	}
}

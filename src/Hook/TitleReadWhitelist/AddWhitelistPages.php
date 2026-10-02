<?php

namespace BlueSpice\Privacy\Hook\TitleReadWhitelist;

use BlueSpice\Privacy\Util\PrivacyPageProvider;
use MediaWiki\Permissions\Hook\TitleReadWhitelistHook;

class AddWhitelistPages implements TitleReadWhitelistHook {

	/**
	 * @param PrivacyPageProvider $pageProvider
	 */
	public function __construct(
		private readonly PrivacyPageProvider $pageProvider
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onTitleReadWhitelist( $title, $user, &$allowed ) {
		if ( $title->isSpecial( 'PrivacyPages' ) ) {
			$allowed = true;
			return false;
		}
		foreach ( $this->pageProvider->getNames() as $name ) {
			$page = $this->pageProvider->getPrivacyTitle( $name );
			if ( $page && $page->equals( $title ) ) {
				$allowed = true;
				return false;
			}
		}

		return true;
	}
}

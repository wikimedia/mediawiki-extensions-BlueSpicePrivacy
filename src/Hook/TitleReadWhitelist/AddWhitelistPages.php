<?php

namespace BlueSpice\Privacy\Hook\TitleReadWhitelist;

use BlueSpice\Hook\TitleReadWhitelist;
use MediaWiki\Title\Title;

class AddWhitelistPages extends TitleReadWhitelist {

	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		if ( $this->title->isSpecial( 'PrivacyPages' ) ) {
			return false;
		}

		$pages = [
			'bs-privacy-privacypage',
			'bs-privacy-termsofservicepage'
		];

		foreach ( $pages as $value ) {
			$page = $this->msg( $value );
			$title = Title::newFromText( $page->plain() );
			if ( $title && $this->title->equals( $title ) ) {
				return false;
			}
			$title = Title::newFromText( $page->inContentLanguage()->plain() );
			if ( $title && $this->title->equals( $title ) ) {
				return false;
			}

		}
		return true;
	}

	protected function doProcess() {
		$this->whitelisted = true;
	}
}

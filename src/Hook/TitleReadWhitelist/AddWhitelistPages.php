<?php

namespace BlueSpice\Privacy\Hook\TitleReadWhitelist;

use BlueSpice\Hook\TitleReadWhitelist;
use Title;

class AddWhitelistPages extends TitleReadWhitelist {

	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		$pages = [
			'bs-privacy-privacypage',
			'bs-privacy-termsofservicepage',
			'privacypages'
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

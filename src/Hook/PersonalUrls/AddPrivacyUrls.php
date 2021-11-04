<?php

namespace BlueSpice\Privacy\Hook\PersonalUrls;

use BlueSpice\Hook\PersonalUrls;

class AddPrivacyUrls extends PersonalUrls {

	protected function skipProcessing() {
		$user = $this->getContext()->getUser();
		return !$user->isRegistered();
	}

	protected function doProcess() {
		$this->personal_urls['privacycenter'] = [
			'href' => \SpecialPage::getTitleFor( 'PrivacyCenter' )->getLocalURL(),
			'text' => \MediaWiki\MediaWikiServices::getInstance()
				->getSpecialPageFactory()
				->getPage( 'PrivacyCenter' )->getDescription(),
			'position' => 70,
		];

		return true;
	}

}

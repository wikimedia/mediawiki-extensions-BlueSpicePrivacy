<?php

namespace BlueSpice\Privacy\Hook\PersonalUrls;

use BlueSpice\Hook\PersonalUrls;

class AddPrivacyUrls extends PersonalUrls {

	protected function skipProcessing() {
		$user = $this->getContext()->getUser();
		return !$user->isLoggedIn();
	}

	protected function doProcess() {
		$this->personal_urls['privacycenter'] = [
			'href' => \SpecialPage::getTitleFor( 'PrivacyCenter' )->getLocalURL(),
			'text' => \SpecialPageFactory::getPage( 'PrivacyCenter' )->getDescription()
		];

		return true;
	}

}

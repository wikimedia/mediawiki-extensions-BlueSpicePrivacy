<?php

namespace BlueSpice\Privacy\Hook\SkinTemplateOutputPageBeforeExec;

use BlueSpice\Hook\SkinTemplateOutputPageBeforeExec;

class AddCookieConsentFooterLink extends SkinTemplateOutputPageBeforeExec {
	protected function skipProcessing() {
		$title = $this->skin->getTitle();

		if ( \SpecialPage::getTitleFor( 'PrivacyCenter' )->equals( $title ) ) {
			return true;
		}

		return false;
	}

	protected function doProcess() {
		$this->template->set(
			'bsPrivacyCookieConsent',
			\Html::element(
				'a',
				[
					"id" => "bs-privacy-footer-change-cookie-settings",
					"style" => "cursor: pointer"
				],
				wfMessage( "bs-privacy-consent-cookie-settings-label" )->plain()
			)
		);

		$this->template->data['footerlinks']['places'][] = 'bsPrivacyCookieConsent';
		return true;
	}
}

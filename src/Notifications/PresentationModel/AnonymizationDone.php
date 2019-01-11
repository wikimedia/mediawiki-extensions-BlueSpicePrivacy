<?php

namespace BlueSpice\Privacy\Notifications\PresentationModel;

use BlueSpice\EchoConnector\EchoEventPresentationModel;

class AnonymizationDone extends EchoEventPresentationModel {
	/**
	 * Gets appropriate messages keys and params
	 * for header message
	 *
	 * @return array
	 */
	public function getHeaderMessageContent() {
		$headerKey = 'notification-bs-privacy-anonymization-done';
		$headerParams = [];

		return [
			'key' => $headerKey,
			'params' => $headerParams
		];
	}

	/**
	 * Gets appropriate message key and params for
	 * web notification message
	 *
	 * @return array
	 */
	public function getBodyMessageContent() {
		$bodyKey = 'notification-bs-privacy-anonymization-done-body';
		$bodyParams = [ 'oldUsername', 'newUsername' ];

		return [
			'key' => $bodyKey,
			'params' => $bodyParams
		];
	}

	/**
	 *
	 * @return array
	 */
	public function getPrimaryLink() {
		return [
			'url' => \SpecialPage::getTitleFor( 'Login' )->getFullURL(),
			'label' => wfMessage( 'notification-bs-privacy-login-url' )->plain()
		];
	}

	/**
	 *
	 * @return array
	 */
	public function getSecondaryLinks() {
		return [];
	}
}

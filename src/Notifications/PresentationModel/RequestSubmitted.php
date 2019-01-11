<?php

namespace BlueSpice\Privacy\Notifications\PresentationModel;

use BlueSpice\EchoConnector\EchoEventPresentationModel;

class RequestSubmitted extends EchoEventPresentationModel {

	/**
	 *
	 * @return array
	 */
	public function getHeaderMessageContent() {
		$headerKey = 'notification-bs-privacy-request-submitted';
		$headerParams = [];

		return [
			'key' => $headerKey,
			'params' => $headerParams
		];
	}

	/**
	 *
	 * @return array
	 */
	public function getBodyMessageContent() {
		$bodyKey = 'notification-bs-privacy-request-submitted-body';
		$bodyParams = [ 'agent', 'comment', 'module' ];

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
			'url' => \SpecialPage::getTitleFor( 'PrivacyAdmin' )->getFullURL(),
			'label' => wfMessage( 'notification-bs-privacy-admin-url' )->plain()
		];
	}

	/**
	 *
	 * @return array
	 */
	public function getSecondaryLinks() {
		return [ $this->getAgentLink() ];
	}
}

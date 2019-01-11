<?php

namespace BlueSpice\Privacy\Notifications\PresentationModel;

use BlueSpice\EchoConnector\EchoEventPresentationModel;

class RequestDeletionDenied extends EchoEventPresentationModel {
	/**
	 * Gets appropriate messages keys and params
	 * for header message
	 *
	 * @return array
	 */
	public function getHeaderMessageContent() {
		$headerKey = 'notification-bs-privacy-request-denied-deletion';
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
		$bodyKey = 'notification-bs-privacy-request-denied-deletion-body';
		$bodyParams = [ 'agent', 'comment' ];

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
			'url' => \SpecialPage::getTitleFor( 'PrivacyCenter' )->getFullURL(),
			'label' => wfMessage( 'notification-bs-privacy-center-url' )->plain()
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

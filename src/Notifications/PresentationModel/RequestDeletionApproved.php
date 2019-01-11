<?php

namespace BlueSpice\Privacy\Notifications\PresentationModel;

use BlueSpice\EchoConnector\EchoEventPresentationModel;

class RequestDeletionApproved extends EchoEventPresentationModel {
	/**
	 * Gets appropriate messages keys and params
	 * for header message
	 *
	 * @return array
	 */
	public function getHeaderMessageContent() {
		$headerKey = 'notification-bs-privacy-deletion-approved';
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
		$bodyKey = 'notification-bs-privacy-deletion-approved-body';
		$bodyParams = [];

		return [
			'key' => $bodyKey,
			'params' => $bodyParams
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

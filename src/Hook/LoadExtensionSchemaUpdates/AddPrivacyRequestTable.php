<?php

namespace BlueSpice\Privacy\Hook\LoadExtensionSchemaUpdates;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddPrivacyRequestTable extends LoadExtensionSchemaUpdates {
	protected function doProcess() {
		$dir = $this->getExtensionPath() . '/maintenance/db';

		$this->updater->addExtensionTable(
			'bs_privacy_request',
			"$dir/bs_privacy_request.sql"
		);
	}

	/**
	 *
	 * @return string
	 */
	protected function getExtensionPath() {
		return dirname( dirname( dirname( __DIR__ ) ) );
	}
}

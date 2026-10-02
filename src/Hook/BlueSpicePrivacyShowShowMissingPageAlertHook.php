<?php

namespace BlueSpice\Privacy\Hook;

use MediaWiki\Skin\Skin;

interface BlueSpicePrivacyShowShowMissingPageAlertHook {

	/**
	 * @param Skin $skin
	 * @param bool &$showAlert
	 * @return void
	 */
	public function onBlueSpicePrivacyShowShowMissingPageAlert( Skin $skin, bool &$showAlert ): void;
}

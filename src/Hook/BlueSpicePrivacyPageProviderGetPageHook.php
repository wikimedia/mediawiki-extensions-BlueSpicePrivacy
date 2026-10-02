<?php

namespace BlueSpice\Privacy\Hook;

use MediaWiki\Title\Title;

interface BlueSpicePrivacyPageProviderGetPageHook {

	/**
	 * @param string $name
	 * @param Title|null &$title
	 * @return void
	 */
	public function onBlueSpicePrivacyPageProviderGetPage( string $name, ?Title &$title ): void;
}

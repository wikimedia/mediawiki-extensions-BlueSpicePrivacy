<?php

namespace BlueSpice\Privacy;

class Extension extends \BlueSpice\Extension {
	public static function onCallback() {
		$GLOBALS['wgLogRestrictions']['bs-privacy'] = 'bs-privacy-admin';

		$GLOBALS['mwsgCommonUIComponentFilters'] = array_merge(
			$GLOBALS['mwsgCommonUIComponentFilters'],
			[
				'privacy' => [
					'class' => 'BlueSpice\\Privacy\\ComponentFilter\\PrivacyFilter',
					'services' => [ 'TitleFactory' ]
				]
			]
		);
	}
}

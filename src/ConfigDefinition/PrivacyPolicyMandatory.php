<?php

namespace BlueSpice\Privacy\ConfigDefinition;

use BlueSpice\ConfigDefinition\BooleanSetting;
use BlueSpice\Privacy\ISettingPaths;

class PrivacyPolicyMandatory extends BooleanSetting implements ISettingPaths {

	/**
	 *
	 * @return string[]
	 */
	public function getPaths() {
		return [
			static::MAIN_PATH_FEATURE . '/' . static::FEATURE_PRIVACY . '/BlueSpicePrivacy',
			static::MAIN_PATH_EXTENSION . '/BlueSpicePrivacy/' . static::FEATURE_PRIVACY,
			static::MAIN_PATH_PACKAGE . '/' . static::PACKAGE_FREE . '/BlueSpicePrivacy',
		];
	}

	/**
	 *
	 * @return string
	 */
	public function getLabelMessageKey() {
		return 'bs-privacy-prefs-privacypolicymandatory-label';
	}

	/**
	 *
	 * @return string
	 */
	public function getHelpMessageKey() {
		return 'bs-privacy-prefs-privacypolicymandatory-help';
	}
}

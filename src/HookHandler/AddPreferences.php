<?php

namespace BlueSpice\Privacy\HookHandler;

use BlueSpice\Privacy\Module\Consent;
use BlueSpice\Privacy\ModuleRegistry;
use MediaWiki\Preferences\Hook\GetPreferencesHook;

class AddPreferences implements GetPreferencesHook {

	/**
	 * @var ModuleRegistry
	 */
	private $moduleRegistry;

	/**
	 * @param ModuleRegistry $moduleRegistry
	 */
	public function __construct( ModuleRegistry $moduleRegistry ) {
		$this->moduleRegistry = $moduleRegistry;
	}

	/**
	 * @inheritDoc
	 */
	public function onGetPreferences( $user, &$preferences ) {
		$module = $this->moduleRegistry->getModuleByKey( 'consent' );
		if ( !( $module instanceof Consent ) ) {
			return true;
		}
		foreach ( $module->getUserPreferenceDescriptors() as $name => $config ) {
			$preferences[$name] = $config;
		}

		return true;
	}
}

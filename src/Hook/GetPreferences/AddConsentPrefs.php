<?php

namespace BlueSpice\Privacy\Hook\GetPreferences;

use BlueSpice\Hook\GetPreferences;
use BlueSpice\Privacy\ModuleRegistry;

class AddConsentPrefs extends GetPreferences {

	protected function doProcess() {
		$moduleRegistry = new ModuleRegistry();
		$moduleConfig = $moduleRegistry->getModuleByKey( 'consent' );
		$module = new $moduleConfig['class']( $this->getContext() );

		foreach ( $module->getUserPreferenceDescriptors() as $name => $config ) {
			$this->preferences[$name] = $config;
		}

		return true;
	}
}

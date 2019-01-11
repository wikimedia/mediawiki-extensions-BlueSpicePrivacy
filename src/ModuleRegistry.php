<?php

namespace BlueSpice\Privacy;

use BlueSpice\ExtensionAttributeBasedRegistry;

class ModuleRegistry extends ExtensionAttributeBasedRegistry {

	public function __construct() {
		parent::__construct( 'BlueSpicePrivacyModules' );
	}

	/**
	 *
	 * @return array
	 */
	public function getAllModules() {
		$modules = [];
		foreach ( $this->getAllKeys() as $key ) {
			$modules[$key] = $this->getModuleByKey( $key );
		}

		return $modules;
	}

	/**
	 *
	 * @param array $module
	 * @return string
	 */
	public function getModuleClass( $module ) {
		$module = $this->getModuleByKey( $module );
		if ( $module ) {
			return $module['class'];
		}
		return '';
	}

	/**
	 *
	 * @param string $key
	 * @return bool
	 */
	public function getModuleByKey( $key ) {
		$registry = $this->extensionRegistry->getAttribute( $this->attribName );
		if ( !isset( $registry[$key] ) ) {
			return false;
		}

		$rawModule = $registry[$key];

		$class = $rawModule['class'];
		if ( is_array( $class ) ) {
			$class = end( $class );
		}

		$module = [
			'class' => $class
		];

		return $module;
	}
}

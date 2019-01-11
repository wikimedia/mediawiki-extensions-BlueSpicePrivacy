<?php

namespace BlueSpice\Privacy;

use BlueSpice\ExtensionAttributeBasedRegistry;

class HandlerRegistry extends ExtensionAttributeBasedRegistry {
	/**
	 *
	 */
	public function __construct() {
		parent::__construct( 'BlueSpicePrivacyHandlers' );
	}

	/**
	 *
	 * @return array
	 */
	public function getAllHandlers() {
		$handlers = [];
		foreach ( $this->getAllKeys() as $key ) {
			$handlers[$key] = $this->getHandlerByKey( $key );
		}

		return $handlers;
	}

	/**
	 *
	 * @param string $key
	 * @return string|false
	 */
	public function getHandlerByKey( $key ) {
		$registry = $this->extensionRegistry->getAttribute( $this->attribName );
		if ( !isset( $registry[$key] ) ) {
			return false;
		}

		return $registry[$key];
	}
}

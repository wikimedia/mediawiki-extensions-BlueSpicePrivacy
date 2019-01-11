<?php

namespace BlueSpice\Privacy\Auth\Request;

use BlueSpice\Privacy\Auth\PrefSaver;
use BlueSpice\Privacy\Module\Consent;
use BlueSpice\Privacy\ModuleRegistry;
use MediaWiki\Auth\UserDataAuthenticationRequest;

class SignupAuthenticationRequest extends UserDataAuthenticationRequest {

	/**
	 *
	 * @return array
	 */
	public function getFieldInfo() {
		$moduleRegistry = new ModuleRegistry();
		$moduleConfig = $moduleRegistry->getModuleByKey( 'consent' );
		$module = new $moduleConfig['class']( \RequestContext::getMain() );

		$module instanceof Consent;
		return $module->getAuthFormDescriptors();
	}

	/**
	 *
	 * @return string
	 */
	public function getUniqueId() {
		return self::class;
	}

	/**
	 *
	 * @param \User $user
	 * @return \Status
	 */
	public function populateUser( $user ) {
		$moduleRegistry = new ModuleRegistry();
		$moduleConfig = $moduleRegistry->getModuleByKey( 'consent' );
		$module = new $moduleConfig['class']( \RequestContext::getMain() );
		$module instanceof Consent;

		$data = [];
		foreach ( $module->getOptions() as $name => $prefName ) {
			$data[$prefName] = $this->$name;
		}

		// Couldn't find another way to persist data between request and AuthProvider
		$prefSaver = PrefSaver::getInstance();
		$prefSaver->setData( $data );

		return \StatusValue::newGood();
	}
}

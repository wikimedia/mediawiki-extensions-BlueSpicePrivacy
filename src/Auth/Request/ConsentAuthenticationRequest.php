<?php

namespace BlueSpice\Privacy\Auth\Request;

use BlueSpice\Privacy\Module\Consent;
use MediaWiki\Auth\UserDataAuthenticationRequest;
use RequestContext;

class ConsentAuthenticationRequest extends UserDataAuthenticationRequest {
	/** @var Consent|null */
	protected $module = null;

	/**
	 *
	 * @return array
	 */
	public function getFieldInfo() {
		$module = new Consent( RequestContext::getMain() );
		return $module->getAuthFormDescriptors();
	}

	/**
	 *
	 * @return string
	 */
	public function getUniqueId() {
		return 'BlueSpicePrivacyConsentAuthenticationRequest';
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	public function loadFromSubmission( array $data ) {
		$module = new Consent( RequestContext::getMain() );
		foreach ( array_keys( $module->getOptions() ) as $name ) {
			if ( isset( $data[$name] ) ) {
				$this->$name = $data[$name];
			}
		}

		return true;
	}
}

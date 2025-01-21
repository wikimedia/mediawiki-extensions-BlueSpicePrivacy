<?php

namespace BlueSpice\Privacy\Auth\Request;

use BlueSpice\Privacy\Module\Consent;
use MediaWiki\Auth\UserDataAuthenticationRequest;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;

class ConsentAuthenticationRequest extends UserDataAuthenticationRequest {
	/** @var Consent|null */
	protected $module = null;

	/**
	 *
	 * @return array
	 */
	public function getFieldInfo() {
		$module = $this->getConsent();
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
		$module = $this->getConsent();
		foreach ( array_keys( $module->getOptions() ) as $name ) {
			if ( isset( $data[$name] ) ) {
				$this->$name = $data[$name];
			}
		}

		return true;
	}

	/**
	 *
	 * @return Consent
	 */
	private function getConsent(): Consent {
		$services = MediaWikiServices::getInstance();
		$lb = $services->getDBLoadBalancer();
		$notifier = $services->get( 'MWStake.Notifier' );
		$permissionManager = $services->getPermissionManager();
		$userOptionsManager = $services->getUserOptionsManager();
		$configFactory = $services->getConfigFactory();
		$config = $services->getMainConfig();
		$consent = new Consent( $lb, $notifier, $permissionManager,
			$userOptionsManager, $configFactory, $config );
		$user = RequestContext::getMain()->getUser();
		$consent->setUser( $user );
		return $consent;
	}
}

<?php

namespace BlueSpice\Privacy\Module;

use BlueSpice\Privacy\CookieConsentProviderRegistry;
use BlueSpice\Privacy\ICookieConsentProvider;
use BlueSpice\Privacy\Module;
use BlueSpice\Services;

class Consent extends Module {
	/**
	 * @var \User
	 */
	protected $user;

	/**
	 *
	 * @var array
	 */
	protected $options = [];

	/**
	 * @var \Config
	 */
	protected $config;

	/**
	 * @var ICookieConsentProvider|null
	 */
	protected $cookieConsentProvider;

	/**
	 *
	 * @param \IContextSource $context
	 */
	public function __construct( $context ) {
		parent::__construct( $context );
		$this->user = $context->getUser();
		$this->config = Services::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		$this->options = $this->config->get( 'PrivacyConsentTypes' );

		$providerRegistry = new CookieConsentProviderRegistry();
		$this->cookieConsentProvider = $providerRegistry->getProvider();
	}

	/**
	 *
	 * @param string $func
	 * @param array $data
	 * @return \Status
	 */
	public function call( $func, $data ) {
		if ( !$this->verifyUser() ) {
			\Status::newFatal( wfMessage( 'bs-privacy-invalid-user' ) );
		}

		if ( $func === 'getConsent' ) {
			return $this->getConsent();
		} elseif ( $func === 'setConsent' ) {
			if ( !isset( $data['consents'] ) ) {
				return \Status::newFatal( wfMessage( 'bs-privacy-missing-param', "consents" ) );
			}

			return $this->setConsent( $data['consents'] );
		}

		return \Status::newFatal( wfMessage( 'bs-privacy-module-no-function', $func ) );
	}

	/**
	 *
	 * @return string
	 */
	public function getModuleName() {
		return 'consent';
	}

	/**
	 *
	 * @return \Status
	 */
	protected function getConsent() {
		$consents = [];
		foreach ( $this->options as $optionName => $userPreference ) {
			$consents[$optionName] = [
				'value' => $this->user->getOption( $userPreference ),
				'label' => wfMessage( $userPreference )->parse(),
				'help' => wfMessage( "$userPreference-help" )->parse()
			];
		}
		return \Status::newGood( [
			'consents' => $consents
		] );
	}

	/**
	 *
	 * @param array $consents
	 * @return \Status
	 */
	protected function setConsent( $consents ) {
		$consentsForLog = [];

		foreach ( $consents as $consentName => $value ) {
			if ( !isset( $this->options[$consentName] ) ) {
				continue;
			}

			$consentMessage = wfMessage( $this->options[$consentName] )->parse();
			$valueMessage = $value ?
				wfMessage( 'bs-privacy-consent-bool-true' )->plain() :
				wfMessage( 'bs-privacy-consent-bool-false' )->plain();
			$consentsForLog[] = wfMessage(
				'bs-privacy-consent-log-consent',
				$consentMessage,
				$valueMessage
			)->plain();

			$this->user->setOption( $this->options[$consentName], $value );
		}
		$this->user->saveSettings();

		$this->logAction( [
			'consents' => implode( ', ', $consentsForLog )
		] );

		return \Status::newGood();
	}

	/**
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 *
	 * @return array
	 */
	public function getUserPreferenceDescriptors() {
		$descriptors = [];
		foreach ( $this->options as $name => $preferenceName ) {
			$descriptors[$preferenceName] = [
				'type' => 'toggle',
				'label-message' => $preferenceName,
				'section' => 'personal/info'
			];
		}

		return $descriptors;
	}

	/**
	 *
	 * @return array
	 */
	public function getAuthFormDescriptors() {
		$descriptors = [];
		foreach ( $this->options as $name => $preferenceName ) {
			$descriptors[$name] = [
				'type' => 'checkbox',
				'label' => wfMessage( $preferenceName ),
				'help-message' => "$preferenceName-help",
				'optional' => true,
			];
		}

		return $descriptors;
	}

	/**
	 * Get RL modules required to run this module
	 * @param string $type
	 * @return string|null
	 */
	public function getRLModule( $type ) {
		if ( $type === static::MODULE_UI_TYPE_USER ) {
			return "ext.bs.privacy.module.consent.user";
		} elseif ( $type === static::MODULE_UI_TYPE_ADMIN ) {
			return "ext.bs.privacy.module.consent.admin";
		}
	}

	/**
	 * @param string $type
	 * @return string|array|null
	 */
	public function getUIWidget( $type ) {
		if ( $type === static::MODULE_UI_TYPE_USER ) {
			$widgetData = [];
			if ( $this->cookieConsentProvider ) {
				$widgetData['cookieConsentProvider'] = [
					"class" => $this->cookieConsentProvider->getHandlerClass(),
					"config" => [
						"map" => $this->cookieConsentProvider->getGroupMapping(),
						"cookieName" => $this->cookieConsentProvider->getCookieName()
					]
				];
			}
			return [
				"callback" => "bs.privacy.widget.Consent",
				"data" => $widgetData
			];
		} elseif ( $type === static::MODULE_UI_TYPE_ADMIN ) {
			return [
				"callback" => "bs.privacy.widget.ConsentOverview",
				"data" => [
					"consentTypes" => $this->config->get( 'PrivacyConsentTypes' )
				]
			];
		}
	}

}

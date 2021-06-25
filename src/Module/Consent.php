<?php

namespace BlueSpice\Privacy\Module;

use BlueSpice\Privacy\CookieConsentProviderRegistry;
use BlueSpice\Privacy\Html\CheckLinkField;
use BlueSpice\Privacy\ICookieConsentProvider;
use BlueSpice\Privacy\Module;
use BlueSpice\Services;
use User;

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
	 * @param string|null $type
	 * @return array
	 */
	public function getAuthFormDescriptors( $type = 'checkbox' ) {
		$descriptors = [];
		foreach ( $this->options as $name => $preferenceName ) {
			// Give grep a chance to find the usages:
			// bs-privacy-prefs-consent-cookies-help
			// bs-privacy-prefs-consent-privacy-policy-help
			$helpMessageKey = "$preferenceName-help";
			$descriptors[$name] = [
				'type' => $type,
				'class' => CheckLinkField::class,
				'label' => wfMessage( $preferenceName ),
				'help' => wfMessage( $helpMessageKey ) ,
				'default' => $this->user->getOption( $preferenceName ),
				// B/C
				'help-message' => $helpMessageKey,
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

	/**
	 * Check if user consented
	 *
	 * @param User $user
	 * @return bool
	 */
	public function hasUserConsented( User $user ) {
		foreach ( $this->getOptions() as $name => $prefName ) {
			if ( !$user->getOption( $prefName, false ) ) {
				return false;
			}
		}

		return true;
	}

}

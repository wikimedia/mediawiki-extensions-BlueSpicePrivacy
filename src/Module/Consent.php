<?php

namespace BlueSpice\Privacy\Module;

use BlueSpice\Privacy\CookieConsentProviderRegistry;
use BlueSpice\Privacy\Html\CheckLinkField;
use BlueSpice\Privacy\ICookieConsentProvider;
use BlueSpice\Privacy\Module;
use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Status\Status;
use MediaWiki\User\Options\UserOptionsManager;
use MediaWiki\User\User;
use MWStake\MediaWiki\Component\Events\Notifier;
use Wikimedia\Rdbms\ILoadBalancer;

class Consent extends Module {
	/**
	 * @var UserOptionsManager
	 */
	protected $userOptionsManager;

	/**
	 * @var ICookieConsentProvider|null
	 */
	protected $cookieConsentProvider;

	/**
	 * @var ConfigFactory
	 */
	protected $configFactory;

	/**
	 * @var Config
	 */
	protected $mainConfig;

	public function __construct(
		ILoadBalancer $lb, Notifier $notifier, PermissionManager $permissionManager,
		UserOptionsManager $userOptionsManager, ConfigFactory $configFactory, Config $mainConfig
	) {
		parent::__construct( $lb, $notifier, $permissionManager );
		$this->userOptionsManager = $userOptionsManager;
		$this->configFactory = $configFactory;
		$this->mainConfig = $mainConfig;
		$providerRegistry = new CookieConsentProviderRegistry();
		$this->cookieConsentProvider = $providerRegistry->getProvider();
	}

	/**
	 *
	 * @param string $func
	 * @param array $data
	 * @return Status
	 */
	public function call( $func, $data ) {
		if ( !$this->verifyUser() ) {
			return Status::newFatal( wfMessage( 'bs-privacy-invalid-user' ) );
		}

		if ( $func === 'getConsent' ) {
			return $this->getConsent();
		} elseif ( $func === 'setConsent' ) {
			if ( !isset( $data['consents'] ) ) {
				return Status::newFatal( wfMessage( 'bs-privacy-missing-param', "consents" ) );
			}

			return $this->setConsent( $data['consents'] );
		}

		return Status::newFatal( wfMessage( 'bs-privacy-module-no-function', $func ) );
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
	 * @return Status
	 */
	protected function getConsent() {
		$consents = [];
		foreach ( $this->getOptions() as $optionName => $userPreference ) {
			$consents[$optionName] = [
				'value' => $this->userOptionsManager->getOption( $this->user, $userPreference ),
				'label' => wfMessage( $userPreference )->parse(),
				'help' => wfMessage( "$userPreference-help" )->parse()
			];
		}
		return Status::newGood( [
			'consents' => $consents
		] );
	}

	/**
	 *
	 * @param array $consents
	 * @return Status
	 */
	protected function setConsent( $consents ) {
		$consentsForLog = [];

		foreach ( $consents as $consentName => $value ) {
			if ( !isset( $this->getOptions()[$consentName] ) ) {
				continue;
			}

			$consentMessage = wfMessage( $this->getOptions()[$consentName] )->parse();
			$valueMessage = $value ?
				wfMessage( 'bs-privacy-consent-bool-true' )->plain() :
				wfMessage( 'bs-privacy-consent-bool-false' )->plain();
			$consentsForLog[] = wfMessage(
				'bs-privacy-consent-log-consent',
				$consentMessage,
				$valueMessage
			)->plain();

			$this->userOptionsManager->setOption( $this->user, $this->getOptions()[$consentName], $value );
		}
		$this->user->saveSettings();

		$this->logAction( [
			'consents' => implode( ', ', $consentsForLog )
		] );

		return Status::newGood();
	}

	/**
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->configFactory->makeConfig( 'bsg' )->get( 'PrivacyConsentTypes' );
	}

	/**
	 *
	 * @return array
	 */
	public function getUserPreferenceDescriptors() {
		$descriptors = [];
		foreach ( $this->getOptions() as $name => $preferenceName ) {
			$descriptors[$preferenceName] = [
				'type' => 'toggle',
				'label-message' => $preferenceName,
				'section' => 'personal/privacy'
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
		foreach ( $this->getOptions() as $name => $preferenceName ) {
			$helpMessageKey = "$preferenceName-help";
			// Give grep a chance to find the usages:
			// bs-privacy-prefs-consent-privacy-policy
			// bs-privacy-prefs-consent-privacy-policy-help
			// bs-privacy-prefs-consent-tos
			// bs-privacy-prefs-consent-tos-help
			$descriptors[$name] = [
				'type' => $type,
				'class' => CheckLinkField::class,
				'label' => wfMessage( $preferenceName ),
				'help' => wfMessage( $helpMessageKey ),
				'default' => $this->userOptionsManager->getOption( $this->user, $preferenceName ),
				// B/C
				'help-message' => $helpMessageKey,
				// validation is implemented elsewhere
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
		return null;
	}

	/**
	 * @param string $type
	 * @return array|null
	 */
	public function getUIWidget( $type ) {
		if ( $type === static::MODULE_UI_TYPE_USER ) {
			$widgetData = [];
			if ( $this->cookieConsentProvider ) {
				$widgetData['cookieConsentProvider'] = [
					"class" => $this->cookieConsentProvider->getHandlerClass(),
					"config" => [
						"cookirMap" => $this->cookieConsentProvider->getGroupMapping(),
						"cookieName" => $this->cookieConsentProvider->getCookieName(),
						"cookiePrefix" => $this->mainConfig->get( 'CookiePrefix' ),
						"cookiePath" => $this->mainConfig->get( 'CookiePath' ),
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
					"consentTypes" => $this->configFactory->makeConfig( 'bsg' )->get( 'PrivacyConsentTypes' ),
				]
			];
		}
		return null;
	}

	/**
	 * Check if user consented
	 *
	 * @param User $user
	 * @return bool
	 */
	public function hasUserConsented( User $user ) {
		foreach ( $this->getOptions() as $name => $prefName ) {
			if ( !$this->userOptionsManager->getBoolOption( $user, $prefName, false ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if PrivacyPolicy consent is mandatory
	 *
	 * @return bool
	 */
	public function isPrivacyPolicyConsentMandatory() {
		$config = $this->configFactory->makeConfig( 'bsg' );
		if ( !$config->has( 'PrivacyPrivacyPolicyMandatory' ) ) {
			return false;
		}

		return $config->get( 'PrivacyPrivacyPolicyMandatory' );
	}
}

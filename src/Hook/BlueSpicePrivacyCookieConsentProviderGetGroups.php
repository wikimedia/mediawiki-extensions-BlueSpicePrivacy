<?php

namespace BlueSpice\Privacy\Hook;

use BlueSpice\Hook;
use BlueSpice\Privacy\CookieConsentProvider\Base as CookieConsentProvider;
use Config;
use IContextSource;

abstract class BlueSpicePrivacyCookieConsentProviderGetGroups extends Hook {
	/** @var CookieConsentProvider */
	protected $provider;
	/** @var */
	protected $groups;

	/**
	 * @param CookieConsentProvider $provider
	 * @param array &$groups
	 * @return static
	 */
	public static function callback( $provider, &$groups ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$provider,
			$groups
		);

		return $hookHandler->process();
	}

	/**
	 * @param IContextSource $context
	 * @param Config $config
	 * @param CookieConsentProvider $provider
	 * @param array &$groups
	 */
	public function __construct( $context, $config, $provider, &$groups ) {
		parent::__construct( $context, $config );

		$this->provider = $provider;
		$this->groups = &$groups;
	}
}

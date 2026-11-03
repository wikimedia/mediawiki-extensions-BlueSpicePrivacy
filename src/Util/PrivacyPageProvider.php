<?php

namespace BlueSpice\Privacy\Util;

use MediaWiki\Config\Config;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Html\Html;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;

class PrivacyPageProvider {

	private const NAMES = [ 'TermsOfService', 'PrivacyPolicy' ];

	private const PAGE_MSG_KEYS = [
		'TermsOfService' => 'bs-privacy-termsofservicepage',
		'PrivacyPolicy' => 'bs-privacy-privacypage'
	];

	private const DISPLAY_NAMES = [
		'TermsOfService' => 'bs-privacy-consent-type-terms-of-service-short',
		'PrivacyPolicy' => 'bs-privacy-consent-type-privacy-policy-short'
	];

	/** @var array */
	private array $privacyTitles = [];

	/**
	 * @param Config $bsConfig
	 * @param TitleFactory $titleFactory
	 * @param HookContainer $hookContainer
	 */
	public function __construct(
		private readonly Config $bsConfig,
		private readonly TitleFactory $titleFactory,
		private readonly HookContainer $hookContainer
	) {
	}

	/**
	 * @return string[]
	 */
	public function getNames(): array {
		return static::NAMES;
	}

	/**
	 * @return string[]
	 */
	public function getExtendedNames(): array {
		return static::NAMES + [
			'privacypage',
			'aboutpage',
			'disclaimerpage'
		];
	}

	/**
	 * @return array
	 */
	public function getDisplayNames(): array {
		$res = [];
		foreach ( self::NAMES as $name ) {
			$res[$name] = Message::newFromKey( self::DISPLAY_NAMES[$name] )->inContentLanguage()->text();
		}
		return $res;
	}

	/**
	 * @param string $name
	 * @return Title|null
	 */
	public function getPrivacyTitle( string $name ): ?Title {
		if ( isset( $this->privacyTitles[$name] ) ) {
			return $this->privacyTitles[$name];
		}
		if ( !in_array( $name, self::NAMES ) ) {
			return null;
		}
		$configName = "Privacy{$name}Link";
		if ( $this->bsConfig->has( $configName ) && $this->bsConfig->get( $configName ) ) {
			// Manual configuration exists, no Title
			return null;
		}

		$pageName = Message::newFromKey( self::PAGE_MSG_KEYS[$name] )->inContentLanguage()->text();
		$title = $this->titleFactory->newFromText( $pageName );
		$this->hookContainer->run( 'BlueSpicePrivacyPageProviderGetPage', [ $name, &$title ] );

		$this->privacyTitles[$name] = $title;
		return $this->getPrivacyTitle( $name );
	}

	/**
	 * @param string $name
	 * @return string|null
	 */
	public function getPageUrl( string $name ): ?string {
		$configName = "Privacy{$name}Link";
		if ( $this->bsConfig->has( $configName ) && $this->bsConfig->get( $configName ) ) {
			return $this->bsConfig->get( $configName );
		}
		$title = $this->getPrivacyTitle( $name );
		return $title?->getFullURL();
	}

	/**
	 * @return array
	 */
	public function getLinksHtml(): array {
		return array_map( function ( $name ) {
			return $this->getLinkHtml( $name );
		}, self::NAMES );
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function getLinkHtml( string $name ): string {
		$url = $this->getPageUrl( $name );
		if ( !$url ) {
			return '';
		}
		$label = Message::newFromKey( self::DISPLAY_NAMES[$name] )->inContentLanguage()->text();
		return Html::rawElement( 'a', [
			'href' => $url,
			'target' => '_blank',
		], $label );
	}

	/**
	 * @param Title|null $title
	 * @return bool
	 */
	public function isPrivacyPage( ?Title $title ): bool {
		if ( !$title ) {
			return false;
		}
		if ( $title->isSpecial( 'PrivacyPages' ) ) {
			return true;
		}
		foreach ( self::NAMES as $name ) {
			$privacyTitle = $this->getPrivacyTitle( $name );
			if ( !$privacyTitle ) {
				continue;
			}
			if ( $title->equals( $privacyTitle ) ) {
				return true;
			}
		}
		return false;
	}
}

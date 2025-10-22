<?php

namespace BlueSpice\Privacy\ComponentFilter;

use MediaWiki\Context\IContextSource;
use MediaWiki\Title\TitleFactory;
use MWStake\MediaWiki\Component\CommonUserInterface\IComponent;
use MWStake\MediaWiki\Component\CommonUserInterface\IComponentFilter;

class PrivacyFilter implements IComponentFilter {

	private const HIDDEN_COMPONENTS = [
		'BlueSpice\Discovery\Component\MainLinksPanel',
		'BlueSpice\Discovery\Component\MediaWikiLinksPanel',
		'BlueSpice\Discovery\Component\NamespaceMainPages',
		'BlueSpice\Discovery\Component\EnhancedSidebarContainer',
		'BlueSpice\Discovery\Component\SubpageTreePanel',
		'BlueSpice\Discovery\Component\SidebarSecondaryToggleButton',
		'BlueSpice\Discovery\Component\SidebarPrimaryToggleButton',
		'BlueSpice\Discovery\Component\SidebarPrimaryToggleButtonMobile'
	];

	private const PRIVACY_PAGES = [
		'bs-privacy-privacypage',
		'bs-privacy-termsofservicepage',
		'privacypage',
		'aboutpage',
		'disclaimerpage'
	];

	/**
	 *
	 * @var TitleFactory
	 */
	private $titleFactory = null;

	/**
	 *
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( TitleFactory $titleFactory ) {
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function shouldRender( IComponent $component, IContextSource $context ): bool {
		$user = $context->getUser();
		if ( $user->isRegistered() ) {
			return true;
		}

		$handledComponent = false;
		foreach ( self::HIDDEN_COMPONENTS as $componentClass ) {
			if ( $component instanceof $componentClass ) {
				$handledComponent = true;
				break;
			}
		}
		if ( !$handledComponent ) {
			return true;
		}

		$title = $context->getTitle();
		if ( $title->isSpecial( 'PrivacyPages' ) ) {
			return false;
		}

		foreach ( self::PRIVACY_PAGES as $value ) {
			$page = $context->msg( $value );
			$privacytitle = $this->titleFactory->newFromText( $page->inContentLanguage()->text() );
			if ( $privacytitle && $title->equals( $privacytitle ) ) {
				return false;
			}
		}

		return true;
	}
}

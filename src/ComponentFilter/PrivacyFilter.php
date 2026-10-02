<?php

namespace BlueSpice\Privacy\ComponentFilter;

use BlueSpice\Privacy\Util\PrivacyPageProvider;
use MediaWiki\Context\IContextSource;
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

	/**
	 * @param PrivacyPageProvider $pageProvider
	 */
	public function __construct(
		private readonly PrivacyPageProvider $pageProvider
	) {
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
		if ( !$title || $title->isSpecial( 'PrivacyPages' ) ) {
			return false;
		}
		foreach ( $this->pageProvider->getExtendedNames() as $name ) {
			$privacyTitle = $this->pageProvider->getPrivacyTitle( $name );
			if ( !$privacyTitle ) {
				continue;
			}
			if ( $title->equals( $privacyTitle ) ) {
				return false;
			}
		}

		return true;
	}
}

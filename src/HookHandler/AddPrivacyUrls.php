<?php

namespace BlueSpice\Privacy\HookHandler;

use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\MediaWikiServices;

class AddPrivacyUrls implements SkinTemplateNavigation__UniversalHook {

	/**
	 * // phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	 * @inheritDoc
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		$user = $sktemplate->getUser();
		if ( !$user->isRegistered() ) {
			return;
		}

		$links['user-menu']['privacycenter'] = [
			'id' => 'pt-privacycenter',
			'href' => \SpecialPage::getTitleFor( 'PrivacyCenter' )->getLocalURL(),
			'text' => MediaWikiServices::getInstance()->getSpecialPageFactory()
				->getPage( 'PrivacyCenter' )->getDescription(),
			'position' => 70,
		];
	}
}

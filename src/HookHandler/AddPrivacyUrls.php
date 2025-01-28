<?php

namespace BlueSpice\Privacy\HookHandler;

use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;

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
		$specialPage = MediaWikiServices::getInstance()->getSpecialPageFactory()->getPage( 'PrivacyCenter' );
		if ( !$specialPage ) {
			return;
		}

		$links['user-menu']['privacycenter'] = [
			'id' => 'pt-privacycenter',
			'href' => SpecialPage::getTitleFor( 'PrivacyCenter' )->getLocalURL(),
			'text' => $specialPage->getDescription(),
			'position' => 70,
		];
	}
}

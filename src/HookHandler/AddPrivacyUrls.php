<?php

namespace BlueSpice\Privacy\HookHandler;

use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\MediaWikiServices;
use RequestContext;

class AddPrivacyUrls implements SkinTemplateNavigation__UniversalHook {

	/**
	 * // phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	 * @inheritDoc
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		$user = RequestContext::getMain()->getUser();
		if ( !$user->isRegistered() ) {
			return;
		}

		$links['privacycenter'] = [
			'href' => \SpecialPage::getTitleFor( 'PrivacyCenter' )->getLocalURL(),
			'text' => MediaWikiServices::getInstance()->getSpecialPageFactory()
				->getPage( 'PrivacyCenter' )->getDescription(),
			'position' => 70,
		];
	}
}

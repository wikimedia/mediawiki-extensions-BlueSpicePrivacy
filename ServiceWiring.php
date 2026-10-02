<?php

use BlueSpice\Privacy\ModuleRegistry;
use BlueSpice\Privacy\Util\PrivacyPageProvider;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;

return [
	'BlueSpicePrivacy.ModuleRegistry' => static function ( MediaWikiServices $services ) {
		$attribute = ExtensionRegistry::getInstance()->getAttribute( 'BlueSpicePrivacyModules' );
		return new ModuleRegistry( $attribute, $services->getObjectFactory(), RequestContext::getMain()->getUser() );
	},
	'BlueSpicePrivacy.PrivacyPageProvider' => static function ( MediaWikiServices $services ) {
		return new PrivacyPageProvider(
			bsConfig: $services->getConfigFactory()->makeConfig( 'bsg' ),
			titleFactory: $services->getTitleFactory(),
			hookContainer: $services->getHookContainer()
		);
	}
];

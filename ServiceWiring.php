<?php

use BlueSpice\Privacy\ModuleRegistry;
use MediaWiki\MediaWikiServices;

return [
	'BlueSpicePrivacy.ModuleRegistry' => static function ( MediaWikiServices $services ) {
		$attribute = ExtensionRegistry::getInstance()->getAttribute( 'BlueSpicePrivacyModules' );
		return new ModuleRegistry( $attribute, $services->getObjectFactory(), RequestContext::getMain()->getUser() );
	},
];

<?php

namespace BlueSpice\Privacy\Api;

use MediaWiki\MediaWikiServices;

class GetRequests extends \BSApiExtJSStoreBase {

	/**
	 *
	 * @param string $query
	 * @return \stdClass[]
	 */
	protected function makeData( $query = '' ) {
		$moduleRegistry = MediaWikiServices::getInstance()->getService( 'BlueSpicePrivacy.ModuleRegistry' );
		$data = [];
		foreach ( $moduleRegistry->getAllModules() as $module ) {
			if ( $module->isRequestable() ) {
				$status = $module->call( 'getRequests', [] );
				if ( $status->isOk() === false ) {
					continue;
				}
				$data = array_merge( $data, $status->getValue() );
			}
		}

		foreach ( $data as &$request ) {
			$request = (object)$request;
		}

		return $data;
	}
}

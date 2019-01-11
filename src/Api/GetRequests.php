<?php

namespace BlueSpice\Privacy\Api;

use BlueSpice\Privacy\ModuleRegistry;

class GetRequests extends \BSApiExtJSStoreBase {

	protected function makeData( $query = '' ) {
		$moduleRegistry = new ModuleRegistry();
		$data = [];
		foreach ( $moduleRegistry->getAllModules() as  $key => $moduleConfig ) {
			$moduleClass = $moduleConfig['class'];
			$module = new $moduleClass( $this->getContext() );
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

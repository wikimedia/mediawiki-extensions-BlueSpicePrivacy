<?php

namespace BlueSpice\Privacy\Api;

use BlueSpice\Privacy\ModuleRegistry;
use MediaWiki\MediaWikiServices;

class GetAllConsents extends \BSApiExtJSStoreBase {

	protected function makeData( $query = '' ) {
		$moduleRegistry = new ModuleRegistry();
		$moduleConfig = $moduleRegistry->getModuleByKey( 'consent' );
		$module = new $moduleConfig['class']( $this->getContext() );

		$db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$res = $db->select(
			'user',
			'user_id'
		);

		$data = [];
		foreach ( $res as $row ) {
			$user = \User::newFromId( $row->user_id );
			$record = [
				'id' => $user->getId(),
				'userName' => $user->getName()
			];
			foreach ( $module->getOptions() as $name => $prefName ) {
				$record[$name] = $user->getOption( $prefName );
			}

			$data[] = (object)$record;
		}

		return $data;
	}
}

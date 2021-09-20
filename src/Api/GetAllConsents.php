<?php

namespace BlueSpice\Privacy\Api;

use BlueSpice\Privacy\ModuleRegistry;

class GetAllConsents extends \BSApiExtJSStoreBase {

	/**
	 *
	 * @param string $query
	 * @return \stdClass[]
	 */
	protected function makeData( $query = '' ) {
		$moduleRegistry = new ModuleRegistry();
		$moduleConfig = $moduleRegistry->getModuleByKey( 'consent' );
		$module = new $moduleConfig['class']( $this->getContext() );

		$db = $this->getServices()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$res = $db->select(
			'user',
			'user_id',
			'',
			__METHOD__
		);

		$data = [];
		foreach ( $res as $row ) {
			$user = \User::newFromId( $row->user_id );

			$block = $user->getBlock( true );
			if ( $block && $block->appliesToRight( 'read' ) ) {
				continue;
			}

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

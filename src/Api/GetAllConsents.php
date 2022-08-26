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

		$services = $this->getServices();
		$db = $services->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$res = $db->select(
			'user',
			'user_id',
			'',
			__METHOD__
		);

		$data = [];
		$userFactory = $services->getUserFactory();
		$userOptionsLookup = $services->getUserOptionsLookup();
		foreach ( $res as $row ) {
			$user = $userFactory->newFromId( $row->user_id );

			$block = $user->getBlock( true );
			if ( $block && $block->appliesToRight( 'read' ) ) {
				continue;
			}

			$record = [
				'id' => $user->getId(),
				'userName' => $user->getName()
			];
			foreach ( $module->getOptions() as $name => $prefName ) {
				$record[$name] = $userOptionsLookup()->getOption( $user, $prefName );
			}

			$data[] = (object)$record;
		}

		return $data;
	}

}

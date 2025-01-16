<?php

namespace BlueSpice\Privacy;

use MediaWiki\User\UserIdentity;
use Wikimedia\ObjectFactory\ObjectFactory;

class ModuleRegistry {

	/**
	 * @var array
	 */
	private $modules;

	/**
	 * @var array|null
	 */
	private $instances = null;

	/**
	 * @var ObjectFactory
	 */
	private $objectFactory;

	/**
	 * @var UserIdentity
	 */
	private $activeUser;

	/**
	 * @param array $modules
	 * @param ObjectFactory $objectFactory
	 * @param UserIdentity $activeUser
	 */
	public function __construct( array $modules, ObjectFactory $objectFactory, UserIdentity $activeUser ) {
		$this->modules = $modules;
		$this->objectFactory = $objectFactory;
		$this->activeUser = $activeUser;
	}

	/**
	 *
	 * @return array
	 */
	public function getAllModules() {
		$this->assertLoaded();
		return $this->instances;
	}

	/**
	 * @param string $name
	 * @param IModule $module
	 * @return void
	 */
	public function register( string $name, IModule $module ) {
		$this->assertLoaded();
		$this->instances[$name] = $module;
		$this->instances[$name]->setUser( $this->activeUser );
	}

	/**
	 *
	 * @param string $key
	 * @return IModule|null
	 */
	public function getModuleByKey( string $key ): ?IModule {
		$this->assertLoaded();
		if ( !isset( $this->instances[$key] ) ) {
			return null;
		}
		return $this->instances[$key];
	}

	/**
	 * @return void
	 */
	private function assertLoaded() {
		if ( $this->instances === null ) {
			foreach ( $this->modules as $name => $module ) {
				$this->instances[$name] = $this->objectFactory->createObject( $module );
				$this->instances[$name]->setUser( $this->activeUser );
			}
		}
	}
}

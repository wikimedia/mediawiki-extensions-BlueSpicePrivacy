<?php

namespace BlueSpice\Privacy;

use MediaWiki\User\UserIdentity;

interface IModule {

	/**
	 * @param string $func
	 * @param array $data
	 * @return \Status
	 */
	public function call( $func, $data );

	/**
	 * @param string $action
	 * @param array $data
	 * @return void
	 */
	public function runHandlers( $action, $data );

	/**
	 * Get the name of the module
	 *
	 * @return string
	 */
	public function getModuleName();

	/**
	 * Does module support request workflow
	 *
	 * @return bool
	 */
	public function isRequestable();

	/**
	 * Get RL modules required to run this module
	 * @param string $type
	 * @return string|null
	 */
	public function getRLModule( $type );

	/**
	 * Class name or array of configs
	 *
	 * @param string $type
	 * @return string|array|null
	 */
	public function getUIWidget( $type );

	/**
	 * @param UserIdentity $user
	 * @return void
	 */
	public function setUser( UserIdentity $user );
}

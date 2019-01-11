<?php

namespace BlueSpice\Privacy;

interface IModule {
	/**
	 * IModule constructor.
	 * @param \IContextSource $context
	 */
	public function __construct( $context );

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
	 * @return boolean
	 */
	public function isRequestable();
}

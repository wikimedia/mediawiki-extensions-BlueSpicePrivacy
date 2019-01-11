<?php

namespace BlueSpice\Privacy\Auth;

class PrefSaver {
	/**
	 *
	 * @var PrefSaver
	 */
	protected static $instance;

	/**
	 *
	 * @var array
	 */
	protected $data;

	/**
	 *
	 * @return PrefSaver
	 */
	public static function getInstance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 *
	 * @param array $data
	 */
	public function setData( $data ) {
		$this->data = $data;
	}

	/**
	 *
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}
}

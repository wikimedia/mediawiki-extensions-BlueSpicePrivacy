<?php

namespace BlueSpice\Privacy;

use BlueSpice\IAdminTool;

class AdminTool implements IAdminTool {

	/**
	 *
	 * @return string
	 */
	public function getURL() {
		$tool = \SpecialPage::getTitleFor( 'PrivacyAdmin' );
		return $tool->getLocalURL();
	}

	/**
	 * @return \Message
	 */
	public function getDescription() {
		return wfMessage( 'bs-privacy-desc' );
	}

	/**
	 * @return \Message
	 */
	public function getName() {
		return wfMessage( 'bs-privacy-admintool-label' );
	}

	/**
	 * @return array
	 */
	public function getClasses() {
		$classes = [
			'icon-eye'
		];

		return $classes;
	}

	/**
	 * @return array
	 */
	public function getDataAttributes() {
		return [];
	}

	/**
	 * @return array
	 */
	public function getPermissions() {
		$permissions = [
			'bs-privacy-admin'
		];
		return $permissions;
	}

}

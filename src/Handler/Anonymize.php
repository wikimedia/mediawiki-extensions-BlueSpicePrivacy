<?php

namespace BlueSpice\Privacy\Handler;

use BlueSpice\Privacy\IPrivacyHandler;

class Anonymize implements IPrivacyHandler {
	/**
	 *
	 * @var array
	 */
	protected $tableMap = [
		'user' => 'user_name',
		'revision' => 'rev_user_text',
		'archive' => 'ar_user_text',
		'ipblocks' => 'ipb_by_text',
		'image' => 'img_user_text',
		'oldimage' => 'oi_user_text',
		'filearchive' => 'fa_user_text',
		'recentchanges' => 'rc_user_text',
		'logging' => 'log_user_text',
		'filearchive' => 'fa_user_text'
	];

	/**
	 * @var \Database
	 */
	protected $db;

	/**
	 * @var bool
	 */
	protected $skipUserTable = false;

	/**
	 * @var \User
	 */
	protected $oldUser;

	/**
	 *
	 * @param \Database $db
	 */
	public function __construct( \Database $db ) {
		$this->db = $db;
	}

	/**
	 *
	 * @param string $oldUsername
	 * @param string $newUsername
	 * @return \Status
	 */
	public function anonymize( $oldUsername, $newUsername ) {
		$this->oldUser = \User::newFromName( $oldUsername );

		$this->updateTables( $newUsername );
		$this->moveUserPage( $newUsername );

		return \Status::newGood();
	}

	protected function updateTables( $newUsername ) {
		// Clear realname
		$this->db->update(
			'user',
			[ 'user_real_name' => '' ],
			[ 'user_name' => $this->oldUser->getName() ]
		);

		foreach ( $this->tableMap as $table => $field ) {
			if ( $table === 'user' && $this->skipUserTable ) {
				continue;
			}
			$this->db->update(
				$table,
				[ $field => $newUsername ],
				[ $field => $this->oldUser->getName() ]
			);
		}
	}

	protected function moveUserPage( $newUsername ) {
		$oldUserPage = $this->oldUser->getUserPage();
		$newUserPage = \Title::makeTitle( NS_USER, $newUsername );
		if ( $oldUserPage->exists() ) {
			$movePage = new \MovePage( $oldUserPage, $newUserPage );
			$movePage->move(
				$this->user,
				'',
				false
			);

			// Delete traces from old to new userpage
			$this->db->delete(
				'logging',
				[
					'log_action' => 'move',
					'log_user' => $this->oldUser->getId(),
					'log_namespace' => NS_USER,
					'log_title' => $oldUserPage->getDBkey()
				]
			);
		}
	}

	/**
	 *
	 * @param \User $userToDelete
	 * @param \User $deletedUser
	 * @return \Status
	 */
	public function delete( \User $userToDelete, \User $deletedUser ) {
		// Handled in another handler
		return \Status::newGood();
	}

	/**
	 *
	 * @param array $types
	 * @param string $format
	 * @param \User $user
	 * @return \Status
	 */
	public function exportData( array $types, $format, \User $user ) {
		// Handled in another handler
		return \Status::newGood( [] );
	}
}

<?php

namespace BlueSpice\Privacy\Handler;

use BlueSpice\Privacy\IPrivacyHandler;

class Delete extends Anonymize implements IPrivacyHandler {
	/**
	 * @var \User
	 */
	protected $userToDelete;

	/**
	 * User that all data from the user we are deleting
	 * will be moved to
	 *
	 * @var \User
	 */
	protected $groupingDeletedUser;

	/**
	 * When anonymizing for deletion,
	 * we must not anonymize user table
	 *
	 * @var bool
	 */
	protected $skipUserTable = true;

	/**
	 *
	 * @var array
	 */
	protected $moveToDeletedTables = [
		'archive' => 'ar_user',
		'filearchive' => 'fa_user',
		'image' => 'img_user',
		'logging' => 'log_user',
		'oldimage' => 'oi_user',
		'page_restrictions' => 'pr_user',
		'protected_titles' => 'pt_user',
		'recentchanges' => 'rc_user',
		'revision' => 'rev_user',
		'user_newtalk' => 'user_id'
	];

	/**
	 *
	 * @var array
	 */
	protected $deleteTables = [
		'user' => 'user_id',
		'user_groups' => 'ug_user',
		'user_properties' => 'up_user',
		'user_former_groups' => 'ufg_user'
	];

	/**
	 *
	 * @param string $oldUsername
	 * @param string $newUsername
	 * @return \Status
	 */
	public function anonymize( $oldUsername, $newUsername ) {
		// Not handled here
		return \Status::newGood();
	}

	/**
	 * @param \User $userToDelete
	 * @param \User $deletedUser
	 * @return \Status
	 */
	public function delete( \User $userToDelete, \User $deletedUser ) {
		$this->userToDelete = $userToDelete;
		$this->groupingDeletedUser = $deletedUser;

		// First anonymize to deleted user
		$anonymizeStatus = parent::anonymize( $userToDelete->getName(), $deletedUser->getName() );
		if ( !$anonymizeStatus->isOK() ) {
			return \Status::newFatal( 'bs-privacy-deletion-failed' );
		}

		$this->removeUserPage();
		$this->moveToDeletedUser();
		$this->deleteFromTables();

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
		return \Status::newGood( [] );
	}

	protected function removeUserPage() {
		$userpage = $this->userToDelete->getUserPage();
		if ( $userpage instanceof \Title && $userpage->exists() ) {
			$article = new \Article( $userpage );
			$article->doDelete( '', true );
		}
	}

	protected function moveToDeletedUser() {
		foreach ( $this->moveToDeletedTables as $table => $field ) {
			$this->db->update(
				$table,
				[ $field => $this->groupingDeletedUser->getId() ],
				[ $field => $this->userToDelete->getId() ]
			);
		}
	}

	protected function deleteFromTables() {
		foreach ( $this->deleteTables as $table => $field ) {
			$this->db->delete(
				$table,
				[ $field => $this->userToDelete->getId() ]
			);
		}
	}
}

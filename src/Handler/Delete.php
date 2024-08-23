<?php

namespace BlueSpice\Privacy\Handler;

use BlueSpice\Privacy\IPrivacyHandler;
use RequestContext;
use Wikimedia\Rdbms\IDatabase;

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
	protected $moveToDeletedActorTables = [
		'archive' => 'ar_actor',
		'filearchive' => 'fa_actor',
		'image' => 'img_actor',
		'logging' => 'log_actor',
		'oldimage' => 'oi_actor',
		'recentchanges' => 'rc_actor',
		'revision' => 'rev_actor'
	];

	/**
	 *
	 * @var array
	 */
	protected $moveToDeletedTables = [
		'protected_titles' => 'pt_user',
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
		'user_former_groups' => 'ufg_user',
		'actor' => 'actor_user',
	];

	/**
	 * We need to implement/call this explicitly as we also have a `delete` method that may be
	 * mistaken as constructor
	 * @param IDatabase $db
	 */
	public function __construct( IDatabase $db ) {
		parent::__construct( $db );
	}

	/**
	 *
	 * @param \User $userToDelete
	 * @param \User $deletedUser
	 * @return \Status
	 */
	public function delete( \User $userToDelete, \User $deletedUser ) {
		$this->userToDelete = $userToDelete;
		$this->groupingDeletedUser = $deletedUser;
		// First anonymize to deleted user
		$anonymizeStatus = $this->anonymize(
			$userToDelete->getName(),
			$deletedUser->getName()
		);
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
			$wikiPage = $this->services->getWikiPageFactory()->newFromTitle( $userpage );
			$deletePage = $this->services->getDeletePageFactory()
				->newDeletePage( $wikiPage, RequestContext::getMain()->getUser() );
			$deletePage->setSuppress( true )->deleteIfAllowed( '' );
		}
	}

	protected function moveToDeletedUser() {
		foreach ( $this->moveToDeletedTables as $table => $field ) {
			$this->db->update(
				$table,
				[ $field => $this->groupingDeletedUser->getId() ],
				[ $field => $this->userToDelete->getId() ],
				__METHOD__
			);
		}
		foreach ( $this->moveToDeletedActorTables as $table => $field ) {
			$this->db->update(
				$table,
				[ $field => $this->groupingDeletedUser->getActorId() ],
				[ $field => $this->userToDelete->getActorId() ],
				__METHOD__
			);
		}
	}

	protected function deleteFromTables() {
		foreach ( $this->deleteTables as $table => $field ) {
			$this->db->delete(
				$table,
				[ $field => $this->userToDelete->getId() ],
				__METHOD__
			);
		}
	}
}

<?php

namespace BlueSpice\Privacy;

use MediaWiki\Status\Status;
use MediaWiki\User\User;

interface IPrivacyHandler {

	/**
	 * @param string $oldUsername
	 * @param string $newUsername
	 * @return Status
	 */
	public function anonymize( $oldUsername, $newUsername );

	/**
	 * @param User $userToDelete
	 * @param User $deletedUser
	 * @return Status
	 */
	public function delete( User $userToDelete, User $deletedUser );

	/**
	 * @param array $types Types of info users wants to retrieve
	 * @param string $format Requested output format
	 * @param User $user User to export data from
	 * @return Status
	 */
	public function exportData( array $types, $format, User $user );
}

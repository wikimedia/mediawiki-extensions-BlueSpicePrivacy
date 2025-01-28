<?php

namespace BlueSpice\Privacy\Handler;

use BlueSpice\Privacy\IPrivacyHandler;
use BlueSpice\Privacy\Module\Transparency;
use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Status\Status;
use MediaWiki\User\User;
use Wikimedia\Rdbms\IDatabase;

class ExportData implements IPrivacyHandler {
	/**
	 * @var array
	 */
	protected $data = [];

	/**
	 * @var User
	 */
	protected $user;

	/**
	 * @var string
	 */
	protected $format;

	/**
	 * @var IDatabase
	 */
	protected $db;

	/**
	 * @var IContextSource
	 */
	protected $context;

	/**
	 *
	 * @param IDatabase $db
	 */
	public function __construct( IDatabase $db ) {
		$this->db = $db;
		$this->context = RequestContext::getMain();
	}

	/**
	 * @param string $oldUsername
	 * @param string $newUsername
	 * @return Status
	 */
	public function anonymize( $oldUsername, $newUsername ) {
		return Status::newGood();
	}

	/**
	 *
	 * @param User $userToDelete
	 * @param User $deletedUser
	 * @return Status
	 */
	public function delete( User $userToDelete, User $deletedUser ) {
		return Status::newGood();
	}

	/**
	 *
	 * @param array $types
	 * @param string $format
	 * @param User $user
	 * @return Status
	 */
	public function exportData( array $types, $format, User $user ) {
		$this->user = $user;
		$this->format = $format;

		if ( in_array( Transparency::DATA_TYPE_PERSONAL, $types ) ) {
			$this->getPersonalInfo();
		}
		if ( in_array( Transparency::DATA_TYPE_WORKING, $types ) ) {
			$this->getWorkingData();
		}
		if ( in_array( Transparency::DATA_TYPE_ACTIONS, $types ) ) {
			$this->getActionsData();
		}
		if ( in_array( Transparency::DATA_TYPE_CONTENT, $types ) ) {
			$this->getContentData();
		}

		return Status::newGood( $this->data );
	}

	/**
	 *
	 */
	protected function getPersonalInfo() {
		$data = [];

		$data[] = wfMessage(
			'bs-privacy-transparency-private-username',
			$this->user->getName()
		)->plain();
		$realname = $this->user->getRealName();
		$data[] = wfMessage( 'bs-privacy-transparency-private-realname', $realname )->plain();
		$registration = $this->user->getRegistration();
		if ( $registration ) {
			$registrationTS = $this->context->getLanguage()->userTimeAndDate(
				$registration,
				$this->user
			);
			$data[] = wfMessage(
				'bs-privacy-transparency-private-registration',
				$registrationTS
			)->plain();
		}
		$block = $this->user->getBlock();
		if ( $block === null ) {
			$data[] = wfMessage( 'bs-privacy-transparency-private-not-blocked' )->plain();
		} else {
			$data[] = wfMessage(
				'bs-privacy-transparency-private-blocked',
				$block->getByName()
			)->plain();
		}
		$email = $this->user->getEmail();
		$emailAuthentication = $this->user->getEmailAuthenticationTimestamp();
		if ( $emailAuthentication ) {
			$data[] = wfMessage(
				'bs-privacy-transparency-private-email-authenticated',
				$email,
				$this->context->getLanguage()->userTimeAndDate(
					$emailAuthentication,
					$this->user
				)
			)->plain();
		} else {
			$data[] = wfMessage(
				'bs-privacy-transparency-private-email-not-authenticated',
				$email )->plain();
		}
		$data[] = wfMessage(
			'bs-privacy-transparency-private-edit-count',
			$this->user->getEditCount()
		)->plain();
		$data[] = wfMessage(
			'bs-privacy-transparency-private-experience',
			$this->user->getExperienceLevel()
		)->plain();

		$groups = MediaWikiServices::getInstance()
			->getUserGroupManager()
			->getUserGroups( $this->user );
		$data[] = wfMessage(
			'bs-privacy-transparency-private-groups',
			implode( ', ', $groups )
		)->plain();

		$formerGroups = MediaWikiServices::getInstance()
			->getUserGroupManager()
			->getUserFormerGroups( $this->user );
		$data[] = wfMessage(
			'bs-privacy-transparency-private-former-groups',
			implode( ', ', $formerGroups )
		)->plain();
		$rights = MediaWikiServices::getInstance()->getPermissionManager()
			->getUserPermissions( $this->user );
		$data[] = wfMessage(
			'bs-privacy-transparency-private-rights',
			implode( ', ', $rights )
		)->plain();
		$data[] = wfMessage(
			'bs-privacy-transparency-private-user-page-url',
			$this->user->getUserPage()->getFullURL()
		)->plain();

		$this->data[Transparency::DATA_TYPE_PERSONAL] = $data;
	}

	/**
	 *
	 */
	protected function getWorkingData() {
		$this->data[Transparency::DATA_TYPE_WORKING] = [];
	}

	/**
	 *
	 */
	protected function getContentData() {
		$this->data[Transparency::DATA_TYPE_CONTENT] = [];
	}

	/**
	 *
	 */
	protected function getActionsData() {
		$data = [];
		$logRows = $this->getLogRows();
		foreach ( $logRows as $logRow ) {
			$formatter = \LogFormatter::newFromRow( $logRow );
			$timestamp = $logRow->log_timestamp;
			$formattedTS = wfMessage(
				'bs-privacy-transparency-action-timestamp',
					$this->context->getLanguage()->userTimeAndDate(
						$timestamp,
						$this->user
					)
				)->plain();

			if ( $this->format === Transparency::DATA_FORMAT_HTML ) {
				$html = Html::openElement( 'span' );
				$html .= $formattedTS;
				$html .= $formatter->getActionText();
				$html .= Html::closeElement( 'span' );

				$data[] = $html;
			} else {
				$plainLogText = $formatter->getPlainActionText();
				// Get rid of internal link syntax for plain text output
				$plainLogText = preg_replace( '/\[\[|\]\]/', '', $plainLogText );
				$data[] = $formattedTS . $plainLogText;
			}
		}
		$this->data[Transparency::DATA_TYPE_ACTIONS] = $data;
	}

	/**
	 *
	 * @return \Wikimedia\Rdbms\ResultWrapper|bool
	 */
	protected function getLogRows() {
		$res = $this->db->select(
			'logging',
			[ '*' ],
			[ 'log_actor' => $this->user->getId() ],
			__METHOD__,
			[ 'ORDER BY' => 'log_timestamp DESC' ]
		);

		return $res;
	}

}

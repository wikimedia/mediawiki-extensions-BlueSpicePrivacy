<?php
namespace BlueSpice\Privacy\Special;

use MediaWiki\MediaWikiServices;

class PrivacyPages extends \BlueSpice\SpecialPage {

	public function __construct() {
		parent::__construct( 'PrivacyPages', '', false );
	}

	/**
	 * @param string|null $subPage - can be either 'TermsOfServices' or 'PrivacyPolicy'
	 */
	public function execute( $subPage ) {
		if ( $subPage === 'TermsOfServices' ) {
			$configName = 'PrivacyTermsOfServiceLink';
			$localPageName = $this->msg( 'bs-privacy-termsofservicepage' )->inContentLanguage()->plain();
		} elseif ( $subPage === 'PrivacyPolicy' ) {
			$configName = 'PrivacyPrivacyPolicyLink';
			$localPageName = $this->msg( 'bs-privacy-privacypage' )->inContentLanguage()->plain();
		} else {
			$this->getOutput()->showErrorPage(
				'error',
				'bs-privacy-special-privacy-pages-invalid-subpage',
				[ 'TermsOfServices, PrivacyPolicy' ]
			);
			return;
		}

		$targetUrl = $this->getConfig()->get( $configName );
		if ( empty( $targetUrl ) ) {
			$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();
			$targetUrl = $titleFactory->newFromText( $localPageName )->getFullURL();
		}

		$this->getOutput()->redirect( $targetUrl );
	}
}

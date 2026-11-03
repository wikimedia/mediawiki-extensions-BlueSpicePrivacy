<?php
namespace BlueSpice\Privacy\Special;

use BlueSpice\Privacy\Util\PrivacyPageProvider;
use MediaWiki\SpecialPage\SpecialPage;
use OOUI\MessageWidget;

class PrivacyPages extends SpecialPage {

	public function __construct(
		private readonly PrivacyPageProvider $provider
	) {
		parent::__construct( 'PrivacyPages' );
	}

	/** @inheritDoc */
	public function isListed() {
		return false;
	}

	/**
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		if ( !in_array( $subPage, $this->provider->getNames(), true ) ) {
			$this->error();
			return;
		}
		$title = $this->provider->getPrivacyTitle( $subPage );
		$url = $this->provider->getPageUrl( $subPage );
		if ( !$title && $url ) {
			// Manual configuration
			$this->getOutput()->redirect( $url );
		} elseif ( $title ) {
			if ( $title->isSpecialPage() || $title->getInterwiki() ) {
				$this->getOutput()->redirect( $url );
			}
			$this->getOutput()->setPageTitle( $title->getText() );
			if ( $title->exists() ) {
				$pageText = $title->getPrefixedText();
				if ( $title->getNamespace() === NS_MAIN ) {
					$pageText = ':' . $pageText;
				}
				// Include local page
				$this->getOutput()->addWikiTextAsContent( "{{{$pageText}}}" );
			} else {
				$this->getOutput()->enableOOUI();
				$this->getOutput()->addHTML(
					new MessageWidget( [
						'type' => 'warning',
						'label' => $this->msg( 'bs-privacy-special-privacy-pages-missing-page' )->text()
					] )
				);
			}
		} else {
			$this->error();
		}
	}

	private function error() {
		$this->getOutput()->showErrorPage(
			'error',
			'bs-privacy-special-privacy-pages-invalid-subpage',
			array_values( $this->provider->getNames() )
		);
	}
}

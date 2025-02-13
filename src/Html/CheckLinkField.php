<?php

namespace BlueSpice\Privacy\Html;

use MediaWiki\HTMLForm\Field\HTMLCheckField;

class CheckLinkField extends HTMLCheckField {
	/**
	 * @return string
	 */
	public function getLabel() {
		return html_entity_decode( parent::getLabel() );
	}
}

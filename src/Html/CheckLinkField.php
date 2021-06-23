<?php

namespace BlueSpice\Privacy\Html;

class CheckLinkField extends \HTMLCheckField {
	/**
	 * @return string
	 */
	public function getLabel() {
		return html_entity_decode( parent::getLabel() );
	}
}

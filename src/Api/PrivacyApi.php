<?php

namespace BlueSpice\Privacy\Api;

use ApiBase;
use BlueSpice\Privacy\ModuleRegistry;
use FormatJson;
use MediaWiki\MediaWikiServices;
use MediaWiki\Status\Status;
use Wikimedia\ParamValidator\ParamValidator;

class PrivacyApi extends ApiBase {
	/** @var Status */
	protected $status;

	public function execute() {
		$this->dispatch();
		$this->returnResults();
	}

	/**
	 *
	 * @return array
	 */
	protected function getAllowedParams() {
		return [
			'module' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'func' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'data' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			]
		];
	}

	/**
	 * Using the settings determine the value for the given parameter
	 *
	 * @param string $name
	 * @param string $settings
	 * @param bool $parseLimit Whether to parse and validate 'limit' parameters
	 * @return mixed Parameter value
	 */
	protected function getParameterFromSettings( $name, $settings, $parseLimit ) {
		$value = parent::getParameterFromSettings( $name, $settings, $parseLimit );
		if ( $name === 'data' ) {
			if ( !$value ) {
				$value = [];
			} else {
				$decodedValue = FormatJson::decode( $value, true );
				$value = $decodedValue;
				if ( !is_array( $value ) ) {
					$value = [ $value ];
				}
			}
		}
		return $value;
	}

	protected function dispatch() {
		$module = $this->getParameter( 'module' );
		$function = $this->getParameter( 'func' );
		$data = $this->getParameter( 'data' );

		/** @var ModuleRegistry $moduleRegistry */
		$moduleRegistry = MediaWikiServices::getInstance()->getService( 'BlueSpicePrivacy.ModuleRegistry' );
		$module = $moduleRegistry->getModuleByKey( $module );
		if ( !$module ) {
			$this->status = \Status::newFatal(
				wfMessage( "bs-privacy-api-error-missing-module", $module )
			);
			return;
		}
		$this->status = $module->call( $function, $data );
	}

	protected function returnResults() {
		$result = $this->getResult();

		if ( $this->status->isOk() ) {
			$result->addValue( null, 'success', 1 );
			$result->addValue( null, 'data', $this->status->getValue() );
		} else {
			$result->addValue( null, 'success', 0 );
			$result->addValue( null, 'error', $this->status->getMessage() );
		}
	}
}

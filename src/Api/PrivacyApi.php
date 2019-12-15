<?php

namespace BlueSpice\Privacy\Api;

use BlueSpice\Privacy\ModuleRegistry;

class PrivacyApi extends \ApiBase {
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
				\ApiBase::PARAM_TYPE => 'string',
				\ApiBase::PARAM_REQUIRED => true
			],
			'func' => [
				\ApiBase::PARAM_TYPE => 'string',
				\ApiBase::PARAM_REQUIRED => true
			],
			'data' => [
				\ApiBase::PARAM_TYPE => 'string',
				\ApiBase::PARAM_REQUIRED => false
			]
		];
	}

	/**
	 * Using the settings determine the value for the given parameter
	 *
	 * @param string $paramName Parameter name
	 * @param array|mixed $paramSettings Default value or an array of settings
	 *  using PARAM_* constants.
	 * @param bool $parseLimit Whether to parse and validate 'limit' parameters
	 * @return mixed Parameter value
	 */
	protected function getParameterFromSettings( $paramName, $paramSettings, $parseLimit ) {
		$value = parent::getParameterFromSettings( $paramName, $paramSettings, $parseLimit );
		if ( $paramName === 'data' ) {
			if ( !$value ) {
				$value = [];
			} else {
				$decodedValue = \FormatJson::decode( $value, true );
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

		$moduleRegistry = new ModuleRegistry();
		$moduleClass = $moduleRegistry->getModuleClass( $module );
		if ( !class_exists( $moduleClass ) ) {
			$this->status = \Status::newFatal(
				wfMessage( "bs-privacy-api-error-missing-module", $module )
			);
			return;
		}

		$moduleObject = new $moduleClass( $this->getContext() );
		$this->status = $moduleObject->call( $function, $data );
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

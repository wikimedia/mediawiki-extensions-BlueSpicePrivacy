<?php

namespace BlueSpice\Privacy\Module;

use BlueSpice\Privacy\Module;

class Transparency extends Module {
	const DATA_TYPE_PERSONAL = 'personal';
	const DATA_TYPE_WORKING = 'working';
	const DATA_TYPE_ACTIONS = 'actions';
	const DATA_TYPE_CONTENT = 'content';

	const DATA_FORMAT_RAW = 'raw';
	const DATA_FORMAT_HTML = 'html';
	const DATA_FORMAT_CSV = 'csv';

	/**
	 *
	 * @param string $func
	 * @param array $data
	 * @return \Status
	 */
	public function call( $func, $data ) {
		if ( !$this->verifyUser() ) {
			\Status::newFatal( wfMessage( 'bs-privacy-invalid-user' ) );
		}

		switch ( $func ) {
			case "getData":
				if ( !isset( $data['types'] ) ) {
					$types = $this->allDataTypes();
				} elseif ( $this->verifyDataTypes( $data['types'] ) ) {
					$types = $data['types'];
				} else {
					return \Status::newFatal( wfMessage( 'bs-privacy-invalid-param', "types" ) );
				}

				if ( !isset( $data['export_format'] ) ) {
					$format = static::DATA_FORMAT_RAW;
				} elseif ( $this->verifyExportFormat( $data['export_format'] ) ) {
					$format = $data['export_format'];
				} else {
					return \Status::newFatal( wfMessage( 'bs-privacy-invalid-param', "format" ) );
				}

				return $this->getData( $types, $format );
				break;
			default:
				return \Status::newFatal( wfMessage( 'bs-privacy-module-no-function', $func ) );
		}
	}

	/**
	 *
	 * @param string $action
	 * @param array $data
	 * @return \Status
	 */
	public function runHandlers( $action, $data ) {
		$status = \Status::newGood();
		$db = wfGetDB( DB_MASTER );

		$exportData = [];
		foreach ( $this->getHandlers() as $handler ) {
			if ( class_exists( $handler ) ) {
				$handlerObject = new $handler( $db );
				$result = call_user_func_array( [ $handlerObject, $action ], $data );

				if ( $result instanceof \Status && $result->isOk() === false ) {
					$status = $result;
					break;
				}
				if ( !$result ) {
					// An error occurred
					$status = \Status::newFatal( wfMessage( 'bs-privacy-handler-error', $handler ) );
					break;
				}

				$exportData = array_merge_recursive( $exportData, $result->getValue() );
			}
		}

		if ( $status->isOK() ) {
			$this->logAction();
			return \Status::newGood( $exportData );
		}
		return $status;
	}

	protected function getData( $types, $format ) {
		$status = $this->runHandlers( 'exportData', [
			$types,
			$format,
			$this->context->getUser()
		] );

		if ( !$status->isOK() ) {
			return $status;
		}

		if ( $format === static::DATA_FORMAT_RAW ) {
			return $status;
		}

		$data = $status->getValue();

		if ( $format === static::DATA_FORMAT_HTML ) {
			return $this->getHTML( $data );
		} else {
			return $this->getCSV( $data );
		}
	}

	/**
	 *
	 * @return string
	 */
	public function getModuleName() {
		return 'transparency';
	}

	protected function allDataTypes() {
		return [
			static::DATA_TYPE_PERSONAL,
			static::DATA_TYPE_WORKING,
			static::DATA_TYPE_ACTIONS,
			static::DATA_TYPE_CONTENT
		];
	}

	/**
	 * Makes sure all passed types are valid
	 *
	 * @param array $types
	 * @return bool
	 */
	protected function verifyDataTypes( $types ) {
		foreach ( $types as $type ) {
			if ( in_array( $type, $this->allDataTypes() ) === false ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Makes sure export format is valid
	 *
	 * @param string $exportFormat
	 * @return bool
	 */
	protected function verifyExportFormat( $exportFormat ) {
		$allExportFormats = [
			static::DATA_FORMAT_RAW,
			static::DATA_FORMAT_HTML,
			static::DATA_FORMAT_CSV
		];

		if ( in_array( $exportFormat, $allExportFormats ) ) {
			return true;
		}
		return false;
	}

	protected function getHTML( $data ) {
		$formattedDate = $this->context->getLanguage()->userTimeAndDate(
			wfTimestamp(),
			$this->context->getUser()
		);
		$args = [
			'title' => wfMessage( 'bs-privacy-transparency-html-export-title', $formattedDate )->plain(),
			'groups' => []
		];

		foreach ( $data as $section => $items ) {
			$args['groups'][] = [
				'name' => wfMessage( 'bs-privacy-transparency-type-title-' . $section )->plain(),
				'items' => $items
			];
		}

		$templateParser = new \TemplateParser( dirname( dirname( __DIR__ ) ) . '/resources/templates' );
		$html = $templateParser->processTemplate(
			'DataExport',
			$args
		);

		$username = $this->context->getUser()->getName();
		$filename = $username . "_" . wfTimestamp( TS_MW ) . ".html";

		return \Status::newGood( [
			'contents' => $html,
			'filename' => $filename,
			'format' => static::DATA_FORMAT_HTML
		] );
	}

	protected function getCSV( $data ) {
		$formattedDate = $this->context->getLanguage()->userTimeAndDate(
			wfTimestamp(),
			$this->context->getUser()
		);

		$csvData = [
			wfMessage( 'bs-privacy-transparency-html-export-title', $formattedDate )->plain()
		];
		foreach ( $data as $section => $items ) {
			$csvData[] = wfMessage( 'bs-privacy-transparency-type-title-' . $section )->plain();
			foreach ( $items as $item ) {
				$csvData[] = "$item";
			}
		}

		$username = $this->context->getUser()->getName();
		$filename = $username . "_" . wfTimestamp( TS_MW ) . ".csv";

		return \Status::newGood( [
			'contents' => implode( "\n", $csvData ),
			'filename' => $filename,
			'format' => static::DATA_FORMAT_CSV
		] );
	}
}

<?php

namespace BlueSpice\Privacy\Module;

use BlueSpice\Privacy\Module;
use MediaWiki\Html\TemplateParser;
use MediaWiki\Language\Language;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Status\Status;
use MWStake\MediaWiki\Component\Events\Notifier;
use Wikimedia\Rdbms\ILoadBalancer;

class Transparency extends Module {
	public const DATA_TYPE_PERSONAL = 'personal';
	public const DATA_TYPE_WORKING = 'working';
	public const DATA_TYPE_ACTIONS = 'actions';
	public const DATA_TYPE_CONTENT = 'content';

	public const DATA_FORMAT_RAW = 'raw';
	public const DATA_FORMAT_HTML = 'html';
	public const DATA_FORMAT_CSV = 'csv';

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @param ILoadBalancer $lb
	 * @param Notifier $notifier
	 * @param PermissionManager $permissionManager
	 * @param Language $language
	 */
	public function __construct(
		ILoadBalancer $lb, Notifier $notifier, PermissionManager $permissionManager, Language $language
	) {
		parent::__construct( $lb, $notifier, $permissionManager );
		$this->language = $language;
	}

	/**
	 *
	 * @param string $func
	 * @param array $data
	 * @return Status
	 */
	public function call( $func, $data ) {
		if ( !$this->verifyUser() ) {
			return Status::newFatal( wfMessage( 'bs-privacy-invalid-user' ) );
		}

		switch ( $func ) {
			case "getData":
				if ( !isset( $data['types'] ) ) {
					$types = $this->allDataTypes();
				} elseif ( $this->verifyDataTypes( $data['types'] ) ) {
					$types = $data['types'];
				} else {
					return Status::newFatal( wfMessage( 'bs-privacy-invalid-param', "types" ) );
				}

				if ( !isset( $data['export_format'] ) ) {
					$format = static::DATA_FORMAT_RAW;
				} elseif ( $this->verifyExportFormat( $data['export_format'] ) ) {
					$format = $data['export_format'];
				} else {
					return Status::newFatal( wfMessage( 'bs-privacy-invalid-param', "format" ) );
				}

				return $this->getData( $types, $format );
			default:
				return Status::newFatal( wfMessage( 'bs-privacy-module-no-function', $func ) );
		}
	}

	/**
	 *
	 * @param string $action
	 * @param array $data
	 * @return Status
	 */
	public function runHandlers( $action, $data ) {
		$status = Status::newGood();
		$db = $this->lb->getConnection( DB_PRIMARY );

		$exportData = [];
		foreach ( $this->getHandlers() as $handler ) {
			if ( class_exists( $handler ) ) {
				$handlerObject = new $handler( $db );
				$result = call_user_func_array( [ $handlerObject, $action ], $data );

				if ( $result instanceof Status && $result->isOk() === false ) {
					$status = $result;
					break;
				}
				if ( !$result ) {
					// An error occurred
					$status = Status::newFatal( wfMessage( 'bs-privacy-handler-error', $handler ) );
					break;
				}

				$handlerData = $result->getValue();
				if ( !is_array( $handlerData ) ) {
					continue;
				}
				$exportData = array_merge_recursive( $exportData, $result->getValue() );
			}
		}

		if ( $status->isOK() ) {
			$this->logAction();
			return Status::newGood( $exportData );
		}
		return $status;
	}

	/**
	 *
	 * @param string $types
	 * @param string $format
	 * @return string
	 */
	protected function getData( $types, $format ) {
		$status = $this->runHandlers( 'exportData', [
			$types,
			$format,
			$this->user
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

	/**
	 *
	 * @param array $data
	 * @return Status
	 */
	protected function getHTML( $data ) {
		$formattedDate = $this->language->userTimeAndDate(
			wfTimestamp(),
			$this->user
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

		$templateParser = new TemplateParser( dirname( dirname( __DIR__ ) ) . '/resources/templates' );
		$html = $templateParser->processTemplate(
			'DataExport',
			$args
		);

		$username = $this->user->getName();
		$filename = $username . "_" . wfTimestamp( TS_MW ) . ".html";

		return Status::newGood( [
			'contents' => $html,
			'filename' => $filename,
			'format' => static::DATA_FORMAT_HTML
		] );
	}

	/**
	 *
	 * @param array $data
	 * @return Status
	 */
	protected function getCSV( $data ) {
		$formattedDate = $this->language->userTimeAndDate(
			wfTimestamp(),
			$this->user
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

		$username = $this->user->getName();
		$filename = $username . "_" . wfTimestamp( TS_MW ) . ".csv";

		return Status::newGood( [
			'contents' => implode( "\n", $csvData ),
			'filename' => $filename,
			'format' => static::DATA_FORMAT_CSV
		] );
	}

	/**
	 * Get RL modules required to run this module
	 * @param string $type
	 * @return string|null
	 */
	public function getRLModule( $type ) {
		if ( $type === static::MODULE_UI_TYPE_USER ) {
			return "ext.bs.privacy.module.transparency.user";
		}
		return null;
	}

	/**
	 * @param string $type
	 * @return string|null
	 */
	public function getUIWidget( $type ) {
		if ( $type === static::MODULE_UI_TYPE_USER ) {
			return "bs.privacy.widget.Transparency";
		}
		return null;
	}
}

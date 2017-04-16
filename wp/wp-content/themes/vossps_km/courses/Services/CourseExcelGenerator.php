<?php

namespace Lumiart\Vosspskm\Courses\Services;

/**
 * Class CourseExcelGenerator
 * @package Lumiart\Vosspskm\Courses\Services
 *
 * Generates PHPExcel object based on field mapping config and array of data
 */
class CourseExcelGenerator {

	/**
	 * @var \PHPExcel
	 */
	private $excel;

	/**
	 * @var string
	 */
	protected $title = 'Sheet 1';

	/**
	 * @var array
	 */
	protected $field_mapping = [];

	/**
	 * @var array
	 */
	protected $data = [];

	/**
	 * @var int
	 */
	protected $column_freeze = 1;

	public function __construct() {

		\PHPExcel_Settings::setLocale( 'cs' );
		$this->excel = new \PHPExcel();

	}

	/**
	 * Sets title of Active Sheet in resulting excel
	 *
	 * @param string $title
	 *
	 * @return $this
	 */
	public function setTitle( $title ) {
		$this->title = $title;
		return $this;
	}

	/**
	 * Sets field mapping config.
	 *
	 * Use array, where keys match attributes in data array. Ex.:
	 * [
	 *      'born_place' => [ 'title' => 'Místo narození' ],
	 *      'born_date' => [ 'title' => 'Datum narození', 'type' => 'date', 'date_format' => 'Ymd' ],
	 * 		'payment_object' => [ 'title' => 'Plátce kurzovného', 'type' => 'select', 'choices' => [ 'self' => 'Samoplátce', 'school' => 'Škola' ] ],
	 *      'note' => [ 'title' => 'Poznámka', 'type' => 'html' ],
	 * ]
	 *
	 * @param array $field_mapping
	 *
	 * @return $this
	 */
	public function setFieldMapping( $field_mapping ) {
		$this->field_mapping = $field_mapping;
		return $this;
	}

	/**
	 * Sets array with data for each row. Data keys have to match those in fieldMapping array.
	 *
	 * @param array $data
	 *
	 * @return $this
	 */
	public function setData( $data ) {
		$this->data = $data;
		return $this;
	}

	/**
	 * Sets Excel column-freeze to specific column. Default is 0.
	 *
	 * @param int $column
	 *
	 * @return $this
	 */
	public function setColumnFreeze( $column ) {
		$this->column_freeze = (int) $column;
		return $this;
	}

	/**
	 * Generate and return PHPExcel object with all relevant data set up.
	 *
	 * @return \PHPExcel
	 */
	public function getExcel() {

		$sheet = $this->excel->getActiveSheet();
		$sheet->setTitle( $this->title );

		$this->createHeader();
		$this->fillData();
		$this->applyPostProcessFormatting();

		return $this->excel;

	}

	/**
	 * Generate header in excel based on field_mapping attributes.
	 */
	private function createHeader() {

		$i = 0;
		$sheet = $this->excel->getActiveSheet();

		foreach( $this->field_mapping as $key => $attributes ) {
			$cell = $sheet->setCellValueByColumnAndRow( $i, 1, $attributes[ 'title' ], true );
			$cell->getStyle()->applyFromArray( [
				'borders' => [ 'outline' => [ 'style' => \PHPExcel_Style_Border::BORDER_MEDIUM ] ],
				'fill' => [ 'type' => \PHPExcel_Style_Fill::FILL_SOLID, 'color' => [ 'rgb' => 'D3D3D3' ] ],
				'font' => [ 'bold' => true ]
			] );
			$i++;
		}

	}

	/**
	 * Fill in data from data array.
	 */
	private function fillData() {

		/*
		 * Generate rows
		 */
		$row = 1;
		foreach( $this->data as $item ) {
			$row++; //Starting at 2

			/*
			 * Generate columns
			 */
			$column = -1;
			foreach( $this->field_mapping as $key => $attributes ) {
				$column++; // Starting at 0

				$type = isset( $attributes[ 'type' ] ) ? $attributes[ 'type' ] : 'default';

				$cell = call_user_func( [ $this, 'writeCell' . ucfirst( $type ) ], $row, $column, $item[ $key ], $attributes );

				$this->applyDefaultCellStyles( $cell );

			}

		}

	}

	/**
	 * Sets default borders and other styles for cell.
	 *
	 * @param \PHPExcel_Cell $cell
	 */
	protected function applyDefaultCellStyles( $cell ) {

		$border = [ 'style' => \PHPExcel_Style_Border::BORDER_THIN ];
		$cell->getStyle()->applyFromArray( [
			'borders' => [
				'left' => $border,
				'bottom' => $border,
				'right' => $border
			]
		] );

	}

	/**
	 * Generate and write cell for simple strings
	 *
	 * IDE-unfriendly polymorphic call in $this->fillData
	 *
	 * @param int $row
	 * @param int $column
	 * @param string $value
	 *
	 * @return \PHPExcel_Cell
	 */
	protected function writeCellDefault( $row = 1, $column = 1, $value = '' ) {

		return $this->excel->getActiveSheet()->setCellValueByColumnAndRow( $column, $row, $value, true );

	}

	/**
	 * Generate and write cell for dates. Field mappings with type=>date also needs to have 'date_format'.
	 *
	 * IDE-unfriendly polymorphic call in $this->fillData
	 *
	 * @param int $row
	 * @param int $column
	 * @param null $value
	 * @param array $cell_attributes
	 *
	 * @return \PHPExcel_Cell
	 */
	protected function writeCellDate( $row = 1, $column = 1, $value = null, $cell_attributes ) {

		// If date is empty, handle it as simple string field
		if( empty( $value ) ) {
			return $this->writeCellDefault( $row, $column, '' );
		}

		$date = \DateTime::createFromFormat( $cell_attributes[ 'date_format' ], $value )->setTime( 0, 0, 0 );
		$cell = $this->excel->getActiveSheet()->setCellValueByColumnAndRow( $column, $row, \PHPExcel_Shared_Date::PHPToExcel( $date ), true );

		$cell->getStyle()->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY );

		return $cell;

	}

	/**
	 * Generate and write cell for select boxes. Field mapping for this one needs 'choices' array.
	 *
	 * IDE-unfriendly polymorphic call in $this->fillData
	 *
	 * @param int $row
	 * @param int $column
	 * @param string $value
	 * @param array $cell_attributes
	 *
	 * @return \PHPExcel_Cell
	 */
	protected function writeCellSelect( $row = 1, $column = 1, $value, $cell_attributes ) {

		return $this->excel->getActiveSheet()->setCellValueByColumnAndRow( $column, $row, $cell_attributes[ 'choices' ][ $value ], true );

	}

	/**
	 * Generate and write cell for ACF HTML.
	 *
	 * Strips all HTML tags and also removes last \n, which is always added by ACF.
	 *
	 * IDE-unfriendly polymorphic call in $this->fillData
	 *
	 * @param int $row
	 * @param int $column
	 * @param string $value
	 *
	 * @return \PHPExcel_Cell
	 */
	protected function writeCellHtml( $row = 1, $column = 1, $value = '' ) {

		$value = strip_tags( preg_replace( '/\s$/', '', $value ) ); // Strip last newline character, which is always there in ACF

		$cell = $this->excel->getActiveSheet()->setCellValueByColumnAndRow( $column, $row, $value, true );
		$cell->getStyle()->getAlignment()->setWrapText( true );

		return $cell;

	}

	/**
	 * Sets all columns to autosize and aplies colum-freezing in excel.
	 */
	protected function applyPostProcessFormatting() {

		$sheet = $this->excel->getActiveSheet();

		/*
		 * Set autosize columns
		 */
		for( $i = 0; $i < count( $this->field_mapping ); $i++ ) {
			$sheet->getColumnDimensionByColumn( $i )->setAutoSize( true );
		}

		/*
		 * Set column freeze
		 */
		$sheet->freezePaneByColumnAndRow( $this->column_freeze, 2 );

	}

}
<?php

namespace Lumiart\Vosspskm\Courses\Controllers;

use Lumiart\Vosspskm\Courses\App;
use Lumiart\Vosspskm\Courses\AutoloadableInterface;
use Lumiart\Vosspskm\Courses\Models\CoursePost;
use Lumiart\Vosspskm\Courses\SingletonTrait;

class CourseStudentsExcelExportController implements AutoloadableInterface {
	use SingletonTrait;

	/**
	 * @var App
	 */
	private $app;

	public function __construct( App $app ) {
		$this->app = $app;
	}

	/**
	 * Used in App autoloader
	 * @return void
	 */
	public function boot() {

		\Routes::map( 'wp-admin/course-excel-export/:post_id', [ $this, 'handleExcelExport' ] );

	}

	/**
	 * Handle request for Students Excel export
	 *
	 * Should output excel contents in php://output with appropriate headers
	 *
	 * @param array $params
	 */
	public function handleExcelExport( $params ) {

		/*
		 * Check for permissions
		 */
		if( !is_user_logged_in() ) wp_die( 'Invalid permissions' );
		if( !isset( $params[ 'post_id' ] ) ) wp_die( 'Missing parameters' );

		$post_id = (int)$params[ 'post_id' ];
		$post = new CoursePost( $post_id );

		// Also handles missing post ID
		if( !in_array( $post->post_type , array_keys( $this->app->getConfig()[ 'courses_post_types' ] ) ) ) wp_die( 'Invalid post ID' );

		if( !current_user_can( 'edit_kurzy-' . $post->post_type ) ) wp_die( 'Invalid permissons' );

		/*
		 * Generate Excel
		 */
		$excel = $post->generateStudentsExcel();
		$excel_writer = new \PHPExcel_Writer_Excel2007( $excel );

		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $post->slug . '.xlsx"');
		header('Cache-Control: max-age=0');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
		header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header('Pragma: public'); // HTTP/1.0

		$excel_writer->save('php://output');

		exit();
	}

}
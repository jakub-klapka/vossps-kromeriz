<?php
global $vossps_km_courses_app;
$courses_controller = $vossps_km_courses_app->make( \Lumiart\Vosspskm\Courses\Controllers\CourseListController::class );/** @var \Lumiart\Vosspskm\Courses\Controllers\CourseListController $courses_controller */

if( $courses_controller->isCourseArchive() ) {
	$courses_controller->courseArchive();
} else {
	$data = Timber::get_context();
	Timber::render( 'archive.twig', $data );
}
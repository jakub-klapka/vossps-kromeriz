<?php
global $vossps_km_courses_app;
$ctrl = $vossps_km_courses_app->make( \Lumiart\Vosspskm\Courses\Controllers\CourseDetailController::class ); /** @var \Lumiart\Vosspskm\Courses\Controllers\CourseDetailController $ctrl */
$ctrl->courseDetail();
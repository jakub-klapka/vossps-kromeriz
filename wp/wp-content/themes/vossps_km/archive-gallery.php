<?php
global $vossps_km_gallery_app;
/** @var \Lumiart\Vosspskm\Gallery\Controllers\GalleryArchiveFrontendController $ctrl */
$ctrl = $vossps_km_gallery_app->make( \Lumiart\Vosspskm\Gallery\Controllers\GalleryArchiveFrontendController::class );
$ctrl->renderArchive();
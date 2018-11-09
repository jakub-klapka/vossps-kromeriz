<?php
global $vossps_km_gallery_app;
/** @var \Lumiart\Vosspskm\Gallery\Controllers\SingleGalleryFrontendController $ctrl */
$ctrl = $vossps_km_gallery_app->make( \Lumiart\Vosspskm\Gallery\Controllers\SingleGalleryFrontendController::class );
$ctrl->renderSingleGallery();
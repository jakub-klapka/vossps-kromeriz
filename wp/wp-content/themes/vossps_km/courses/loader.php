<?php

/*
 * Setup autoloader
 */
spl_autoload_register( function( $class_name ) {
	$prefix = 'Lumiart\Vosspskm\Courses';
	if( substr( $class_name, 0, strlen( $prefix ) ) === $prefix ) {
		include_once( str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) + 1 ) ) . '.php' );
	}
} );

/*
 * Load App
 */
$app = new \Lumiart\Vosspskm\Courses\App();
$app->boot();

global $vossps_km_courses_app;
$vossps_km_courses_app = $app;
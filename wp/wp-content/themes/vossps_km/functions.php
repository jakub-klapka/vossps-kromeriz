<?php
include_once( 'vendor/autoload.php' );

define( 'LUMI_CORE_PATH', get_template_directory() . '/core/' );
define( 'LUMI_CSS_JS_VER', 2 );
define( 'LUMI_TEXTDOMAIN', 'vossps_km' );

if (class_exists('Timber')){
	if( !in_array( $_SERVER['HTTP_HOST'], [ 'localhost', 'ped-km.dev' ] ) ) {
		Timber::$cache = true;
	}
}

/**
 * Load Plugins translations
 * Actually fix textdomains, where plugin don't have same textdomain as pluginname
 */
$plugins_textdomain_fix = array(
//	'acf' => 'acf-options-page',
	'baweic' => 'baw-invitation-codes',
//	'sitepress' => 'sitepress-multilingual-cms',
//	'contact-form-7-to-database-extension' => 'contact-form-7-to-database-extension',
	'acf' => 'acf',
	'ga-dash' => 'google-analytics-dashboard-for-wp'
);
foreach( $plugins_textdomain_fix as $textdomain => $file_name ) {
	$file = WP_LANG_DIR . '/plugins/' . $file_name . '-' . get_locale() . '.mo';
	if( file_exists( $file ) ) {
		load_textdomain( $textdomain, $file );
	}
}

/**
 * Var containing references to all theme objects
 * @var array $lumi array with all classes used in template, by namespace
 *      $lumi['Glob'|'Admin'|'Frontend'][class_name]
 */
$lumi = array();
global $lumi;

$lumi['config'] = array(
	'static_ver' => 2,
	'dokumenty_id' => 98,
	'gdpr_id' => 4284,
	'fotogalerie_id' => 100,
	'ss_id' => 22,
	'vos_id' => 24,
	'dv_id' => 26,
	'spp_id' => 2908,
	'ckp_id' => 3813,
	'tax_vos_id' => 4,
	'tax_ss_id' => 3,
	'tax_dv_id' => 5,
	'uzitecne_odkazy_id' => 876
);


/**
 * Classes autoloading
 * All classes are located in core/classes as class_name.class.php
 * All classes are using namespace Lumi/Classes
 */
spl_autoload_register( function ( $class ) {
	if ( strpos( $class, 'Lumi\\Classes\\' ) === false ) {
		return;
	}
	$tmp        = explode( '\\', $class );
	$class_name = end( $tmp );
	require_once( LUMI_CORE_PATH . 'classes/' . $class_name . '.class.php' );
} );



/**
 * Classes autoloading
 * Will load files in core directory absed on their suffix
 *      .glob.php will be loaded everytime
 *      .admin.php will be loaded in admin (is_admin())
 *      .frontend.php will be loaded when not in admin (even logged in)
 */
$core['Glob'] = glob( LUMI_CORE_PATH . '*.glob.php' );
if ( is_admin() ) {
	$core['Admin'] = glob( LUMI_CORE_PATH . '*.admin.php' );
} else {
	$core['Frontend'] = glob( LUMI_CORE_PATH . '*.frontend.php' );
}
foreach ( $core as $scope => $files ) {
	if ( $files !== false ) {
		foreach ( $files as $file ) {
			include_once $file;
			$class_name                                  = basename( $file, '.' . strtolower( $scope ) . '.php' );
			$class_path                                  = '\\Lumi\\' . $scope . '\\' . $class_name;
			$lumi[ $scope ][ strtolower( $class_name ) ] = new $class_path;
		}
	}
}


/**
 * Template classes loading
 * Those contain functions used in templates - which are loaded on demand to save mem
 * Will return reference to class, which can contain your functions. It will load the class, if it's not loaded yet.
 * @var string $name
 * @return \Lumi\Template\Reviews
 */
function lumi_template( $name ) {
	global $lumi;
	if ( empty( $name ) ) {
		return false;
	}
	if ( isset( $lumi['Template'][ $name ] ) ) {
		return $lumi['Template'][ $name ]; //If template functions are already loaded
	}
	include_once LUMI_CORE_PATH . $name . '.template.php';
	$class_name                = '\\Lumi\\Template\\' . $name;
	$lumi['Template'][ $name ] = new $class_name;
	return $lumi['Template'][ $name ];
}

/**
 * Modern components autoloader
 */
spl_autoload_register( function( $class_name ) {
	$prefix = 'Lumiart\Vosspskm';
	if( substr( $class_name, 0, strlen( $prefix ) ) === $prefix ) {
		include_once( str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) + 1 ) ) . '.php' );
	}
} );

/**
 * Load components
 */
global $vossps_km_courses_app;
$vossps_km_courses_app = new \Lumiart\Vosspskm\App\App( 'Courses' );
$vossps_km_courses_app->boot();

global $vossps_km_gallery_app;
$vossps_km_gallery_app = new \Lumiart\Vosspskm\App\App( 'Gallery' );
$vossps_km_gallery_app->boot();


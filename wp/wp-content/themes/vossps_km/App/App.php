<?php

namespace Lumiart\Vosspskm\App;

/**
 * Generic component app
 *
 * Handles autoloading and config for modern theme components.
 * App expects existig config.php in component root.
 *
 * @package Lumiart\Vosspskm\Courses
 */
class App {

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * @var array
	 */
	private $booted_singletons = [];

	/**
	 * @param string $component Provide directory name (case-sensitive) of component located in theme root
	 */
	public function __construct( string $component ) {
		$this->config = require( dirname( __DIR__ ) . '/' . $component . '/config.php' );
	}

	/**
	 * Application bootstrap
	 */
	public function boot() {

		$this->autoloadClasses();

	}

	/**
	 * Instanciate classes defined in config as autoloadable
	 */
	private function autoloadClasses() {

		$classes = $this->config[ 'autoload_classes' ];

		foreach( $classes as $class ) {
			$instance = $this->make( $class );
			$instance->boot();
		}

		//Admin-only autoloading
		add_action( 'init', function() {
			foreach( $this->config[ 'autoload_on_admin_init' ] as $class_name ) {
				$instance = $this->make( $class_name );
				$instance->boot();
			}
		}, 5 );

	}

	/**
	 * Make class instance
	 *
	 * If class is defined as singleton, return it's booted instance
	 *
	 * Pass this instance of App as fist parameter to all classes __constructs
	 *
	 * @param string $class_name
	 *
	 * @return mixed
	 */
	public function make( $class_name ) {

		if( in_array( $class_name, array_keys( $this->booted_singletons ) ) ) {
			return $this->booted_singletons[ $class_name ];
		}

		$new_instance = new $class_name( $this );

		if( method_exists( $new_instance, 'isSingleton' ) && $new_instance->isSingleton() === true ) {
			$this->booted_singletons[ $class_name ] = $new_instance;
		}

		return $new_instance;

	}

	/**
	 * Get config directives
	 *
	 * @return array
	 */
	public function getConfig() {
		return $this->config;
	}

}
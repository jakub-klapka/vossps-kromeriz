<?php

namespace Lumiart\Vosspskm\Courses\Controllers;

use Lumiart\Vosspskm\App\AutoloadableInterface;
use Lumiart\Vosspskm\App\SingletonTrait;

class RegisterAssets implements AutoloadableInterface {
	use SingletonTrait;

	/**
	 * Used in App autoloader
	 * @return void
	 */
	public function boot() {

		add_action( 'wp', [ $this, 'registerAssets' ] );

	}

	/**
	 * Register assets related to courses
	 *
	 * Fired in wp action to have them avail in controllers
	 *
	 * @wp-action wp
	 */
	public function registerAssets() {
		$version = wp_get_theme()->get( 'Version' );
		wp_register_style( 'courses_style', get_template_directory_uri() . '/assets/css/courses.css', [ 'layout' ], $version );
		wp_register_script( 'bootstrap_modal', get_template_directory_uri() . '/assets/js/libs/bootstrap_modal.js', [ 'jquery' ], $version, true );
		wp_register_script( 'icheck', get_template_directory_uri() . '/assets/js/libs/icheck.js', [ 'jquery' ], $version, true );
		wp_register_script( 'validator', get_template_directory_uri() . '/assets/js/libs/validator.js', [ 'jquery' ], $version, true );
		wp_register_script( 'datepicker_dep', get_template_directory_uri() . '/assets/js/libs/jquery-ui-datepicker.js', [ 'jquery' ], $version, true );
		wp_register_script( 'datepicker', get_template_directory_uri() . '/assets/js/libs/jquery-ui-datepicker-cs.js', [ 'jquery', 'datepicker_dep' ], $version, true );
		wp_register_script( 'courses_form', get_template_directory_uri() . '/assets/js/courses_form.js', [ 'jquery', 'bootstrap_modal', 'icheck', 'validator', 'velocity', 'datepicker' ], $version, true );
	}

}
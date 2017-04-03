<?php

namespace Lumiart\Vosspskm\Courses\Controllers;

use Lumiart\Vosspskm\Courses\App;
use Lumiart\Vosspskm\Courses\AutoloadableInterface;
use Lumiart\Vosspskm\Courses\SingletonTrait;

class UserManagement implements AutoloadableInterface {
	use SingletonTrait;

	/**
	 * @var App
	 */
	private $app;

	public function __construct( App $app ) {
		$this->app = $app;
	}

	public function boot() {

		add_filter( 'timber_context', [ $this, 'addTemplateVarsToContext'] );

	}

	/**
	 * Can current user edit any course post type?
	 *
	 * @return bool
	 */
	public function currentUserCanEditAnyCourse() {

		$current_user_can_edit_any_course = false;
		$post_types_slugs = array_keys( $this->app->getConfig()[ 'courses_post_types' ] );

		foreach( $post_types_slugs as $slug ) {
			if( current_user_can( 'edit_kurzy-' . $slug ) ) {
				$current_user_can_edit_any_course = true;
			}
		}

		return $current_user_can_edit_any_course;

	}

	/**
	 * Add info about abulity to edit any course post type to Timber context
	 *
	 * @param array $context
	 *
	 * @return array
	 */
	public function addTemplateVarsToContext( $context ) {

		return array_merge( $context, [ 'current_user_can_edit_any_course' => $this->currentUserCanEditAnyCourse() ] );

	}

}
<?php

namespace Lumiart\Vosspskm\Courses\Controllers;


use Lumiart\Vosspskm\Courses\App;
use Lumiart\Vosspskm\Courses\AutoloadableInterface;
use Lumiart\Vosspskm\Courses\Models\CourseMenuItem;
use Lumiart\Vosspskm\Courses\SingletonTrait;

class MainMenuController implements AutoloadableInterface {
	use SingletonTrait;

	/**
	 * @var App
	 */
	private $app;

	/**
	 * @var array
	 */

	protected $post_types_slugs;

	/**
	 * @var UserManagement
	 */
	private $userManagement;

	public function __construct( App $app ) {
		$this->app = $app;
		$this->post_types_slugs = array_keys( $this->app->getConfig()[ 'courses_post_types' ] );
		$this->userManagement = $this->app->make( UserManagement::class );
	}

	/**
	 * WP hooks on init 9
	 */
	public function boot() {

		if( $this->app->getConfig()[ 'courses_published' ] || $this->userManagement->currentUserCanEditAnyCourse() ) {
			add_filter( 'lumi/pedkm/unsorted_menu', [ $this, 'addMenuItems' ] );
			add_filter( 'lumi/pedkm/menu_item', [ $this, 'transformMenuItemToFlatArray' ], 10, 2 );
		}

	}

	/**
	 * Add new items to menu array
	 *
	 * @wp-filter lumi/pedkm/unsorted_menu
	 * @param array $menu
	 *
	 * @return array
	 */
	public function addMenuItems( $menu ) {

		$course_menu_item = $this->app->make( CourseMenuItem::class );

		return array_merge( $menu, [ $course_menu_item ] );

	}

	/**
	 * Modify course menu items to mock WP_Post
	 *
	 * @wp-filter lumi/pedkm/menu_item
	 * @param array $item_output
	 * @param \WP_Post $page (mock)
	 *
	 * @return array
	 */
	public function transformMenuItemToFlatArray( $item_output, $page ) {
		if( !( $page instanceof CourseMenuItem ) ) return $item_output;

		$item_output = array(
			'id' => $page->ID,
			'name' => $page->post_title,
			'url' => trailingslashit( get_bloginfo( 'url' ) ) . 'kurzy/',
			'children' => ( $this->currentlyIsOnCousePage() ) ? $this->generateMenuChildren() : [],
			'is_dv' => true
		);

		return $item_output;
	}

	/**
	 * Generate children for main course menu
	 *
	 * @return array
	 */
	private function generateMenuChildren() {

		$types = [];

		foreach( $this->app->getConfig()[ 'courses_post_types' ] as $slug => $attrs ) {
			$active  = ( is_post_type_archive( $slug ) || is_singular( $slug ) ) ? true : false;
			$new_item = [
				'url' => get_post_type_archive_link( $slug ),
				'name' => $attrs[ 'full_name' ],
				'is_active' => $active
			];
			if( $active ) array_unshift( $types, $new_item ); else $types[] = $new_item; // Move active item to first position
		}

		return $types;

	}

	/**
	 * Gets if we are currently on any course page, thus displaying also child pages
	 *
	 * @return bool
	 */
	public function currentlyIsOnCousePage() {

		global $wp_query;
		if( is_post_type_archive( $this->post_types_slugs )
		    || is_singular( $this->post_types_slugs )
		    || $wp_query->get( 'course_type_page' ) === '1' ) return true;
		return false;

	}

}
<?php
namespace Lumiart\Vosspskm\Courses\Controllers;

use Lumiart\Vosspskm\Courses\App;
use Lumiart\Vosspskm\Courses\AutoloadableInterface;
use Lumiart\Vosspskm\Courses\SingletonTrait;

/**
 * Class RegisterPostTypes
 *
 * Handles post types associated with Courses
 *
 * @package Lumiart\Vosspskm\Courses\Controllers
 */
class RegisterPostTypes implements AutoloadableInterface {
	use SingletonTrait;

	/**
	 * @var App
	 */
	protected $app;

	public function __construct( App $app ) {
		$this->app = $app;
	}

	/**
	 * Hook to WP
	 */
	public function boot() {

		add_action( 'init', [ $this, 'registerPostTypes' ] );

	}

	/**
	 * Do basic registration of CPT
	 *
	 * @wp-action init
	 */
	public function registerPostTypes() {

		foreach( $this->app->getConfig()[ 'courses_post_types' ] as $post_type_slug => $attributes ) {

			register_post_type( $post_type_slug, [
				'label' => $attributes[ 'short_name' ],
				'public' => true,
				'menu_position' => 20,
				//TODO'menu_icon' => ,
				//TODO capabilities,
				'supports' => [ 'title', 'editor', 'author', 'revisions' ],
				'has_archive' => true,
				'rewrite' => [
					'slug' => 'kurzy/' . $post_type_slug,
					'with_front' => false,
					'feeds' => false,
					'pages' => false
				],
				'delete_with_user' => false
			] );

		}

	}

}
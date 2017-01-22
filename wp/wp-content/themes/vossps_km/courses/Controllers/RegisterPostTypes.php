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
				'labels' => [
					'add_new' => 'Nový kurz'
				],
				'public' => true,
				'menu_position' => 53,
				'menu_icon' => 'dashicons-welcome-learn-more',
				'capability_type' => [ 'kurzy-' . $post_type_slug, 'kurzy-' . $post_type_slug ],
				'map_meta_cap' => false,
				'supports' => [ 'title', 'editor', 'author', 'revisions' ],
				'has_archive' => true,
				'rewrite' => [
					'slug' => 'kurzy/' . $attributes[ 'rewrite_slug' ],
					'with_front' => false,
					'feeds' => false,
					'pages' => false
				],
				'delete_with_user' => false
			] );

		}

	}

}
<?php

namespace Lumiart\Vosspskm\Courses\Controllers;

use Lumiart\Vosspskm\App\App;
use Lumiart\Vosspskm\App\AutoloadableInterface;
use Lumiart\Vosspskm\App\SingletonTrait;

class CourseTypesController implements AutoloadableInterface {
	use SingletonTrait;

	/**
	 * @var \Lumiart\Vosspskm\\Lumiart\Vosspskm\App\App
	 */
	private $app;

	public function __construct( App $app ) {

		$this->app = $app;
	}

	/**
	 * WP Hooks
	 */
	public function boot() {

		add_action( 'init', [ $this, 'registerRewriteRule' ] );
		add_filter( 'template_include', [ $this, 'loadTypeTemplate' ] );

	}

	/**
	 * Register custom rewrite rules for types page
	 *
	 * @wp-action init
	 */
	public function registerRewriteRule() {

		add_rewrite_rule( 'kurzy/?$', 'index.php?course_type_page=1', 'top' );
		add_rewrite_tag( '%course_type_page%', '([^&]+)' );

	}

	/**
	 * Hook to template redirecting and coose types page if we have custom tag in url
	 *
	 * @wp-filter template_redirect
	 * @param string $template
	 *
	 * @return string
	 */
	public function loadTypeTemplate( $template ) {
		if( get_query_var( 'course_type_page' ) === '1' ) {
			return locate_template( 'course_type_page.php' );
		};

		return $template;
	}

	/**
	 * Controller for content display
	 */
	public function index() {

		wp_enqueue_style( 'courses_style' );

		/*
		 * Builin types
		 */
		$types = [];
		foreach( $this->app->getConfig()[ 'courses_post_types' ] as $slug => $attributes ) {
			$types[] = [
				'name' => $attributes[ 'full_name' ],
				'url' => get_post_type_archive_link( $slug )
			];
		}

		/*
		 * Custom buttons from settings
		 */
		$custom_post_ids = get_field('courses_types_additional_links', 'option' );
		if( empty( $custom_post_ids ) ) $custom_post_ids = [];
		foreach( $custom_post_ids as $post_id ) {
			$post = new \TimberPost( $post_id );
			$types[] = [
				'name' => $post->post_title,
				'url' => $post->link(),
				'custom_button' => true
			];
		}

		$data = [
			'course_types' => $types,
			'breadcrumbs' => [
				[
					'name' => 'Hlavní strana',
					'url' => get_bloginfo( 'url' )
				],
				[
					'name' => 'Další vzdělávání',
					'url' => trailingslashit( get_bloginfo( 'url' ) ) . 'kurzy/'
				]
			]
		];

		\Timber::render( [ 'course_types.twig' ], array_merge( \Timber::get_context(), $data ) );

	}

}
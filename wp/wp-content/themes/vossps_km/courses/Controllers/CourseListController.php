<?php

namespace Lumiart\Vosspskm\Courses\Controllers;


use Lumiart\Vosspskm\App\App;
use Lumiart\Vosspskm\App\AutoloadableInterface;
use Lumiart\Vosspskm\Courses\Models\CoursePost;
use Lumiart\Vosspskm\App\SingletonTrait;

class CourseListController implements AutoloadableInterface {
	use SingletonTrait;

	/**
	 * @var \Lumiart\Vosspskm\App\App
	 */
	private $app;

	/**
	 * Just cache for course type slugs
	 *
	 * @var array
	 */
	protected $course_post_type_slugs;

	public function __construct( App $app ) {
		$this->app = $app;
		$this->course_post_type_slugs = array_keys( $this->app->getConfig()['courses_post_types'] );
	}

	/**
	 * WP Hooks
	 */
	public function boot() {

		add_action( 'pre_get_posts', [ $this, 'modifyCourseArchiveQuery' ] );

	}

	/**
	 * Hook to post archive query and modify it on course post types
	 *
	 * We don't need paging, we have to sort by signup date and we want just visible courses
	 *
	 * @wp-action pre_get_posts
	 * @param \WP_Query $query
	 */
	public function modifyCourseArchiveQuery( $query ) {
		if( is_admin() || !$query->is_main_query() || !$query->is_post_type_archive( $this->course_post_type_slugs ) ) return;

		$query->set( 'nopaging', true );
		$query->set( 'meta_query', [
			[
				'key' => 'course_visible',
				'value' => true
			]
		] );

		$query->set( 'meta_key', 'signup_close_date' );
		$query->set( 'orderby', 'meta_value_num' );

	}

	/**
	 * Get, if current page is course archive page
	 *
	 * @return bool
	 */
	public function isCourseArchive() {

		if( is_post_type_archive( $this->course_post_type_slugs ) ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Main controller method
	 *
	 * Called from archive.php, only if current page is really course post archive
	 */
	public function courseArchive() {

		wp_enqueue_style( 'courses_style' );

		global $wp_query;
		$posts = $wp_query->get_posts();

		// Split posts by signup date
		$avail_courses = [];
		$due_courses = [];
		foreach( $posts as $post ) {
			$course_post = new CoursePost( $post->ID );
			if( !$course_post->isSignupDue() ) {
				$avail_courses[] = $course_post;
			} else {
				$due_courses[] = $course_post;
			}
		}

		$course_type_title = $this->app->getConfig()['courses_post_types'][ get_post_type() ]['full_name'];
		$data = [
			'page_title' => $course_type_title,
			'avail_courses' => $avail_courses,
			'due_courses' => $due_courses,
			'course_info' => get_field( 'course_option_' . get_post_type() . '_course_type_description', 'option' ),
			'breadcrumbs' => [
				[
					'name' => 'Hlavní strana',
					'url' => get_bloginfo( 'url' )
				],
				[
					'name' => 'Další vzdělávání',
					'url' => trailingslashit( get_bloginfo( 'url' ) ) . 'kurzy/'
				],
				[
					'name' => $course_type_title,
					'url' => get_post_type_archive_link( get_post_type() )
				]
			]
		];

		\Timber::render( 'course_archive.twig', array_merge( \Timber::get_context(), $data ) );

	}

}
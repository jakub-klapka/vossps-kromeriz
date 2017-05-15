<?php

namespace Lumiart\Vosspskm\Courses\Controllers;

use Lumiart\Vosspskm\Courses\App;
use Lumiart\Vosspskm\Courses\Models\CoursePost;
use Lumiart\Vosspskm\Courses\SingletonTrait;
use Timber;

class CourseDetailController {
	use SingletonTrait;

	protected $app;
	protected $register_post_types; /** @var RegisterPostTypes register_post_types */
	protected $config;

	public function __construct( App $app ) {
		$this->app = $app;
		$this->register_post_types = $app->make( RegisterPostTypes::class );
		$this->config = $app->getConfig();
	}

	/**
	 * Controller for course detail display
	 *
	 * @wp-template single.php
	 */
	public function courseDetail() {

		if( is_singular( $this->register_post_types->getArrayOfAllPostTypeSlugs() ) ) {

			global $wp_post;
			$post = new CoursePost( $wp_post );

			wp_enqueue_style( 'courses_style' );

			if( $post->isStillSignable() ) {
				wp_enqueue_script( 'courses_form' );
				$form = $post->getCourseForm();
				$form_view = $form->createView();
			}

			$data = [
				'breadcrumbs' => $this->generateBreadcrumbs( $post ),
				'post' => $post,
				'wp_date_format' => get_option( 'date_format' ),
				'signup_date_is_critical' => $post->isSignupDateCritical(),
				'form' => ( isset( $form_view ) ) ? $form_view : null,
				'sign_closed_reason' => $post->getSignClosedReason(),
				'form_errors' => ( isset( $form ) ) ? $form->getErrors( true ) : false,
				'recaptcha_sitekey' => $this->config[ 'recaptcha' ][ 'sitekey' ]
			];
			Timber::render( 'course_detail.twig', array_merge( Timber::get_context(), $data ) );
			return;
		}

		echo get_template_part( 'index' ); return;
	}

	/**
	 * Generate Course-specific breadcrumbs
	 *
	 * @param CoursePost $post
	 *
	 * @return array {
	 *  @type string $name Display name
	 *  @type string $url URL
	 * }
	 */
	private function generateBreadcrumbs( CoursePost $post ) {
		$home_page = [ 'name' => 'Hlavní strana', 'url' => get_bloginfo( 'url' ) ];
		$dv = [ 'name' => 'Další vzdělávání', 'url' => trailingslashit( get_bloginfo( 'url' ) ) . 'kurzy/' ];
		$category = [
			'name' => $this->config[ 'courses_post_types' ][ $post->get_post_type()->name ][ 'full_name' ],
			'url' => get_post_type_archive_link( $post->post_type )
		];

		return [ $home_page, $dv, $category ];
	}

}
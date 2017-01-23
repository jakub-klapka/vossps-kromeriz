<?php

namespace Lumiart\Vosspskm\Courses\Controllers;


use Lumiart\Vosspskm\Courses\App;
use Lumiart\Vosspskm\Courses\AutoloadableInterface;
use Lumiart\Vosspskm\Courses\Models\CoursePost;
use Lumiart\Vosspskm\Courses\SingletonTrait;

class AdminListController implements AutoloadableInterface {
	use SingletonTrait;

	protected $app;

	public function __construct( App $app ) {
		$this->app = $app;
	}

	/**
	 * Used in App autoloader
	 * @return void
	 */
	public function boot() {

		$cpt_slugs = array_keys( $this->app->getConfig()[ 'courses_post_types' ] );

		/*
		 * Actions and filters for all cpts
		 */
		foreach( $cpt_slugs as $cpt_slug ) {

			add_filter( "manage_edit-{$cpt_slug}_columns", [ $this, 'removeAuthorAndPublishDateColumns' ] );
			add_filter( "manage_edit-{$cpt_slug}_columns", [ $this, 'addCoursesCustomColumns' ] );
			add_action( "manage_{$cpt_slug}_posts_custom_column", [ $this, 'renderNewColumns'], 10, 2 );

		}

	}

	/**
	 * Remove Author and Published Date columns from edit screen for all courses
	 *
	 * @wp-filter manage_edit-{$cpt_slug}_columns
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function removeAuthorAndPublishDateColumns( $columns ) {

		unset( $columns[ 'author' ] );
		unset( $columns[ 'date' ] );

		return $columns;

	}

	/**
	 * Add columns relevant to courses
	 *
	 * @wp-filter manage_edit-{$cpt_slug}_columns
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function addCoursesCustomColumns( $columns ) {

		$new_columns = [
			'signup_close_date' => 'Datum uzávěrky přihlášek',
			'students_count' => 'Kapacita kurzu',
			'students_left' => 'Zbývá míst'
		];

		$title_position = array_search( 'title', array_keys( $columns ) ); //1-indexed
		$before_title = array_slice( $columns, 0, $title_position + 1 );
		$after_title = array_slice( $columns, $title_position + 1 );

		return array_merge( $before_title, $new_columns, $after_title );

	}

	/**
	 * Handle rendering of custom columns
	 *
	 * @wp-action manage_{$cpt_slug}_posts_custom_column, 10, 2
	 *
	 * @param string $column
	 * @param \WP_Post $wp_post
	 */
	public function renderNewColumns( $column, $wp_post ) {

		$post = new CoursePost( $wp_post );

		if( $column === 'signup_close_date' ) {

			echo $post->getFormatedSignupCloseDate();

		};

	}



}
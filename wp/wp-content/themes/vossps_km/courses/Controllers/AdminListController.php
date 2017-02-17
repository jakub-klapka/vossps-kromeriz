<?php

namespace Lumiart\Vosspskm\Courses\Controllers;


use Lumiart\Vosspskm\Courses\App;
use Lumiart\Vosspskm\Courses\AutoloadableInterface;
use Lumiart\Vosspskm\Courses\Models\CoursePost;
use Lumiart\Vosspskm\Courses\SingletonTrait;

class AdminListController implements AutoloadableInterface {
	use SingletonTrait;

	protected $app;

	/**
	 * Array of fetched CoursePosts for caching purposes
	 *
	 * @var CoursePost[]
	 */
	protected $post_identity_map = [];

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
			add_filter( "manage_edit-{$cpt_slug}_sortable_columns", [ $this, 'makeNewColumnsSortable'] );

		}

		add_action( 'pre_get_posts', [ $this, 'orderByNewColumns' ] );
		add_filter( 'disable_months_dropdown', [ $this, 'maybeDisableDateFilter' ], 10, 2 );
		add_action( 'restrict_manage_posts', [ $this, 'addVisibilityFilterDropdown' ], 10, 2 );
		add_action( 'pre_get_posts', [ $this, 'filterByVisibility' ] );
		add_filter( 'query_vars', [ $this, 'registerFilteringQueryVars' ] );

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
	 * Helper for this Controller to cache created instances of CoursePosts
	 *
	 * Kinda IdentityMap
	 *
	 * @param int|string $id
	 *
	 * @return CoursePost
	 */
	protected function getPostInstance( $id ) {
		$id = (int)$id;
		if( !isset( $this->post_identity_map[ $id ] ) ) {
			$this->post_identity_map[ $id ] = new CoursePost( $id );
		};
		return $this->post_identity_map[ $id ];
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
			'course_visible' => 'Viditelný',
			'signup_close_date' => 'Datum uzávěrky přihlášek',
			'course_realization' => 'Datum realizace kurzu',
			'students_count' => 'Obsazenost kurzu'
		];

		$title_position = array_search( 'title', array_keys( $columns ) ); //1-indexed
		$before_title = array_slice( $columns, 0, $title_position + 1 );
		$after_title = array_slice( $columns, $title_position + 1 );

		return array_merge( $before_title, $new_columns, $after_title );

	}

	/**
	 * Handle rendering of custom columns
	 *
	 * Followed by polymorphic functions for each rendered column
	 *
	 * @wp-action manage_{$cpt_slug}_posts_custom_column, 10, 2
	 *
	 * @param string $column
	 * @param int $wp_post_id
	 */
	public function renderNewColumns( $column, $wp_post_id ) {

		$polymorphic_method_name = 'renderNewColumn_' . $column;

		$post = $this->getPostInstance( $wp_post_id );

		if( method_exists( $this, $polymorphic_method_name ) ) {
			echo call_user_func( [ $this, $polymorphic_method_name ], $post );
		}

	}

	private function renderNewColumn_course_realization( CoursePost $post ) {
		return $post->getCourseRealizationDate();
	}

	private function renderNewColumn_signup_close_date( CoursePost $post ) {
		return $post->getFormatedSignupCloseDate();
	}

	private function renderNewColumn_students_count( CoursePost $post ) {
		return $post->getSignedStudentsCount() . '/' . $post->getCourseCapacity();
	}

	private function renderNewColumn_course_visible( CoursePost $post ) {
		$html_params = [
			true => [ 'green', 'yes', 'Ano' ],
			false => [ 'red', 'no', 'Ne' ]
		];
		return vsprintf( '<div style="color: %s;"><span class="dashicons dashicons-%s"></span>&nbsp;%s</div>', $html_params[ $post->isVisible() ] );
	}

	/**
	 * Indicate, which columns are sortable on admin screen
	 *
	 * @wp-filter manage_edit-{$cpt_slug}_sortable_columns
	 *
	 * @param array $sortable_columns
	 *
	 * @return array
	 */
	public function makeNewColumnsSortable( $sortable_columns ) {
		if( isset( $sortable_columns[ 'date' ] ) ) unset( $sortable_columns[ 'date' ] );
		$sortable_columns[ 'signup_close_date' ] = [ 'signup_close_date', true ];
		return $sortable_columns;
	}

	/**
	 * If we are on Course admin lists, order by signup date
	 *
	 * Order direction is set automatically, when user selects another ordering
	 *
	 * @wp-filter pre_get_posts
	 *
	 * @param \WP_Query &$query
	 */
	public function orderByNewColumns( $query ) {

		if( is_admin()
		    && $query->is_post_type_archive( array_keys( $this->app->getConfig()[ 'courses_post_types' ] ) )
			&& $query->is_main_query() ) {

			$query->set( 'meta_key', 'signup_close_date' );
			$query->set( 'orderby', 'meta_value' );
			//Order is set automatically via admin query

		}

	}

	/**
	 * Disable Date filter on courses listings
	 *
	 * @wp-filter disable_months_dropdown
	 *
	 * @param bool $disabled
	 * @param string $post_type
	 *
	 * @return bool
	 */
	public function maybeDisableDateFilter( $disabled, $post_type ) {
		if( in_array( $post_type, array_keys( $this->app->getConfig()[ 'courses_post_types' ] ) ) ) {
			return true;
		}
		return $disabled;
	}

	/**
	 * Create dropdown for visibility filtering
	 *
	 * @wp-action restrict_manage_posts
	 *
	 * @param $post_type
	 * @param $which
	 */
	public function addVisibilityFilterDropdown( $post_type, $which ) {
		if( !in_array( $post_type, array_keys( $this->app->getConfig()[ 'courses_post_types' ] ) ) ) return;

		$visible_selected = isset( $_GET['course_filter_by_visibility'] ) && $_GET['course_filter_by_visibility'] === 'visible';
		$invisible_selected = isset( $_GET['course_filter_by_visibility'] ) && $_GET['course_filter_by_visibility'] === 'invisible';
		$all_selected = isset( $_GET['course_filter_by_visibility'] ) && $_GET['course_filter_by_visibility'] === 'all';

		$output = '<select name="course_filter_by_visibility" style="max-width: none;">
					<option value="all">— Filtrovat podle Viditelnosti —</option>
					<option value="visible" ' . ( ( $visible_selected ) ? 'selected="selected"' : '' ) . ' >Pouze viditelné</option>
					<option value="invisible" ' . ( ( $invisible_selected ) ? 'selected="selected"' : '' ) . ' >Pouze neviditelné</option>
					<option value="all" ' . ( ( $all_selected ) ? 'selected="selected"' : '' ) . ' >Všechny</option>
				   </select>';

		echo $output;

	}

	/**
	 * Register query var used for visibility filtering
	 *
	 * @wp-filter query_vars
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	public function registerFilteringQueryVars( $vars ) {
		$vars[] = 'course_filter_by_visibility';
		return $vars;
	}

	/**
	 * Do actual filtering by visibility (maybe)
	 *
	 * @wp-action pre_get_posts
	 *
	 * @param \WP_Query &$query
	 */
	public function filterByVisibility( $query ) {
		if( !is_admin()
		    || !$query->is_post_type_archive( array_keys( $this->app->getConfig()[ 'courses_post_types' ] ) )
		    || !$query->is_main_query() ) return;

		if( $query->get( 'course_filter_by_visibility' ) && $query->get( 'course_filter_by_visibility' ) !== 'all' ) {

			$query->set( 'meta_query', [
				[
					'key' => 'course_visible',
					'value' => $query->get( 'course_filter_by_visibility' ) === 'invisible' ? '0' : '1'
				]
			] );

		};

	}

}
<?php

namespace Lumiart\Vosspskm\Courses\Controllers;


use Lumiart\Vosspskm\Courses\App;
use Lumiart\Vosspskm\Courses\AutoloadableInterface;
use Lumiart\Vosspskm\Courses\Models\CoursePost;
use Lumiart\Vosspskm\Courses\Services\CourseExcelGenerator;
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

		add_action( 'manage_posts_extra_tablenav', [ $this, 'maybeAddExportToExcelButton' ] );
		add_filter( 'query_vars', [ $this, 'registerExportQueryVars' ] );
		add_action( 'wp', [ $this, 'doExcelExport' ] );

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
			'students_count' => 'Obsazenost kurzu',
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

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function renderNewColumn_course_realization( CoursePost $post ) {
		return $post->getCourseRealizationDate();
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function renderNewColumn_signup_close_date( CoursePost $post ) {
		return $post->getFormatedSignupCloseDate();
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function renderNewColumn_students_count( CoursePost $post ) {
		return $post->getSignedStudentsCount() . '/' . $post->getCourseCapacity();
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function renderNewColumn_course_visible( CoursePost $post ) {
		$html_params = [
			true => [ 'green', 'yes', 'Ano' ],
			false => [ 'red', 'no', 'Ne' ],
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
	 */
	public function addVisibilityFilterDropdown( $post_type ) {
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
					'value' => $query->get( 'course_filter_by_visibility' ) === 'invisible' ? '0' : '1',
				],
			] );

		};

	}

	/**
	 * Check for course post type edit screen and add button to tablenav
	 *
	 * @wp-action 'manage_posts_extra_tablenav'
	 * @param string $which 'top'|'bottom'
	 */
	public function maybeAddExportToExcelButton( $which ) {

		$screen = get_current_screen();
		if( $which !== 'top'
			|| $screen->parent_base !== 'edit'
		    || ! in_array( $screen->post_type, array_keys( $this->app->getConfig()['courses_post_types'] ) ) ) return;

		global $wp;
		$current_url = trailingslashit( get_bloginfo( 'url' ) ) . $wp->request . '?' . $wp->query_string;
		$target_url = add_query_arg( 'do-course-list-excel-export', true, $current_url );

		echo '<div class="alignleft actions"><a href="' . $target_url . '" class="button button-primary" style="margin-top: 0;" target="_blank">Exportovat seznam kurzů do Excelu</a></div>';

	}

	/**
	 * Register query var indicating request for excel export
	 *
	 * @param array $vars
	 *
	 * @wp-filter 'query_vars'
	 * @return array
	 */
	public function registerExportQueryVars( $vars ) {
		$vars[] = 'do-course-list-excel-export';
		return $vars;
	}

	/**
	 * Handle request for course list export
	 *
	 * @param \WP $wp
	 */
	public function doExcelExport( $wp ) {
		if( ! isset( $wp->query_vars[ 'do-course-list-excel-export' ] )
		    || $wp->query_vars[ 'do-course-list-excel-export' ] !== '1' ) return;

		$data = [];
		global $wp_query;
		foreach( $wp_query->posts as $post ) {
			/** @var \WP_Post $post */
			$course_post = new CoursePost( $post->ID );
			$data[] = [
				'title' => $course_post->title(),
				'visible' => $course_post->isVisible() ? 'Ano' : 'Ne',
				'signup_close_date' => get_field( 'signup_close_date', $course_post->ID ),
				'course_realization' => get_field( 'course_realization', $course_post->ID ),
				'acreditation_number' => get_field( 'acreditation_number', $course_post->ID ),
				'price' => get_field( 'price', $course_post->ID ),
				'lesson_count' => get_field( 'lesson_count', $course_post->ID ),
				'teacher' => get_field( 'teacher', $course_post->ID ),
				'students_count' => get_field( 'students_count', $course_post->ID ),
				'signed_students_count' => $course_post->getSignedStudentsCount(),
			];
		}

		/** @var CourseExcelGenerator $generator */
		$generator = $this->app->make( CourseExcelGenerator::class );
		$generator->setTitle( 'Kurzy' )
		          ->setFieldMapping( [
			          'title' => [ 'title' => 'Název' ],
			          'visible' => [ 'title' => 'Kurz viditelný' ],
			          'signup_close_date' => [ 'title' => 'Termín uzávěrky přihlášek', 'type' => 'date', 'date_format' => 'Ymd' ],
			          'course_realization' => [ 'title' => 'Termín realizace kurzu' ],
			          'acreditation_number' => [ 'title' => 'Číslo akreditace' ],
			          'price' => [ 'title' => 'Cena', 'type' => 'price' ],
			          'lesson_count' => [ 'title' => 'Počet lekcí' ],
			          'teacher' => [ 'title' => 'Lektor' ],
			          'students_count' => [ 'title' => 'Kapacita kurzu' ],
			          'signed_students_count' => [ 'title' => 'Přihlášených studentů' ]
		          ] )
		          ->setData( $data )
		          ->setColumnFreeze( 1 )
		          ->setFilename( 'export-kurzu' );

		$generator->writeExcelToPhpOutput();
		exit();

	}

}
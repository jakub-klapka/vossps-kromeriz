<?php

namespace Lumiart\Vosspskm\Courses\Controllers;

use Lumiart\Vosspskm\Courses\App;
use Lumiart\Vosspskm\Courses\AutoloadableInterface;
use Lumiart\Vosspskm\Courses\Models\CoursePost;
use Lumiart\Vosspskm\Courses\SingletonTrait;

class AdminCourseDetailController implements AutoloadableInterface {
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

		add_action( 'current_screen', [ $this, 'maybeAddStudentRowsCollapsingScript' ] );

		foreach( array_keys( $this->app->getConfig()[ 'courses_post_types' ] ) as $slug ) {
			add_action( 'add_meta_boxes_' . $slug, [ $this, 'addBatchActionsMetaBox' ] );
		}

	}

	/**
	 * Find out, if we are on course edit page and inject script to collapse all student rows
	 *
	 * @param \WP_Screen $screen
	 */
	public function maybeAddStudentRowsCollapsingScript( $screen ) {
		if( in_array( $screen->post_type, array_keys( $this->app->getConfig()[ 'courses_post_types' ] ) )
			&& $screen->base === 'post' ) {

			add_action( 'acf/input/admin_footer', [ $this, 'collapseAllStudentRows' ] );
		}
	}

	/**
	 * Echo script for student rows collapsing
	 */
	public function collapseAllStudentRows() {
		?>
		<script type="text/javascript">
			(function($) {
				$(document).ready(function(){
					$('.acf-field-589f5ca97362d .acf-row').addClass( "-collapsed" );
				});
			})(jQuery);
		</script>
		<?php
	}

	/**
	 * Register meta boxes for course edit pages
	 *
	 * @wp-action add_meta_boxes_{$slug}
	 */
	public function addBatchActionsMetaBox() {

		add_meta_box( 'course-batch-actions', 'Hromadné akce', [ $this, 'renderBatchActionsMetabox' ], null, 'normal', 'high' );

	}

	/**
	 * Render meta boxes for Batch Actions on course edit page
	 *
	 * @param \WP_Post $wp_post
	 */
	public function renderBatchActionsMetabox( $wp_post ) {

		$post = new CoursePost( $wp_post->ID );
		$emails = $post->getAllStudentEmails();

		$data = [
			'batch_email_link' => 'mailto:?bcc=' . implode( ',', $emails ),
			'excel_download_link' => trailingslashit( get_bloginfo( 'url' ) ) . 'wp-admin/course-excel-export/' . $post->ID,
		];

		\Timber::render( [ 'course_admin_batch_actions.twig' ], $data );

	}

}
<?php

namespace Lumiart\Vosspskm\Courses\Controllers;

use Lumiart\Vosspskm\Courses\App;
use Lumiart\Vosspskm\Courses\AutoloadableInterface;
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

}
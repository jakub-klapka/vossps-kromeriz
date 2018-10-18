<?php

namespace Lumiart\Vosspskm\Courses\Controllers;

use Lumiart\Vosspskm\App\App;
use Lumiart\Vosspskm\App\AutoloadableInterface;
use Lumiart\Vosspskm\Courses\Models\CoursePost;
use Lumiart\Vosspskm\App\SingletonTrait;

class AdminPostDuplicatorController implements AutoloadableInterface {
	use SingletonTrait;

	/**
	 * @var \Lumiart\Vosspskm\App\App
	 */
	private $app;

	public function __construct( App $app ) {
		$this->app = $app;
	}

	/**
	 * WP Hooks
	 */
	public function boot() {

		add_action( 'post_submitbox_start', [ $this, 'maybeAddButtonToPostDetail' ] );
		add_action( 'post_action_duplicate', [ $this, 'handlePostDuplication' ] );

	}

	/**
	 * Add link for post duplication next to Publish button
	 *
	 * @wp-action post_submitbox_start
	 */
	public function maybeAddButtonToPostDetail() {

		/** @var \WP_Screen $screen */
		$screen = get_current_screen();
		global $pagenow;
		if( $screen->parent_base !== 'edit'
		    || ! in_array( $screen->post_type, array_keys( $this->app->getConfig()[ 'courses_post_types' ] ) )
			|| $pagenow === 'post-new.php' ) return;

		/** @var \WP_Post $post */
		global $post;
		$course_post = new CoursePost( $post->ID );

		echo "<a href=\"{$course_post->getDuplicatePostUrl()}\">Duplikovat kurz</a><div class=\"clear\"></div>";

	}

	/**
	 * Handle reqest to post.php?action=duplicate
	 *
	 * @param $post_id
	 * @wp-aciton post_action_{action}
	 */
	public function handlePostDuplication( $post_id ) {

		$source_post = new CoursePost( $post_id );
		if( ! current_user_can( 'edit_kurzy-' . $source_post->post_type ) ) wp_die( 'Nepovolený přístup' );

		/*
		 * Create post
		 */
		$new_post_id = wp_insert_post( [
			'post_content' => $source_post->post_content,
			'post_title' => $source_post->post_title,
			'post_type' => $source_post->post_type
		] );

		if( $new_post_id === 0 ) wp_die( 'Nepodařilo se duplikovat kurz, prosím kontaktujte administrátora.' );
		$target_post = new CoursePost( $new_post_id );

		/*
		 * Add mandatory metadata
		 */
		$mandatory_fields = [ 'signup_close_date', 'students_count' ];
		foreach( $mandatory_fields as $field_key ) {
			update_field( $field_key, get_field( $field_key, $source_post->ID ), $target_post->ID );
		}

		/*
		 * Optional fields - based on config
		 */
		$optional_field = $this->app->getConfig()[ 'duplicate_post_optional_metadata' ];
		foreach( $optional_field as $field_key ) {
			update_field( $field_key, get_field( $field_key, $source_post->ID ), $target_post->ID );
		}

		/*
		 * Redirect to new post editing screen
		 */
		wp_redirect( admin_url( "post.php?post={$target_post->ID}&action=edit" ) );
		exit();

	}

}
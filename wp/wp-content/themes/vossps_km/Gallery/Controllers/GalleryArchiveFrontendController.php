<?php

namespace Lumiart\Vosspskm\Gallery\Controllers;

use Lumiart\Vosspskm\App\App;
use Lumiart\Vosspskm\App\SingletonTrait;
use Lumiart\Vosspskm\Gallery\Models\GalleryPost;

class GalleryArchiveFrontendController {
	use SingletonTrait;

	/**
	 * @var App
	 */
	private $app;

	public function __construct( $app ) {
		$this->app = $app;
	}

	public function renderArchive() {

		/** @var ScriptStyleController $script_ctrl */
		$script_ctrl = $this->app->make( ScriptStyleController::class );
		$script_ctrl->enqueueGalleryScriptsAndStyles();



		$data = \Timber::get_context();

		$data[ 'breadcrumbs' ] = array_merge( $data['breadcrumbs'], [
			[
				'name' => 'Fotogalerie',
				'url' => 'TODO' //TODO
			]
		] );

		$data['school_years'] = $this->getGalleryGroups();
		$data['gallery_content'] = get_field( 'gallery_content', 'options' );

		\Timber::render( 'gallery_archive.twig', $data );

	}

	/**
	 * Get groups of school years and attached galleries
	 *
	 * @return array
	 */
	private function getGalleryGroups(): array {

		$query = new \WP_Query( [
			'post_type' => 'gallery',
			'nopaging' => true,
			'order'				=> 'DESC',
			'orderby'			=> 'meta_value',
			'meta_key'			=> 'date',
			'meta_type'			=> 'DATETIME'
		] );

		/** @var GalleryPost[] $all_posts */
		$all_posts = array_map( function( \WP_Post $WP_post ): GalleryPost {
			return new GalleryPost( $WP_post );
		}, $query->get_posts() );

		return $this->partitionPostsToSchoolYearGroups( $all_posts );

	}

	/**
	 * Create groups of School years from bunch of posts
	 * Posts with no school years should go to 'root' year. Posts with multiple school years should go to both.
	 *
	 * @param GalleryPost[] $all_posts
	 *
	 * @return array of arrays. School year as a Key and posts as values for those.
	 */
	private function partitionPostsToSchoolYearGroups( array $all_posts ): array {

		$groups = [];

		foreach( $all_posts as $post ) {

			if( \count( $post->getSchoolYearNames() ) === 0 ) {
				$groups['root'][] = $post;
				continue;
			}

			foreach( $post->getSchoolYearNames() as $year_name ) {
				$groups[ $year_name ][] = $post;
			}

		}

		$groups = $this->sortGroups( $groups );

		return $groups;

	}

	/**
	 * Sort school years alphabetically and galeries within by their date field
	 * Root year should go to top as galleries with blank date
	 *
	 * @param array $groups
	 *
	 * @return array
	 */
	private function sortGroups( array $groups ): array {

		$sorted = $groups;

		// Sort group keys
		uksort( $sorted, function( $year1, $year2 ): int {
			if( $year1 === 'root' ) {
				return -1;
			}
			if( $year2 === 'root' ) {
				return 1;
			}
			return strcasecmp( $year1, $year2 ) * -1;
		} );

		// Sort posts within
		$sorted_with_posts = [];
		foreach( $sorted as $school_year => $posts ) {
			usort( $posts, function( $post1, $post2 ) {
				/**
				 * @var GalleryPost $post1
				 * @var GalleryPost $post2
				 */
				if( $post1->getGalleryDate() === null ) {
					return -1;
				}
				if( $post2->getGalleryDate() === null ) {
					return 1;
				}
				if( $post1->getGalleryDate() > $post2->getGalleryDate() ) {
					return -1;
				}
				return 1;
			} );
			$sorted_with_posts[$school_year] = $posts;
		}

		return $sorted_with_posts;

	}

}
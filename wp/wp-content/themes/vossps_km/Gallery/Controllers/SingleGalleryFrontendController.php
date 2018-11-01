<?php

namespace Lumiart\Vosspskm\Gallery\Controllers;

use Lumiart\Vosspskm\App\App;
use Lumiart\Vosspskm\App\SingletonTrait;
use Timber;

class SingleGalleryFrontendController {
	use SingletonTrait;

	/**
	 * @var App
	 */
	private $app;

	public function __construct( $app ) {
		$this->app = $app;
	}

	public function renderSingleGallery() {

		/** @var ScriptStyleController $script_ctrl */
		$script_ctrl = $this->app->make( ScriptStyleController::class );
		$script_ctrl->enqueueGalleryScriptsAndStyles();

		$gallery_post = new \TimberPost();

		$context = Timber::get_context();

		$gallery = get_field( 'photogallery' );
		if( empty( $gallery ) ) {
			$gallery = [];
		}
		$images = [];
		foreach( $gallery as $item ) {
			$images[] = new \TimberImage( $item['ID'] );
		}

		$data = [
			'breadcrumbs' => array_merge( $context['breadcrumbs'], [
				[
					'name' => 'Fotogalerie',
					'url' => get_post_type_archive_link( 'gallery' )
				],
				[
					'name' => $gallery_post->name(),
					'url' => $gallery_post->link()
				]
			] ),
			'gallery' => $images
		];

		Timber::render( 'gallery_single.twig', array_merge( $context, $data ) );

	}

}
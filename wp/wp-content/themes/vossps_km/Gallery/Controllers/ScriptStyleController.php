<?php

namespace Lumiart\Vosspskm\Gallery\Controllers;

use Lumiart\Vosspskm\App\SingletonTrait;

class ScriptStyleController {
	use SingletonTrait;

	public function enqueueGalleryScriptsAndStyles() {

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_ped_gallery' ] );

	}

	public function enqueue_ped_gallery() {
		wp_enqueue_style( 'ped_gallery' );
		wp_enqueue_script( 'ped_gallery' );
	}

}
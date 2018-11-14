<?php

namespace Lumiart\Vosspskm\Gallery\Controllers;

use Lumiart\Vosspskm\App\AutoloadableInterface;
use Lumiart\Vosspskm\App\SingletonTrait;

class RegisterPostTypes implements AutoloadableInterface {
	use SingletonTrait;

	/**
	 * Used in App autoloader
	 * @return void
	 */
	public function boot() {

		add_action( 'init', [ $this, 'registerGalleryPostType' ] );
		add_action( 'init', [ $this, 'registerAcfOptionsPage' ] );

	}

	public function registerGalleryPostType() {

		register_post_type( 'gallery', [
			'labels' => [
				'name'               => 'Galerie',
				'singular_name'      => 'Galerie',
				'menu_name'          => 'Galerie',
				'name_admin_bar'     => 'Galerie',
				'add_new'            => 'Přidat',
				'add_new_item'       => 'Přidat Galerii',
				'new_item'           => 'Nová Galerie',
				'edit_item'          => 'Upravit Galerii',
				'view_item'          => 'Zobrazit Galerii',
				'all_items'          => 'Všechny Galerie',
				'search_items'       => 'Hledat Galerie',
				'parent_item_colon'  => 'Nadřazená Galerie:',
				'not_found'          => 'Galerie nenalezeny.',
				'not_found_in_trash' => 'Galerie nenalezeny ani v koši.',
			],
			'public' => true,
			'menu_position' => 55,
			'menu_icon' => 'dashicons-images-alt2',
			'supports' => [ 'title', 'editor', 'revisions' ],
			'taxonomies' => [ 'school_year' ],
			'has_archive' => true,
			'rewrite' => [
				'slug' => 'galerie',
				'feeds' => false,
				'pages' => false
			]
		] );

		register_taxonomy( 'school_year', [ 'gallery' ], [
			'labels' => [
				'name' => 'Školní roky',
				'singular_name' => 'Školní rok',
                'all_items' => 'Všechny školní roky',
				'edit_item' => 'Upravit školní rok',
				'view_item' => 'Zobrazit školní rok',
				'update_item' => 'Aktualizovat školní rok',
				'add_new_item' => 'Přidat školní rok',
				'new_item_name' => 'Nový školní rok',
				'parent_item' => 'Nadřazený školní rok',
				'parent_item_colon' => 'Nadřazený školní rok:',
				'search_items' => 'Hledat školní roky',
				'popular_items' => 'Oblíbené školní roky',
				'separate_items_with_commas' => 'Oddělete školní roky čárkami',
				'add_or_remove_items' => 'Přidat nebo odebrat školní roky',
				'choose_from_most_used' => 'Vyberte z populárních školních roků',
				'not_found' => 'Školní roky nenalezeny',
				'back_to_items' => 'Zpět na školní roky'
			],
			'hierarchical' => true,
			'publicly_queryable' => false,
			'show_admin_column' => true,
			'rewrite' => false,
			'capabilities' => [
				'manage_terms' => 'manage_school_years',
				'edit_terms' => 'manage_school_years',
				'delete_terms' => 'manage_school_years',
				'assign_terms' => 'edit_posts'
			]
		] );

	}

	/**
	 * Register ACF options page for gallery archive content
	 */
	public function registerAcfOptionsPage(): void {

		if( ! \function_exists( 'acf_add_options_page' ) ) return;

		acf_add_options_page( [
			'page_title' => 'Nastavení galerie',
			'parent_slug' => 'edit.php?post_type=gallery',
			'redirect' => false,
			'update_button' => 'Uložit',
			'updated_message'	=> 'Změny uloženy',
		] );

	}

}
<?php
use Lumiart\Vosspskm\Courses\Controllers\AdminCourseDetailController;
use Lumiart\Vosspskm\Courses\Controllers\AdminListController;
use Lumiart\Vosspskm\Courses\Controllers\Migrations;
use Lumiart\Vosspskm\Courses\Controllers\RegisterPostTypes;

return [

	/*
	 * Classes to load on app bootstrap (to hook into WP, mostly)
	 */
	'autoload_classes' => [
		RegisterPostTypes::class,
		Migrations::class,
	],

	/*
	 * Classes to autoload only on admin request. Hooked to init with prio 5
	 */
	'autoload_on_admin_init' => [
		AdminListController::class,
		AdminCourseDetailController::class
	],

	/*
	 * List of post types attached to courses
	 */
	'courses_post_types' => [
		'kval_predpoklady' => [
			'full_name' => 'Studium ke splnění kvalifikačních předpokladů',
			'short_name' => 'Kvalifikační předpoklady',
			'rewrite_slug' => 'studium-ke-splneni-kvalifikacnich-predpokladu',
		],
		'dalsi_vzdelavani' => [
			'full_name' => 'Další vzdělávání pedagogických pracovníků',
			'short_name' => 'Další vzdělávání',
			'rewrite_slug' => 'dalsi-vzdelavani-pedagogickych-pracovniku',
		],
		'jednotlive_zkousky' => [
			'full_name' => 'Jednotlivé zkoušky',
			'short_name' => 'Jednotlivé zkoušky',
			'rewrite_slug' => 'jednotlive-zkousky',
		],
		'prof_kvalifikace' => [
			'full_name' => 'Profesní kvalifikace',
			'short_name' => 'Profesní kvalifikace',
			'rewrite_slug' => 'profesni-kvalifikace',
		],
		'kurzy_verejnost' => [
			'full_name' => 'Kurzy pro veřejnost',
			'short_name' => 'Kurzy pro veřejnost',
			'rewrite_slug' => 'kurzy-pro-verejnost',
		]
	]

];
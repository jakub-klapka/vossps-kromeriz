<?php
use Lumiart\Vosspskm\Courses\Controllers\RegisterPostTypes;

return [

	/*
	 * Classes to load on app bootstrap (to hook into WP, mostly)
	 */
	'autoload_classes' => [
		RegisterPostTypes::class
	],

	/*
	 * List of post types attached to courses
	 */
	'courses_post_types' => [
		'kval_predpoklady' => [
			'full_name' => 'Studium ke splnění kvalifikačních předpokladů',
			'short_name' => 'Kvalifikační předpoklady'
		],
		'dalsi_vzdelavani' => [
			'full_name' => 'Další vzdělávání pedagogických pracovníků',
			'short_name' => 'Další vzdělávání'
		]
	]

];
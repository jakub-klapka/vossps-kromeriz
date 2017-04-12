<?php
use Lumiart\Vosspskm\Courses\Controllers\AdminCourseCategoryController;
use Lumiart\Vosspskm\Courses\Controllers\AdminCourseDetailController;
use Lumiart\Vosspskm\Courses\Controllers\AdminListController;
use Lumiart\Vosspskm\Courses\Controllers\CourseListController;
use Lumiart\Vosspskm\Courses\Controllers\CourseStudentsExcelExportController;
use Lumiart\Vosspskm\Courses\Controllers\CourseTypesController;
use Lumiart\Vosspskm\Courses\Controllers\MainMenuController;
use Lumiart\Vosspskm\Courses\Controllers\Migrations;
use Lumiart\Vosspskm\Courses\Controllers\RegisterAssets;
use Lumiart\Vosspskm\Courses\Controllers\RegisterPostTypes;
use Lumiart\Vosspskm\Courses\Controllers\UserManagement;

return [

	/*
	 * Classes to load on app bootstrap (to hook into WP, mostly)
	 */
	'autoload_classes' => [
		RegisterPostTypes::class,
		Migrations::class,
		RegisterAssets::class,
		CourseListController::class,
		CourseTypesController::class,
		UserManagement::class,
		MainMenuController::class,
		CourseStudentsExcelExportController::class,
	],

	/*
	 * Classes to autoload only on admin request. Hooked to init with prio 5
	 */
	'autoload_on_admin_init' => [
		AdminListController::class,
		AdminCourseDetailController::class,
		AdminCourseCategoryController::class,
	],

	/*
	 * List of post types attached to courses
	 */
	'courses_post_types' => [
		'kval_predpoklady' => [
			'full_name' => 'Studium ke splnění kvalifikačních předpokladů',
			'short_name' => 'Kvalifikační předpoklady',
			'rewrite_slug' => 'studium-ke-splneni-kvalifikacnich-predpokladu',
			'requires_pin' => true,
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
			'requires_pin' => true,
		],
		'kurzy_verejnost' => [
			'full_name' => 'Kurzy pro veřejnost',
			'short_name' => 'Kurzy pro veřejnost',
			'rewrite_slug' => 'kurzy-pro-verejnost',
		]
	],

	/*
	 * Recaptcha API keys
	 */
	'recaptcha' => [
		'sitekey' => ( defined( 'RECAPTCHA_API_SITEKEY' ) ) ? RECAPTCHA_API_SITEKEY : null,
		'secret' => ( defined( 'RECAPTCHA_API_SECRET' ) ) ? RECAPTCHA_API_SECRET : null,
	],

	/*
	 * Disables menu for public and so on
	 */
	'courses_published' => ( defined( 'LUMI_COURSES_PUBLISHED' ) ) ? LUMI_COURSES_PUBLISHED : false,


];
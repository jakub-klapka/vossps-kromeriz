<?php

use Lumiart\Vosspskm\Gallery\Controllers\RegisterPostTypes;

return [

	/*
	 * Classes to load on app bootstrap (to hook into WP, mostly)
	 */
	'autoload_classes' => [
		RegisterPostTypes::class
	],

	/*
	 * Classes to autoload only on admin request. Hooked to init with prio 5
	 */
	'autoload_on_admin_init' => [
	],

];
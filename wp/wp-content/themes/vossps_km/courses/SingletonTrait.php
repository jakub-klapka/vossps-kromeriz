<?php

namespace Lumiart\Vosspskm\Courses;

/**
 * Class SingletonTrait
 *
 * @package Lumiart\Vosspskm\Courses
 */
trait SingletonTrait {

	/**
	 * Used in App->make()
	 *
	 * @return true
	 */
	public function isSingleton() {
		return true;
	}

}
<?php

namespace Lumiart\Vosspskm\Courses\Controllers;

use Lumiart\Vosspskm\Courses\SingletonTrait;
use Symfony\Component\Form\Forms;

/**
 * Class FormFactory
 *
 * Used as wrapper to get Symfony\FormFactory as singleton via Courses\App
 *
 * @package Lumiart\Vosspskm\Courses\Controllers
 */
class FormFactory {
	use SingletonTrait;

	protected $form_factory;

	public function __construct() {

		$this->form_factory = Forms::createFormFactory();

	}

	/**
	 * Get current instance of FormFactory
	 *
	 * @return \Symfony\Component\Form\FormFactoryInterface
	 */
	public function getFormFactory() {
		return $this->form_factory;
	}

}
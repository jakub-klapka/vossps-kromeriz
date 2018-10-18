<?php

namespace Lumiart\Vosspskm\Courses\Controllers;

use Lumiart\Vosspskm\App\SingletonTrait;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

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

		$validator = Validation::createValidator();

		$this->form_factory = Forms::createFormFactoryBuilder()
		                           ->addExtension( new ValidatorExtension( $validator ) )
		                           ->getFormFactory();

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
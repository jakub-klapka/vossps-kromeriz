<?php

namespace Lumiart\Vosspskm\Courses\Controllers;

use Lumiart\Vosspskm\Courses\AutoloadableInterface;
use Lumiart\Vosspskm\Courses\Models\CoursePost;
use Lumiart\Vosspskm\Courses\SingletonTrait;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;

class FormSubmissionController {

	/**
	 * Handle form submission
	 *
	 * Form is already validated by Symfony create builder
	 *
	 * @param Form $form
	 * @param CoursePost $post
	 */
	public function handle( $form, $post ) {

		$form_data = $form->getData();

		// TODO: captcha

		// Validate request to open course
		if( !$post->isStillSignable() ) {
			$form->addError( new FormError( 'Invalid Request' ) );
			return;
		}

		// Validate unique e-mail
		if( $post->hasSignedUpEmail( $form_data[ 'email' ] ) ) {
			$form->addError( new FormError( 'Tento e-mail je ke kurzu již registrován. Pokud si myslíte, že je to chyba, tak nás prosím kontaktujte.' ) );
			return;
		}

		/*
		 * Validated from here
		 */
		$this->addStudentToPost( $form_data, $post );
		$this->clearCaches( $post );

	}

	/**
	 * Handle mapping between symfony form and CoursePost
	 *
	 * @param array $data
	 * @param CoursePost $post
	 */
	private function addStudentToPost( $data, CoursePost $post ) {

		$payment_subject_map = [
			'self_payment' => 'self',
			'school_payment' => 'school'
		];

		$mapping = [
			'name' => $data[ 'last_name'] . ' ' . $data[ 'first_name' ],
			'degree' => $data[ 'degree' ],
			'email' => $data[ 'email' ],
			'born_place' => $data[ 'birth_place' ],
			'born_date' => $data[ 'birth_date' ]->format( 'Ymd' ),
			'phone' => $data[ 'phone' ],
			'pers_pin' => ( isset( $data[ 'pin' ] ) ) ? $data[ 'pin' ] : null,
			'payment_object' => $payment_subject_map[ $data[ 'payment_subject' ] ],
			'street' => $data[ 'self_payment_street' ],
			'city' => $data[ 'self_payment_city' ],
			'psc' => $data[ 'self_payment_psc' ],
			'school_name' => $data[ 'school_name' ],
			'school_email' => $data[ 'school_email' ],
			'school_ic' => $data[ 'school_ic' ],
			'school_phone' => $data[ 'school_phone' ],
			'school_street' => $data[ 'school_address_street' ],
			'school_city' => $data[ 'school_address_city' ],
			'school_psc' => $data[ 'school_address_psc' ],
			'payment_type' => $data[ 'payment_type' ],
			'invoice_street' => $data[ 'invoice_street' ],
			'invoice_city' => $data[ 'invoice_city' ],
			'invoice_psc' => $data[ 'invoice_psc' ],
			'note' => $data[ 'note' ]
		];

		$post->addStudent( $mapping );

	}

	/**
	 * Clear caches related to form submission
	 *
	 * @param CoursePost $post
	 */
	private function clearCaches( CoursePost $post ) {

		/*
		 * Single Post cache
		 */
		if( function_exists( 'wp_cache_post_change' ) ) {
			wp_cache_post_change( $post->ID );
		}

	}

}
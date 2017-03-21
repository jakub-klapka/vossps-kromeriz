<?php

namespace Lumiart\Vosspskm\Courses\Controllers;

use Lumiart\Vosspskm\Courses\AutoloadableInterface;
use Lumiart\Vosspskm\Courses\Models\CoursePost;
use Lumiart\Vosspskm\Courses\SingletonTrait;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;

class FormSubmissionController {

	/**
	 * Cache for email summary HTML output
	 *
	 * @var null|string
	 */
	protected $email_summary_cache = null;

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
		$this->sendNotificationEmails( $form, $form_data, $post );

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

	/**
	 * Handle sending notification e-mails to student and course administrator
	 *
	 * @param Form $form
	 * @param array $form_data
	 * @param CoursePost $post
	 *
	 * @throws \Exception
	 */
	private function sendNotificationEmails( $form, $form_data, $post ) {

		$student_email_contents = $this->getStudentEmailContents( $form, $form_data, $post );
		$admin_email_contents = $this->getAdminEmailContents( $post, $form, $form_data );

		$headers = array('Content-Type: text/html; charset=UTF-8');

		if( is_email( $form_data[ 'email' ] ) ) {
			wp_mail( $form_data[ 'email' ] , 'Potvrzení online přihlášky ke kurzu ' . $post->post_name, $student_email_contents, $headers );
		}

		$admin_emails = get_field( 'course_admin_emails', $post->ID );
		if( is_array( $admin_emails ) ) {
			$admin_emails = array_map( function ( $field ) {
				return $field[ 'email' ];
			}, $admin_emails );
		}

		if( !empty( $admin_emails ) ) {
			wp_mail( $admin_emails, 'Nový účastník kurzu ' . $post->post_name, $admin_email_contents, $headers );
		}

	}

	/**
	 * Create HTML table of all submitted data
	 *
	 * @param Form $form
	 * @param array $form_data
	 *
	 * @return string
	 */
	private function constructEmailSummaryTable( $form, $form_data ) {

		if( $this->email_summary_cache !== null ) return $this->email_summary_cache;

		$used_fields_data = [];
		foreach( $form_data as $field_name => $value ) {
			if ( empty( $value ) ) continue;

			/*
			 * Format DateTime
			 */
			if( $value instanceof \DateTime ) $value = $value->format( get_option( 'date_format' ) );

			/*
			 * Correct value for choice types
			 */
			if( $form->get( $field_name )->getConfig()->getType()->getInnerType() instanceof ChoiceType ) {
				$value = array_search( $value, $form->get( $field_name )->getConfig()->getOptions()['choices'] );
			}

			/*
			 * Filter out TOS conduct
			 */
			if( $field_name === 'tos_conduct' ) continue;

			$used_fields_data[] = [
				'label' => $form->get( $field_name )->getConfig()->getOptions()['label'],
				'value' => $value
			];

		}

		$this->email_summary_cache = \Timber::compile( '_course_email_table.twig', [ 'fields' => $used_fields_data ] );

		return $this->email_summary_cache;

	}

	/**
	 * Compile contents of student e-mail
	 *
	 * Student e-mail template is different based on payment type
	 *
	 * @param Form $form
	 * @param array $form_data
	 * @param CoursePost $post
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function getStudentEmailContents( $form, $form_data, $post ) {

		$option_name_map = [
			'cash' => 'cash',
			'invoice' => 'invoice'
		];
		if( !in_array( $form_data[ 'payment_type' ], $option_name_map ) ) throw new \Exception( 'Invalid request' );

		$student_email_template = get_field( 'course_option_' . $post->get_post_type()->name . '_student_email_template_' . $option_name_map[ $form_data[ 'payment_type' ] ], 'option' );

		$summary_table_contents = $this->constructEmailSummaryTable( $form, $form_data );

		$student_email = str_replace( '[zadane_udaje]', $this->constructEmailSummaryTable( $form, $form_data ), $student_email_template );

		return $student_email;

	}

	/**
	 * Compile contents for admin e-mail (from hardcoded twig template)
	 *
	 * @param CoursePost $post
	 * @param Form $form
	 * @param array $form_data
	 *
	 * @return string
	 */
	private function getAdminEmailContents( $post, $form, $form_data ) {

		$data = [
			'course_name' => $post->post_name,
			'course_url' => $post->link(),
			'course_summary' => $this->constructEmailSummaryTable( $form, $form_data )
		];

		return \Timber::compile( '_course_email_admin_notification.twig', $data );

	}

}
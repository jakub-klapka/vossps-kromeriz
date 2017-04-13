<?php

namespace Lumiart\Vosspskm\Courses\Models;

use Lumiart\Vosspskm\Courses\Controllers\FormSubmissionController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Lumiart\Vosspskm\Courses\Controllers\FormFactory;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
use TimberPost;

class CoursePost extends TimberPost {

	protected $app;

	public function __construct( $pid = null ) {
		parent::__construct( $pid );

		global $vossps_km_courses_app;
		$this->app = $vossps_km_courses_app;
	}

	/**
	 * Get date of signup close
	 *
	 * @return bool|\DateTime
	 */
	public function getSignupCloseDate() {

		$db_signup_date = $this->getRawField( 'signup_close_date' );
		return \DateTime::createFromFormat( 'Ymd', $db_signup_date );

	}

	/**
	 * Get formated date of signup close according to WP date format
	 *
	 * @return string
	 */
	public function getFormatedSignupCloseDate() {
		$date = $this->getSignupCloseDate();
		return $date->format( get_option( 'date_format' ) );
	}

	/**
	 * Get, if signup date is close to current time
	 *
	 * Threshold is one week from now
	 *
	 * @return bool
	 */
	public function isSignupDateCritical() {
		if( $this->getSignupCloseDate() < ( new \DateTime() )->add( new \DateInterval( 'P1W' ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get free text of realization date
	 *
	 * @return string
	 */
	public function getCourseRealizationDate() {
		return (string)$this->meta( 'course_realization' );
	}

	/**
	 * Get number of course capacity
	 *
	 * @return int
	 */
	public function getCourseCapacity() {
		return (int)$this->meta( 'students_count' );
	}

	/**
	 * Get array with all students
	 *
	 * @return array|null
	 */
	public function getCourseStudents() {
		return get_field( 'course_students', $this->ID );
	}

	/**
	 * Get count of signed students
	 *
	 * @return int
	 */
	public function getSignedStudentsCount() {
		$students = $this->meta( 'course_students' );
		if( !is_array( $students ) ) return 0;
		return count( $students );
	}

	/**
	 * Get remaining free slots for current course
	 *
	 * Floored to 0 for use in templates
	 *
	 * @return int
	 */
	public function getCourseFreePlaces() {
		$real_count = $this->getCourseCapacity() - $this->getSignedStudentsCount();
		return ( $real_count < 0 ) ? 0 : $real_count;
	}

	/**
	 * Get post meta without timber filters
	 *
	 * @param string $field_name
	 * @param bool $single If true, return single value, array otherwise (see get_post_meta())
	 *
	 * @return mixed
	 */
	private function getRawField( $field_name, $single = true ) {
		return get_post_meta( $this->ID, $field_name, $single );
	}

	/**
	 * Gets, if current post is selected as visible
	 *
	 * @return bool
	 */
	public function isVisible() {
		return (bool)$this->meta( 'course_visible' );
	}

	/**
	 * Get course price as string formated to CZK
	 *
	 * @return string|null
	 */
	public function formatCoursePrice() {
		$price = $this->meta( 'price' );
		if( empty( $price ) ) return null;

		$price = (float)$price;
		setlocale(LC_MONETARY, "cs_CZ.UTF-8");
		return str_replace( ',00', ',-', money_format( "%n", $price ) );

	}

	/**
	 * Has the singup date already passed
	 *
	 * @return bool
	 */
	public function isSignupDue() {
		if( $this->getSignupCloseDate() < ( new \DateTime() ) ) return true;
		return false;
	}

	/**
	 * Is it still posible to sign in
	 *
	 * True, if signup date is not due and there are still free places in course
	 *
	 * @return bool
	 */
	public function isStillSignable() {
		if( !$this->isSignupDue() && $this->getCourseFreePlaces() > 0 ) return true;
		return false;
	}

	/**
	 * Get Symfony\Form instance for current post
	 *
	 * @return \Symfony\Component\Form\FormInterface
	 */
	public function getCourseForm() {
		$form_factory = $this->app->make( FormFactory::class )->getFormFactory();/** @var \Symfony\Component\Form\FormFactory $form_factory */

		$school_constraints = [];
		$self_constraints = [];
		if( isset( $_REQUEST[ 'form' ][ 'payment_subject' ] ) && $_REQUEST[ 'form' ][ 'payment_subject' ] === 'school_payment' ) {
			$school_constraints = [ new NotBlank() ]; //One instance is enough for this case
		}
		if( isset( $_REQUEST[ 'form' ][ 'payment_subject' ] ) && $_REQUEST[ 'form' ][ 'payment_subject' ] === 'self_payment' ) {
			$self_constraints = [ new NotBlank() ]; //One instance is enough for this case
		}

		/** @var Form $form */
		$form = $form_factory->createBuilder()->setAction( $this->link() )->getForm();

		$form->add( 'first_name', TextType::class, [ 'label' => 'Jméno', 'constraints' => [ new NotBlank() ] ] )
		     ->add( 'last_name', TextType::class, [ 'label' => 'Příjmení', 'constraints' => [ new NotBlank() ] ] )
		     ->add( 'degree', TextType::class, [ 'label' => 'Titul', 'required' => false ] )
		     ->add( 'email', EmailType::class, [ 'label' => 'E-mail', 'constraints' => [ new NotBlank(), new Email() ] ] )
		     ->add( 'birth_place', TextType::class, [ 'label' => 'Místo narození', 'constraints' => [ new NotBlank() ] ] )
		     ->add( 'birth_date', DateType::class, [ 'label' => 'Datum narození', 'widget' => 'single_text', 'constraints' => [ new NotBlank(), new Date() ] ] )
		     ->add( 'phone', TextType::class, [ 'label' => 'Telefon', 'constraints' => [ new NotBlank() ] ] );

		/*
		 * PIN is required only in some categories
		 */
		if( isset( $this->app->getConfig()[ 'courses_post_types' ][ $this->get_post_type()->name ][ 'requires_pin' ] )
		    && $this->app->getConfig()[ 'courses_post_types' ][ $this->get_post_type()->name ][ 'requires_pin' ] === true ) {

			$form->add( 'pin', TextType::class, [ 'label' => 'Rodné číslo', 'constraints' => [ new NotBlank() ] ] );

		}

		$form->add( 'payment_subject', ChoiceType::class, [
				'label' => 'Plátce kurzovného',
				'choices' => [
					'Samoplátce' => 'self_payment',
					'Hrazeno zaměstnavatelem (školou)' => 'school_payment'
				],
				'expanded' => true,
				'multiple' => false,
				'choice_attr' => function( $val, $key, $index ) {
					$map = [
						'self_payment' => 'samoplatce',
						'school_payment' => 'zamestnavatel'
					];
					return [ 'data-form-switching-target' => $map[ $index ] ];
				},
				'constraints' => [ new NotBlank() ]
			] )
			->add( 'school_name', TextType::class, [ 'label' => 'Název školy', 'attr' => [ 'data-required' => 'data-required' ], 'constraints' => $school_constraints ] )
			->add( 'school_ic', TextType::class, [ 'label' => 'IČ školy', 'attr' => [ 'data-required' => 'data-required' ], 'constraints' => $school_constraints ] )
			->add( 'school_email', EmailType::class, [ 'label' => 'E-mail školy', 'attr' => [ 'data-required' => 'data-required' ], 'constraints' => $school_constraints ] )
			->add( 'school_phone', TextType::class, [ 'label' => 'Telefon školy', 'attr' => [ 'data-required' => 'data-required' ], 'constraints' => $school_constraints ] )
			->add( 'school_address_street', TextType::class, [ 'label' => 'Ulice', 'attr' => [ 'data-required' => 'data-required' ], 'constraints' => $school_constraints ] )
			->add( 'school_address_city', TextType::class, [ 'label' => 'Město', 'attr' => [ 'data-required' => 'data-required' ], 'constraints' => $school_constraints ] )
			->add( 'school_address_psc', TextType::class, [ 'label' => 'PSČ', 'attr' => [ 'data-required' => 'data-required' ], 'constraints' => $school_constraints ] )
			->add( 'self_payment_street', TextType::class, [ 'label' => 'Ulice', 'attr' => [ 'data-required' => 'data-required' ], 'constraints' => $self_constraints ] )
			->add( 'self_payment_city', TextType::class, [ 'label' => 'Město', 'attr' => [ 'data-required' => 'data-required' ], 'constraints' => $self_constraints ] )
			->add( 'self_payment_psc', TextType::class, [ 'label' => 'PSČ', 'attr' => [ 'data-required' => 'data-required' ], 'constraints' => $self_constraints ] )
			->add( 'payment_type', ChoiceType::class, [
				'label' => 'Způsob platby',
				'choices' => [
					'Hotově' => 'cash',
					'Fakturou / Převodem na účet' => 'invoice'
				],
				'expanded' => true,
				'multiple' => false,
				'choice_attr' => function( $val, $key, $index ) {
					if( $index === 'invoice' ) {
						return [ 'data-form-switching-target' => 'faktura2' ];
					}
					return [];
				},
				'constraints' => [ new NotBlank() ]
			] )
			->add( 'invoice_street', TextType::class, [ 'label' => 'Ulice' ] )
			->add( 'invoice_city', TextType::class, [ 'label' => 'Město' ] )
			->add( 'invoice_psc', TextType::class, [ 'label' => 'PSČ' ] )
			->add( 'tos_conduct', RadioType::class, [ 'required' => true, 'constraints' => new IsTrue() ] )
			->add( 'note', TextareaType::class );

		$form->handleRequest();

		if( $form->isSubmitted() && $form->isValid() ) {
			$this->app->make( FormSubmissionController::class )->handle( $form, $this );
		}

		return $form;

	}

	/**
	 * Add new student to this course
	 *
	 * Expecting already validated and sanitized data
	 *
	 * @param array $student_args
	 */
	public function addStudent( $student_args ) {

		$students = get_field( 'course_students', $this->ID );
		$students = ( is_array( $students ) ) ? $students : [];

		$students[] = $student_args;
		update_field( 'course_students', $students, $this->ID ) ;

	}

	/**
	 * Get if given e-mail already exist in course students
	 *
	 * @param string $email
	 *
	 * @return bool
	 */
	public function hasSignedUpEmail( $email ) {
		$signed_students = get_field( 'course_students', $this->ID );

		if( empty( $signed_students) ) return false;

		foreach( $signed_students as $student ) {
			if( $student[ 'email' ] === $email ) return true;
		}

		return false;
	}

	/**
	 * Returns array of all student e-mails. Empty array when there is no student.
	 *
	 * @return array
	 */
	public function getAllStudentEmails() {

		$students = get_field( 'course_students', $this->ID );

		$emails = [];
		foreach( $students as $student ) {
			if( !empty( $student[ 'email' ] ) && is_email( $student[ 'email' ] ) ) $emails[] = $student[ 'email' ];
		}

		return $emails;

	}

	/**
	 * Generate PHPExcel object with students data
	 *
	 * @return \PHPExcel
	 */
	public function generateStudentsExcel() {

		\PHPExcel_Settings::setLocale( 'cs' );
		$excel = new \PHPExcel();
		$sheet = $excel->getActiveSheet();
		$sheet->setTitle( 'Studenti kurzu ' . $this->post_title );

		$fields_mapping = [
			'name' => [ 'title' => 'Jméno' ],
			'degree' => [ 'title' => 'Titul' ],
			'email' => [ 'title' => 'E-mail' ],
			'born_place' => [ 'title' => 'Místo narození' ],
			'born_date' => [ 'title' => 'Datum narození', 'type' => 'date' ],
			'phone' => [ 'title' => 'Telefon' ],
			'pers_pin' => [ 'title' => 'Osobní číslo' ],
			'payment_object' => [ 'title' => 'Plátce kurzovného', 'select' => [ 'self' => 'Samoplátce', 'school' => 'Škola' ] ],
			'street' => [ 'title' => 'Účastník - Ulice' ],
			'city' => [ 'title' => 'Účastník - Město' ],
			'psc' => [ 'title' => 'Účastník - PSČ' ],
			'school_name' => [ 'title' => 'Název školy' ],
			'school_email' => [ 'title' => 'E-mail školy' ],
			'school_ic' => [ 'title' => 'IČ' ],
			'school_phone' => [ 'title' => 'Telefon školy' ],
			'school_street' => [ 'title' => 'Škola - Ulice' ],
			'school_city' => [ 'title' => 'Škola - Město' ],
			'school_psc' => [ 'title' => 'Škola - PSČ' ],
			'payment_type' => [ 'title' => 'Způsob plabty', 'select' => [ 'cash' => 'Hotově', 'invoice' => 'Fakturou' ] ],
			'invoice_street' => [ 'title' => 'Fakturace - Ulice' ],
			'invoice_city' => [ 'title' => 'Fakturace - Město' ],
			'invoice_psc' => [ 'title' => 'Fakturace - PSČ' ],
			'note' => [ 'title' => 'Poznámka', 'type' => 'html' ],
		];

		// TODO: it's working! but refactor it for gods sake. Without wine this time...

		/*
		 * Create header
		 */
		$i = 0;
		foreach( $fields_mapping as $key => $attributes ) {
			$cell = $sheet->setCellValueByColumnAndRow( $i, 1, $attributes[ 'title' ], true );
			$cell->getStyle()->applyFromArray( [
				'borders' => [ 'outline' => [ 'style' => \PHPExcel_Style_Border::BORDER_MEDIUM ] ],
				'fill' => [ 'type' => \PHPExcel_Style_Fill::FILL_SOLID, 'color' => [ 'rgb' => 'D3D3D3' ] ],
				'font' => [ 'bold' => true ]
			] );
			$i++;
		}

		/*
		 * Fill students
		 */
		$students = $this->getCourseStudents();
		$row = 1;
		foreach( $students as $student ) {
			$row++; //Starting at 2

			$column = -1;
			foreach( $fields_mapping as $key => $attributes ) {
				$column++; // Starting at 0

				if( isset( $attributes[ 'type' ] ) && $attributes[ 'type' ] === 'date' ) {
					$date_string = $student[ $key ];

					if( empty( $date_string ) ) {
						$cell = $sheet->setCellValueByColumnAndRow( $column, $row, '', true );
					} else {
						$date = \DateTime::createFromFormat( 'Ymd', $date_string )->setTime( 0, 0, 0 );
						$cell = $sheet->setCellValueByColumnAndRow( $column, $row, \PHPExcel_Shared_Date::PHPToExcel( $date ), true );
						$cell->getStyle()->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY );
					}

				} elseif( isset( $attributes[ 'select' ] ) ){

					$value = $student[ $key ];
					$cell = $sheet->setCellValueByColumnAndRow( $column, $row, $attributes[ 'select' ][ $value ], true );

				} elseif( isset( $attributes[ 'type' ] ) && $attributes[ 'type' ] === 'html' ) {

					$value = strip_tags( preg_replace( '/\s$/', '', $student[ $key ]) ); // Strip last newline character, which is always there
					$cell = $sheet->setCellValueByColumnAndRow( $column, $row, $value, true );
					$cell->getStyle()->getAlignment()->setWrapText( true );

				} else {

					$cell = $sheet->setCellValueByColumnAndRow( $column, $row, $student[ $key ], true );

				}

				$border = [ 'style' => \PHPExcel_Style_Border::BORDER_THIN ];
				$cell->getStyle()->applyFromArray( [
					'borders' => [
						'left' => $border,
						'bottom' => $border,
						'right' => $border
					]
				] );

			}

		}

		/*
		 * Set autosize columns
		 */
		for( $i = 0; $i < count( $fields_mapping ); $i++ ) {
			$sheet->getColumnDimensionByColumn( $i )->setAutoSize( true );
		}

		/*
		 * Set column freeze
		 */
		$sheet->freezePaneByColumnAndRow( 0, 2 );

		return $excel;

	}

}
<?php

namespace Lumiart\Vosspskm\Courses\Models;

use TimberPost;

class CoursePost extends TimberPost {

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

}
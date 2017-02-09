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
	 * Get remaining free slots for current course
	 *
	 * TODO: maybe floor it to 0, so admins can add students above limit and it will still show 0...
	 *
	 * @return int
	 */
	public function getCourseFreePlaces() {
		//TODO: implement
		return 5;
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

}
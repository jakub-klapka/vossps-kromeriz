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
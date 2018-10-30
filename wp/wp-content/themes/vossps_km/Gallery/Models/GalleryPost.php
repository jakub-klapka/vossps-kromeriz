<?php

namespace Lumiart\Vosspskm\Gallery\Models;

class GalleryPost {

	/**
	 * @var \WP_Post
	 */
	private $wp_post;

	public function __construct( \WP_Post $WP_post ) {
		$this->wp_post = $WP_post;
	}

	/**
	 * Get date from 'date' custom field
	 *
	 * @return \DateTimeImmutable|null
	 */
	public function getGalleryDate(): ?\DateTimeImmutable {
		$field = get_field( 'date', $this->wp_post->ID, false );

		if( empty( $field ) ) {
			return null;
		}

		return \DateTimeImmutable::createFromFormat( 'Ymd', $field );
	}

	/**
	 * Get array of string of attached school years
	 *
	 * @return string[]
	 */
	public function getSchoolYearNames(): array {

		$wp_terms = wp_get_post_terms( $this->wp_post->ID, 'school_year' );

		return array_map( function( \WP_Term $term ): string {
			return $term->name;
		}, $wp_terms );

	}

	/**
	 * Get underlying WP_Post
	 *
	 * @return \WP_Post
	 */
	public function getWpPost(): \WP_Post {
		return $this->wp_post;
	}

}
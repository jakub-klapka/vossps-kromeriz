<?php

namespace Lumiart\Vosspskm\Courses\Controllers;

use Lumiart\Vosspskm\App\App;
use Lumiart\Vosspskm\App\AutoloadableInterface;
use Lumiart\Vosspskm\App\SingletonTrait;

class AdminCourseCategoryController implements AutoloadableInterface {
	use SingletonTrait;

	protected $app;

	public function __construct( App $app ) {
		$this->app = $app;
	}

	/**
	 * Bind actions
	 */
	public function boot() {

		add_action( 'admin_menu', [ $this, 'registerCategoryOptionsAcfPages' ] );
		add_filter( 'acf/location/rule_values/options_page', [ $this, 'addOptionToLocationRules' ] );
		add_action( 'init', [ $this, 'createOptionFieldGroups'] );

	}

	/**
	 * Create ACF settings page for each custom post type
	 *
	 * @wp-action admin_menu
	 */
	public function registerCategoryOptionsAcfPages() {

		if( function_exists('acf_add_options_page') ) {

			$courses_slugs = array_keys( $this->app->getConfig()[ 'courses_post_types' ] );

			foreach( $courses_slugs as $slug ) {

				acf_add_options_page( [
					'title' => 'Nastavení kurzů',
					'menu_slug' => "{$slug}_course_options",
					'capability' => "edit_kurzy-{$slug}",
					'parent_slug' => "edit.php?post_type={$slug}",
				] );

			}

		}

	}

	/**
	 * Add custom rule to ACF location rules.
	 * This rule will allways match to false. We will use it's field groups only as bootstrap for others.
	 *
	 * @wp-action acf/location/rule_values/options_page
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public function addOptionToLocationRules( $options ) {
		$options[ 'any_course_options' ] = 'Prázdné nastavení';
		return $options;
	}

	/**
	 * Take bootstrap field group (which has no matching location rule) and create another field group for each CPT.
	 * This way, all courses Option pages are controlled by this one ACF field group, but all of them will have
	 * different names and IDs, thus ACF will save each into it's own settings.
	 *
	 * @wp-action init
	 */
	public function createOptionFieldGroups() {

		$template_field_group_definition = acf_get_field_group('group_58c8383cb9445');
		$template_field_definition = acf_get_local_fields('group_58c8383cb9445');

		$post_types = array_keys( $this->app->getConfig()[ 'courses_post_types' ] );

		foreach( $post_types as $slug ) {

			$new_field_group = $template_field_group_definition;
			$new_field_group[ 'key' ] = 'group_course_options_' . $slug;
			$new_field_group[ 'title' ] = $template_field_group_definition[ 'title' ] . ' - ' . $this->app->getConfig()[ 'courses_post_types' ][ $slug ][ 'short_name' ];
			$new_field_group[ 'location' ] = [ [ [ 'param' => 'options_page', 'operator' => '==', 'value' => $slug . '_course_options' ] ] ];

			$new_fields = [];
			foreach( $template_field_definition as $field ) {
				$field[ 'key' ] = $field[ 'key' ] . '_' . $slug;
				if( !empty( $field[ 'name' ] ) ) {
					// Count with tab fields, which have empty name
					$field[ 'name' ] = 'course_option_' . $slug . '_' . $field[ 'name' ];
				}
				unset( $field[ 'parent' ] );
				$new_fields[] = $field;
			}

			$new_field_group[ 'fields' ] = $new_fields;
			acf_add_local_field_group( $new_field_group );

		}

	}

}
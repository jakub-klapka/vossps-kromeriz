<?php

namespace Lumiart\Vosspskm\Courses\Controllers;

use Lumiart\Vosspskm\Courses\App;
use Lumiart\Vosspskm\Courses\AutoloadableInterface;
use Lumiart\Vosspskm\Courses\SingletonTrait;

class Migrations implements AutoloadableInterface {
	use SingletonTrait;

	/** @var App  */
	protected $app;

	public function __construct( App $app ) {
		$this->app = $app;
	}

	/**
	 * Register migrate route
	 */
	public function boot() {

		\Routes::map( 'wp-admin/migrate', [ $this, 'maybeRunMigrations' ] );

	}

	/**
	 * Check for user privileges and run migrations, if he has 'manage_options'
	 */
	public function maybeRunMigrations() {

		if( current_user_can( 'manage_options' ) ) {

			$result = $this->runMigrations();

			wp_die( $result );

		} else {
			wp_die( 'Nepovolený přístup' );
		}

	}

	/**
	 * Run required migrations
	 *
	 * @return string
	 */
	public function runMigrations() {

		$result = '';
		$schema_version = get_option( 'theme_schema_version' );

		if( $schema_version == false || $schema_version < 1 ) {
			$result .= $this->migration_1();
			$schema_version = 1;
		};

		update_option( 'theme_schema_version', $schema_version, false );
		return $result;

	}

	/**
	 * Migration 1
	 *
	 * Add courses capabilities to superadmin and admin
	 *
	 * @return string
	 */
	private function migration_1() {

		$result = '';

		//Construct all caps
		$cpt_slugs = array_keys( $this->app->getConfig()[ 'courses_post_types' ] );
		$required_cap_prefixes = [ 'edit_', 'read_', 'delete_', 'edit_others_', 'publish_', 'read_private_', 'create_' ];
		$all_required_caps = [];

		foreach( $cpt_slugs as $cpt ) {
			foreach ( $required_cap_prefixes as $required_cap_prefix ) {
				$all_required_caps[] = $required_cap_prefix . 'kurzy-' . $cpt;
			}
		}

		$admin_roles = [ get_role( 'administrator' ), get_role( 'admin' ) ];

		/** @var \WP_Role $role */
		foreach( $admin_roles as $role ) {
			foreach( $all_required_caps as $required_cap ) {
				$result .= 'Adding ' . $required_cap . ' to ' . $role->name . '<br/>';
				$role->add_cap( $required_cap );
			}
		}

		return $result;

	}

}
<?php

namespace Lumiart\Vosspskm\Courses\Controllers;

use Lumiart\Vosspskm\App\App;
use Lumiart\Vosspskm\App\AutoloadableInterface;
use Lumiart\Vosspskm\App\SingletonTrait;

class Migrations implements AutoloadableInterface {
	use SingletonTrait;

	/** @var \Lumiart\Vosspskm\App\App  */
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
			$result .= $this->clearCaches();

			wp_die( $result );

		} else {
			wp_die( 'Nepovolený přístup' );
		}

	}

	private function clearCaches() {
		$out = '';
		if( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
			$out .= 'WP Super cache cleared.<br/>';
		}

		$cache_path = ABSPATH . 'wp-content/plugins/timber-library/cache/twig';
		if( is_dir( $cache_path ) ) {
			static::rrmdir( $cache_path );
			$out .= 'Twig cache cleared.<br/>';
		}

		if( class_exists( 'BWP_MINIFY' ) ) {
			$bwp = new \BWP_MINIFY();

			$deleted = 0;
			$cache_dir = !empty($cache_dir) ? $cache_dir : $bwp->get_cache_dir();
			$cache_dir = trailingslashit($cache_dir);

			if (is_dir($cache_dir))
			{
				if ($dh = opendir($cache_dir))
				{
					while (($file = readdir($dh)) !== false)
					{
						if (preg_match('/^minify_[a-z0-9\\.=_,]+(\.gz)?$/ui', $file)
						    || preg_match('/^minify-b\d+-[a-z0-9-_.]+(\.gz)?$/ui', $file)
						) {
							$deleted += true === @unlink($cache_dir . $file)
								? 1 : 0;
						}
					}
					closedir($dh);
				}
			}
			return $out .= 'Deleted: ' . $deleted . ' BWP minify files.<br/>';
		}

		return $out;
	}

	public static function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir."/".$object) == "dir") self::rrmdir($dir."/".$object); else unlink($dir."/".$object);
				}
			}
			reset($objects);
			rmdir($dir);
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

		if( $schema_version < 2 ) {
			$result .= $this->migration_2();
			$schema_version = 2;
		}

		if( $schema_version < 3 ) {
			$result .= $this->migration_3();
			$schema_version = 3;
		}

		if( $schema_version < 4 ) {
			$result .= $this->migration_4();
			$schema_version = 4;
		}

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

	/**
	 * Flush rewrite rules, as we are adding new rewrite tag for types page
	 *
	 * @return string
	 */
	private function migration_2() {
		update_option( 'rewrite_rules', '' );
		flush_rewrite_rules();
		return 'Migration 2 - rewrite rules flushed.<br/>';
	}

	/**
	 * Add new course-related user roles
	 */
	private function migration_3() {

		$return = '';

		foreach( $this->app->getConfig()['courses_post_types'] as $slug => $attrs ) {

			$caps = [
				'read' => true,
				'upload_files' => true,
				'edit_kurzy-' . $slug => true,
				'edit_others_kurzy-' . $slug => true,
				'publish_kurzy-' . $slug => true,
				'read_kurzy-' . $slug => true,
				'delete_kurzy-' . $slug => true,
				'publish_kurzy-' . $slug => true,
				'create_kurzy-' . $slug => true,
				'read_private_kurzy-' . $slug => true
			];

			$return .= 'Adding role: ' . 'course_admin-' . $slug . ' (' . 'Správce kurzů - ' . $attrs[ 'short_name' ] . ')<br/>';
			add_role( 'course_admin-' . $slug, 'Správce kurzů - ' . $attrs[ 'short_name' ], $caps );

		}

		return $return;

	}

	/**
	 * Flush rewrite rules, as we are adding new rewrite tag for types page
	 *
	 * @return string
	 */
	private function migration_4() {
		update_option( 'rewrite_rules', '' );
		flush_rewrite_rules();
		return 'Migration 4 - rewrite rules flushed.<br/>';
	}

}
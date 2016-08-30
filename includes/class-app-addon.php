<?php

class Appointments_Addon {

	public $headers = array();

	private static $default_headers = array(
		'PluginName' => 'Plugin Name',
		'Description' => 'Description',
		'PluginURI' => 'Plugin URI',
		'Version' => 'Version',
		'AddonType' => 'AddonType',
		'Author' => 'Author',
		'Requires' => 'Requires',
		'Free' => 'Free'
	);

	public $slug = '';

	public $addon_file;

	public $is_beta = false;

	public $error = false;

	public $active = false;

	public function __construct( $addon_file ) {
		if ( ! is_readable( $addon_file ) ) {
			$this->error = true;
			return;
		}

		$this->addon_file = $addon_file;
		$this->slug = basename( $addon_file, '.php' );

		$this->headers = get_file_data( $this->addon_file, self::$default_headers );

		$appointments = appointments();
		$this->active = in_array( $this->slug, $appointments->addons_loader->get_active_addons() );
	}

	public function __get( $name ) {
		if ( isset( $this->headers[ $name ] ) ) {
			if ( 'Requires' == $name ) {
				if ( ! strlen( trim( $this->headers[ $name ] ) ) ) {
					return array();
				}
				$requires = explode( ',', trim( $this->headers[ $name ] ) );
				if ( ! is_array( $requires ) ) {
					return array();
				}
				else {
					return $requires;
				}
			}
			else {
				return $this->headers[ $name ];
			}

		}

		return '';
	}

	public static function activate_addon( $slug ) {
		$appointments = appointments();
		$addon  = self::get_addon( $slug );
		if ( $addon->Free ) {
			return;
		}
		$active = $appointments->addons_loader->get_active_addons();
		if ( $addon && ! in_array( $slug, $active ) ) {
			$active[] = $addon->slug;

			// Activate dependencies too
			if ( $addon->Requires ) {
				$requires = $addon->Requires;
				foreach ( $requires as $required ) {
					$required_addon = self::get_addon_by_name( $required );
					if ( $required_addon && ! $required_addon->active ) {
						$active[] = $required_addon->slug;
					}
				}
			}
			appointments_clear_cache();
			update_option( 'app_activated_plugins', $active );
		}
	}

	public static function deactivate_addon( $slug ) {
		$appointments = appointments();
		$addon  = self::get_addon( $slug );
		$active = $appointments->addons_loader->get_active_addons();
		if ( $addon && in_array( $slug, $active ) ) {
			$key = array_search( $slug, $active );
			unset( $active[ $key ] );
			appointments_clear_cache();

			update_option( 'app_activated_plugins', $active );
		}
	}

	/**
	 * @param $slug
	 *
	 * @return bool|Appointments_Addon
	 */
	public static function get_addon( $slug ) {
		$appointments = appointments();
		$all = $appointments->addons_loader->get_addons();
		$filtered = wp_list_filter( $all, array( 'slug' => $slug ) );
		if ( $filtered ) {
			$addon = current( $filtered );
			if ( ! $addon->error ) {
				return $addon;
			}
		}

		return false;
	}

	public static function get_addon_by_name( $name ) {
		$appointments = appointments();
		$all = $appointments->addons_loader->get_addons();
		foreach ( $all as $addon ) {
			if ( $addon->PluginName === $name ) {
				return $addon;
			}
		}
		return false;
	}


}
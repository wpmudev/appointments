<?php

class Appointments_Addons_Loader {

	/**
	 * Save the loaded addons
	 * @var array
	 */
	private $loaded_addons;

	/**
	 * Saves the list of all addons (activated or not)
	 * @var array
	 */
	private $addons;

	private static $instance;

	private function __construct() {

	}

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return the list of all loaded addons
	 *
	 * @return array
	 */
	public function get_loaded_addons() {
		return $this->loaded_addons;
	}

	public function get_loaded_addon( $slug ) {
		if ( $this->is_addon_loaded( $slug ) ) {
			return $this->loaded_addons[ $slug ];
		}

		return false;
	}

	/**
	 * Return the list of loaded addons
	 *
	 * @return mixed|void
	 */
	public function get_active_addons() {
		return get_option('app_activated_plugins', array());
	}

	/**
	 * Return true if an addon is active
	 *
	 * @param $slug
	 *
	 * @return bool
	 */
	public function is_addon_loaded( $slug ) {
		return isset( $this->loaded_addons[ $slug ] );
	}

	/**
	 * Scan for addons
	 */
	public function get_addons() {
		if ( ! is_array( $this->addons ) ) {
			$all = glob( APP_PLUGIN_ADDONS_DIR . '/*.php' );
			$addons = array();
			foreach ( $all as $addon_file ) {
				$addon = new Appointments_Addon( $addon_file );
				if ( ! $addon->error ) {
					$addons[ $addon_file ] = $addon;
				}

			}
			$this->addons = $addons;
		}

		/**
		 * Filter the list of found addons (active or not)
		 *
		 * @param array $addons
		 */
		return apply_filters( 'appointments_addons', $this->addons );
	}

	/**
	 * Load all active addons
	 */
	public function load_active_addons() {
		$this->get_addons();
		$this->loaded_addons = array();
		$active_addons = $this->get_active_addons();
		foreach ( $active_addons as $_addon ) {
			$addon = Appointments_Addon::get_addon( $_addon );
			if ( $addon ) {
				$this->loaded_addons[ $addon->slug ] = $addon;
				require_once($addon->addon_file);
			}
		}
	}
}
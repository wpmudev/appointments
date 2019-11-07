<?php

/**
 * The class that manages Premium features
 *
 * Class Appointments_Pro
 */
class Appointments_Pro {

	public $google_login;

	public $integrations = array();
	
	public function __construct() {
		$this->includes();

		// Login with Google
		$this->google_login = new App_Appointments_Google_Login();

		add_action( 'plugins_loaded', array( $this, 'load_integrations' ), 999 );

		add_filter( 'appointments_addons', array( $this, 'load_extra_addons' ) );

		add_filter( 'appointments_before_insert_service', '__return_true' );
		add_filter( 'appointments_before_insert_worker', '__return_true' );

		add_filter( 'appointments_default_options', array( $this, 'appointments_default_options' ) );

	}

	/**
	 * Por version default options
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public function appointments_default_options( $options ) {
		// In PRO version we don't want to keep the data in case of plugin uninstall by default
		$options['keep_options_on_uninstall'] = false;
		return $options;
	}

	private function includes() {
		include_once( appointments_plugin_dir() . 'includes/pro/includes/class-app-google-login.php' );
	}

	public function load_integrations() {
		// Integrations
	}

	/**
	 * Loads extra addons from includes/pro/includes/addons
	 *
	 * @param $addons
	 *
	 * @return array
	 */
	public function load_extra_addons( $addons ) {
		$all = glob( appointments_plugin_dir() . 'includes/pro/includes/addons/*.php' );
		foreach ( $all as $addon_file ) {
			$addon = new Appointments_Addon( $addon_file );
			if ( ! $addon->error ) {
				$addons[ $addon->slug ] = $addon;
			}
		}
		return $addons;
	}
}
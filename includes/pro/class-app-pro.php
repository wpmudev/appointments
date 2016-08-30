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
		$this->google_login = new Appointments_Google_Login();

		add_action( 'plugins_loaded', array( $this, 'load_integrations' ), 999 );

		add_filter( 'appointments_addons', array( $this, 'load_extra_addons' ) );

		add_filter( 'appointments_before_insert_service', '__return_true' );

	}

	private function includes() {
		include_once( appointments_plugin_dir() . 'includes/pro/includes/class-app-google-login.php' );

		// Other plugins/themes integrations
		include_once( appointments_plugin_dir() . 'includes/pro/integrations/class-app-marketpress.php' );
	}

	public function load_integrations() {
		// Integrations
		$this->integrations['marketpress'] = new Appointments_Integrations_MarketPress();
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
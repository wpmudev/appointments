<?php

/**
 * The class that manages Premium features
 *
 * Class Appointments_Pro
 */
class Appointments_Pro {

	public $google_login;
	
	public function __construct() {
		$this->includes();

		// Login with Google
		$this->google_login = new Appointments_Google_Login();
	}

	private function includes() {
		include_once( appointments_plugin_dir() . 'includes/pro/includes/class-app-google-login.php' );
	}
}
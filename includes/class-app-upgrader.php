<?php

/**
 * Class Appointments_Upgrader
 *
 * Manage the upgrades in Appointments +
 */
class Appointments_Upgrader {

	public function __construct() {

	}

	public function upgrade( $version ) {
		$version_slug = str_replace( array( '.', '-' ), '_', $version );
		if ( method_exists( $this, 'upgrade_' . $version_slug ) ) {
			call_user_func( array( $this, 'upgrade_' . $version_slug ) );
		}
	}

	private function upgrade_1_7() {
		$admin_notices = get_option( 'app_admin_notices', array() );
		if ( isset( $admin_notices['1-7-gcal'] ) ) {
			return;
		}

		$admin_notices['1-7-gcal'] = array(
			'cap' => App_Roles::get_capability( 'manage_options', App_Roles::CTX_PAGE_APPOINTMENTS )
		);

		update_option( 'app_admin_notices', $admin_notices );
	}
}
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

	private function upgrade_1_7_1() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$max_index_length = 191;

		$appmeta = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}app_appointmentmeta` (
  meta_id bigint(20) unsigned NOT NULL auto_increment,
  app_appointment_id bigint(20) unsigned NOT NULL default '0',
  meta_key varchar(255) default NULL,
  meta_value longtext,
  PRIMARY KEY  (meta_id),
  KEY app_appointment_id (app_appointment_id),
  KEY meta_key (meta_key($max_index_length))
) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$wpdb->query( $appmeta );

		// Move the Apps data to meta
		$data = get_option( 'appointments_data', array() );
		foreach ( $data as $app_id => $additional_fields ) {
			if ( appointments_get_appointment( $app_id ) ) {
				appointments_update_appointment_meta( $app_id, 'additional_fields', $additional_fields );
			}
		}
	}
}
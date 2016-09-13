<?php

/**
 * Class Appointments_Upgrader
 *
 * Manage the upgrades in Appointments +
 */
class Appointments_Upgrader {

	public function upgrade( $saved_version, $new_version ) {

		if ( version_compare( $saved_version, '1.7', '<' ) ) {
			$this->upgrade_1_7();
		}

		if ( version_compare( $saved_version, '1.7.1', '<' ) ) {
			$this->upgrade_1_7_1();
		}

		if ( version_compare( $saved_version, '1.7.2-beta1', '<' ) ) {
			$this->upgrade_1_7_2_beta1();
		}

		if ( version_compare( $saved_version, '1.9.4', '<' ) ) {
			$this->upgrade_1_9_4();
		}

		update_option( 'app_db_version', $new_version );

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

	private function upgrade_1_7_2_beta1() {
		$appointments = appointments();
		$gcal_api = $appointments->get_gcal_api();
		$gcal_api->maybe_sync();
	}

	private function upgrade_1_9_4() {

	}
}
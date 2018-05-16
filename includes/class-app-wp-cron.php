<?php

/**
 * Appointments WP Cron Class
 *
 * @since 2.3.0
 */

class Appointments_WP_Cron {
	/**
	 * GDPR wp_cron event name
	 */
	private $gdpr_delete = 'appointments_gdpr_wp_cron';

	public function __construct() {
		/**
		 * GDPR - delete
		 */
		if ( ! wp_next_scheduled( $this->gdpr_delete ) ) {
			wp_schedule_event( time(), 'hourly', $this->gdpr_delete );
		}
		add_action( $this->gdpr_delete, array( $this, 'gdpr_check_and_delete' ) );
	}

	/**
	 * Try to delete older appointments.
	 *
	 * @since 2.3.0
	 */
	public function gdpr_check_and_delete() {
		global $wpdb;
		$value = appointments_get_option( 'gdpr_delete' );
		if ( 'yes' !== $value ) {
			return;
		}
		$days = intval( appointments_get_option( 'gdpr_number_of_days' ) );
		if ( 1 > $days ) {
			return;
		}
		$days = sprintf( '-%d day', $days );
		$table = appointments_get_table( 'appointments' );
		$date = date( 'Y-m-d H:i', strtotime( $days ) );
		$sql = $wpdb->prepare( "select ID from {$table} where start < %s", $date );
		$results = $wpdb->get_col( $sql );
		foreach ( $results as $id ) {
			appointments_delete_appointment( $id );
		}
	}
}


<?php

/**
 * Appointments GDPR
 *
 * @since 2.3.0
 */

class Appointments_GDPR {
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
		/**
		 * Add information to privacy policy page (only during creation).
		 */
		add_filter( 'wp_get_default_privacy_policy_content', array( $this, 'add_policy' ) );
	}

	/**
	 * Get number of days of auto-erase data.
	 *
	 * @since 2.3.0
	 */
	private function get_number_of_days() {
		$value = appointments_get_option( 'gdpr_delete' );
		if ( 'yes' !== $value ) {
			return 0;
		}
		return intval( appointments_get_option( 'gdpr_number_of_days' ) );
	}

	/**
	 * Try to delete older appointments.
	 *
	 * @since 2.3.0
	 */
	public function gdpr_check_and_delete() {
		global $wpdb;
		$days = $this->get_number_of_days();
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

	/**
	 * Add Appointments Policy to "Privace Policy" page during creation.
	 *
	 * @since 2.3.0
	 */
	public function add_policy( $content ) {
		$content .= '<h3>' . __( 'Plugin: Appointments', 'appointments' ) . '</h3>';
		$content .=
			'<p>' . $suggested_text . __( 'When visitors book an appointment on the site we collect the data shown in the appointments form to allow future contact with a client.' ) . '</p>' .
			'<p>' . __( 'All collected data is not shown publicly but we can send it to our workers or contractors who will perform ordered services.', 'appointments' ) . '</p>';

		$days = $this->get_number_of_days();
		if ( 0 < $days ) {
			$days_desc = sprintf( _nx( '%d day', '%d days', $days, 'policy page days string', 'appointments' ), $days );
			$content .= sprintf( '<p>' . __( 'All collected data will be automatically erased %s after appointment date.', 'appointments' ) . '</p>', $days_desc );
		}
		return $content;
	}
}


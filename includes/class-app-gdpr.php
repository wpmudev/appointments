<?php

/**
 * Appointments GDPR
 *
 * @since 2.3.0
 */

class Appointments_GDPR {
	/**
	 * GDPR wp_cron event name
	 *
	 * @since 2.3.0
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
		/**
		 * Adding the Personal Data Exporter
		 */
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_plugin_exporter' ) );
		/**
		 * Adding the Personal Data Eraser
		 */
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_plugin_eraser' ) );
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
	 * Get plugin friendly name
	 */
	private function get_plugin_friendly_name() {
		$name = _x( 'Appointments Plugin', 'Free plugin name in personal data exporter.', 'appointments' );
		$is_pro = apply_filters( 'appointments_is_pro'. false );
		if ( $is_pro ) {
			$name = _x( 'Appointments+ Plugin', 'Pro plugin name in personal data exporter.', 'appointments' );
		}
		return $name;
	}


	/**
	 * Register plugin exporter.
	 *
	 * @since 2.3.0
	 */
	public function register_plugin_exporter( $exporters ) {
		$exporters['appointments'] = array(
			'exporter_friendly_name' => $this->get_plugin_friendly_name(),
			'callback' => array( $this, 'plugin_exporter' ),
		);
		return $exporters;
	}

	/**
	 * Export personal data.
	 *
	 * @since 2.3.0
	 */
	public function plugin_exporter( $email, $page = 1 ) {
		$appointments = appointments_get_appointments( array( 'email' => $email ) );
		$export_items = array();
		if ( count( $appointments ) ) {
			foreach ( $appointments as $appointment ) {
				$export_items[] = array(
					'address' => $appointment->address,
					'city' => $appointment->city,
					'name' => $appointment->name,
					'phone' => $appointment->phone,
				);
			}
		}
		return array(
			'data' => $export_items,
			'done' => true,
		);
	}

	/**
	 * Register plugin eraser.
	 *
	 * @since 2.3.0
	 */
	public function register_plugin_eraser( $erasers ) {
		$erasers['appointments'] = array(
			'eraser_friendly_name' => $this->get_plugin_friendly_name(),
			'callback'             => array( $this, 'plugin_eraser' ),
		);
		return $erasers;
	}

	/**
	 * Erase personal data.
	 *
	 * @since 2.3.0
	 */
	public function plugin_eraser( $email, $page = 1 ) {
		$days = intval( appointments_get_option( 'gdpr_number_of_days_user_erease' ) );
		if ( 1 > $days ) {
			return;
		}
		$table = appointments_get_table( 'appointments' );
		$date = date( 'Y-m-d H:i', strtotime( $days ) );
		$sql = $wpdb->prepare( "select ID from {$table} where start < %s", $date );
		$results = $wpdb->get_col( $sql );
		foreach ( $results as $id ) {
			appointments_delete_appointment( $id );
		}
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
			'<p>'.__( 'When visitors book an appointment on the site we collect the data shown in the appointments form to allow future contact with a client.' ) . '</p>' .
			'<p>' . __( 'All collected data is not shown publicly but we can send it to our workers or contractors who will perform ordered services.', 'appointments' ) . '</p>';

		$days = $this->get_number_of_days();
		if ( 0 < $days ) {
			$days_desc = sprintf( _nx( '%d day', '%d days', $days, 'policy page days string', 'appointments' ), $days );
			$content .= sprintf( '<p>' . __( 'All collected data will be automatically erased %s after appointment date.', 'appointments' ) . '</p>', $days_desc );
		}
		return $content;
	}
}


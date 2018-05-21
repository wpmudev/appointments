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
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_plugin_exporter' ), 10 );
		/**
		 * Adding the Personal Data Eraser
		 */
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_plugin_eraser' ), 10 );
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
		$is_pro = apply_filters( 'appointments_is_pro', false );
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
				$item = array(
					'group_id' => 'appointments',
					'group_label' => $this->get_plugin_friendly_name(),
					'item_id' => 'appointments-'.$appointment->ID,
					'data' => array(
						array(
							'name' => __( 'Name', 'appointments' ),
							'value' => $appointment->name,
						),
						array(
							'name' => __( 'Email', 'appointments' ),
							'value' => $appointment->email,
						),
						array(
							'name' => __( 'Phone', 'appointments' ),
							'value' => $appointment->phone,
						),
						array(
							'name' => __( 'Address', 'appointments' ),
							'value' => $appointment->address,
						),
						array(
							'name' => __( 'City', 'appointments' ),
							'value' => $appointment->city,
						),
						array(
							'name' => __( 'Note', 'appointments' ),
							'value' => $appointment->notes,
						),
						array(
							'name' => __( 'Create date', 'appointments' ),
							'value' => $appointment->created,
						),
						array(
							'name' => __( 'Start date', 'appointments' ),
							'value' => $appointment->start,
						),
						array(
							'name' => __( 'End date', 'appointments' ),
							'value' => $appointment->end,
						),
					),
				);
				/**
				 * Export single appointment row.
				 *
				 * @since 2.3.0
				 *
				 * @param array $item Export data for appointment.
				 * @param string $email Appointment email.
				 * @param object $appointment Single appointment data object.
				 */
				$export_items[] = apply_filters( 'appointments_gdpr_export', $item, $email, $appointment );
			}
		}
		$export = array(
			'data' => $export_items,
			'done' => true,
		);
		return $export;
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
		global $wpdb;
		$days = intval( appointments_get_option( 'gdpr_number_of_days_user_erease' ) );
		if ( 1 > $days ) {
			return;
		}
		$messages = array();
		$table = appointments_get_table( 'appointments' );
		/**
		 * count days
		 */
		$days = sprintf( '-%d day', $days );
		$date = date( 'Y-m-d H:i', strtotime( $days ) );
		/**
		 * delete
		 */
		$sql = $wpdb->prepare( "select ID from {$table} where email = %s and start < %s", $email, $date );
		$results = $wpdb->get_col( $sql );
		$count = 0;
		foreach ( $results as $id ) {
			$result = appointments_delete_appointment( $id );
			if ( $result ) {
				$count++;
			}
		}
		if ( 0 < $count ) {
			$messages[] = sprintf( _n( 'We deleted %d appointment.', 'We deleted %d appointments', $count, 'appointments' ), $count );
		} else {
			$messages[] = __( 'We do not deleted any appointments.', 'appointments' );
		}
		/**
		 * check how much left
		 */
		$sql = $wpdb->prepare( "select count(ID) from {$table} where email = %s", $email );
		$items_retained = $wpdb->get_var( $sql );
		if ( 0 < $items_retained ) {
			$messages[] = sprintf( _n( 'We do not deleted %d appointment.', 'We do not deleted %d appointments', $count, 'appointments' ), $count );
		}
		/**
		 * return
		 */
		return array(
			'items_removed' => $count,
			'items_retained' => $items_retained,
			'messages' => $messages,
			'done' => true,
		);
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


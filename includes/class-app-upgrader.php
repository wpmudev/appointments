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

		if ( version_compare( $saved_version, '1.9.4', '<' ) ) {
			$this->upgrade_1_9_4();
		}

		if ( version_compare( $saved_version, '1.9.4.1', '<' ) ) {
			$this->upgrade_1_9_4();
		}

		if ( version_compare( $saved_version, '1.9.4.2', '<' ) ) {
			$this->upgrade_1_9_4_2();
		}

		if ( version_compare( $saved_version, '1.9.4.3', '<' ) ) {
			$this->upgrade_1_9_4_3();
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


	private function upgrade_1_9_4() {
		global $wpdb;
		// Fixes a bug with working hours format
		$table = appointments_get_table( 'wh' );

		$rows = $wpdb->get_results( "SELECT * FROM {$table}" );
		foreach ( $rows as $key => $row ) {
			$rows[ $key ]->hours = maybe_unserialize( $row->hours );
		}

		foreach ( $rows as $key => $row ) {
			if ( ! is_array( $row->hours ) ) {
				continue;
			}

			foreach ( $row->hours as $day_of_week => $values ) {
				if ( ! isset( $values['start'] ) ) {
					continue;
				}

				$start = $values['start'];
				$end = $values['end'];
				if ( is_array( $start ) ) {
					foreach ( $start as $idx => $start_value ) {
						$start_value = date( 'H:i', strtotime( $start_value ) );
						if ( strlen( $start_value ) != 5 ) {
							// Wrong conversion
						}
						else {
							// All good!
							$rows[ $key ]->hours[ $day_of_week ]['start'][ $idx ] = $start_value;
						}
					}
				}

				if ( is_array( $end ) ) {
					foreach ( $end as $idx => $end_value ) {
						$end_value = date( 'H:i', strtotime( $end_value ) );
						if ( strlen( $end_value ) != 5 ) {
							// Wrong conversion
						}
						else {
							// All good!
							$rows[ $key ]->hours[ $day_of_week ]['end'][ $idx ] = $end_value;
						}
					}
				}

				if ( ! is_array( $start ) && ! is_array( $end ) ) {
					$start = date( 'H:i', strtotime( $start ) );
					$end = date( 'H:i', strtotime( $end ) );
					if ( strlen( $start ) != 5 || strlen( $end ) != 5 ) {
						// Wrong conversion
					}
					else {
						// All good!
						$rows[ $key ]->hours[ $day_of_week ]['start'] = $start;
						$rows[ $key ]->hours[ $day_of_week ]['end'] = $end;
					}
				}
			}
		}

		foreach ( $rows as $key => $row ) {
			$hours = maybe_serialize( $row->hours );
			$wpdb->update(
				$table,
				array( 'hours' => $hours ),
				array( 'ID' => $row->ID ),
				array( '%s' ),
				array( '%d' )
			);
		}

		appointments_clear_cache();
	}

	private function upgrade_1_9_4_2() {
		// Delete cache table
		global $wpdb;
		$table = $wpdb->prefix . 'app_cache';
		$wpdb->query( "DROP TABLE IF EXISTS $table" );
	}

	private function upgrade_1_9_4_3() {
		// Move paddings to general options
		$service_paddings = get_option( 'appointments_services_padding', array() );
		$options = appointments_get_options();
		if ( ! empty( $service_paddings ) ) {
			$options['service_padding'] = $service_paddings;
		}

		$worker_paddings = get_option( 'appointments_workers_padding', array() );
		if ( ! empty( $worker_paddings ) ) {
			$options['worker_padding'] = $worker_paddings;
		}

		appointments_update_options( $options );
	}
}
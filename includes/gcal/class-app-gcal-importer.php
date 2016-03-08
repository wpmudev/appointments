<?php

class Appointments_Google_Calendar_Importer {
	/**
	 * @var Appointments_Google_Calendar $gcal_api
	 */
	private $gcal_api;

	public function __construct( $gcal_api ) {
		$this->gcal_api = $gcal_api;
	}



	function export( $offset ) {
		$per_page = 2;

		$args = array( 'status' => $this->get_syncable_status() );
		// @TODO Not exporting workers right now
		$apps = appointments_get_appointments( $args );
		$counter = 0;
		while ( $counter < $offset ) {
			array_shift( $apps );
			$counter++;
		}

		if ( empty( $apps ) ) {
			return false;
		}

		$counter = 1;
		foreach ( $apps as $app ) {
			if ( $counter > $per_page ) {
				break;
			}

			$this->gcal_api->update_event( $app->ID );
			$counter++;
		}

		$offset += $per_page;

		return $offset;
	}


	/**
	 * @return array|WP_Error
	 */
	function import( $worker_id = false ) {
		global $wpdb;

		if ( $worker_id && ! appointments_get_worker( $worker_id ) ) {
			return array(
				'inserted' => 0,
				'updated' => 0,
				'deleted' => 0
			);
		}

		$current_gmt_time = current_time( 'timestamp', true );

		$events = $this->gcal_api->get_events_list();
		if ( is_wp_error( $events ) ) {
			return $events;
		}

		$table = appointments_get_table( 'appointments' );
		$query = "SELECT gcal_ID FROM $table WHERE gcal_ID IS NOT NULL";
		if ( $worker_id ) {
			$query .= $wpdb->prepare( " AND worker = %d", $worker_id );
		}
		$current_gcal_event_ids = $wpdb->get_col( $query );

		if ( ! $current_gcal_event_ids ) {
			$current_gcal_event_ids = array();
		}

		$updated = array();
		$inserted = array();
		$deleted = array();

		// Service ID is not important as we will use this record for blocking our time slots only
		$service_id = appointments_get_services_min_id();

		// Create a list of event_id's
		/** @var Google_Service_Calendar_Event $event */
		foreach ( $events as $event ) {
			$event_id = $event->getId();
			$app = appointments_get_appointment_by_gcal_id( $event_id );

			$event_start = $event->getStart();
			$event_start_gmt_date = gmdate( 'Y-m-d H:i:s', strtotime( $event_start->dateTime ) );
			$event_start_gmt_timestamp = strtotime( $event_start_gmt_date );
			$event_start_date = get_date_from_gmt( $event_start_gmt_date );

			$event_end = $event->getEnd();
			$event_end_gmt_date = gmdate( 'Y-m-d H:i:s', strtotime( $event_end->dateTime ) );
			$event_end_gmt_timestamp = strtotime( $event_end_gmt_date );

			$event_updated = $event->getUpdated();
			$event_updated_gmt_date = gmdate( 'Y-m-d H:i:s', strtotime( $event_updated ) );
			$event_updated_date = get_date_from_gmt( $event_updated_gmt_date );

			$duration = ( strtotime( $event_end_gmt_date ) - strtotime( $event_start_gmt_date ) ) / 60;

			if ( $event_start_gmt_timestamp > $current_gmt_time && $event_end_gmt_timestamp > $current_gmt_time ) {
				// We can add it
				$args = array(
					'service' => $service_id,
					'worker' => $worker_id ? $worker_id : false,
					'date' => strtotime( $event_start_date ),
					'duration' => $duration,
					'status' => 'reserved',
					'gcal_ID' => $event_id,
					'gcal_updated' => $event_updated_date,
					'note' => $event->getSummary()
				);

				if ( ! $app ) {
					// New Appointment
					appointments_insert_appointment( $args );
					$inserted[] = $event_id;
				}
				else {
					// Update Appointment
					appointments_update_appointment( $app->ID, $args );
					$updated[] = $event_id;
				}
			}
		}

		$processed = array_merge( $updated, $inserted );
		foreach ( $current_gcal_event_ids as $gcal_event_id ) {
			if ( ! in_array( $gcal_event_id, $processed ) ) {
				// The event is no longer in the calendar, let's delete it locally
				$app = appointments_get_appointment_by_gcal_id( $gcal_event_id );
				appointments_delete_appointment( $app->ID );
				$deleted[] = $gcal_event_id;
			}
		}


		return array_map( 'count', compact( 'inserted', 'updated', 'deleted' ) );
	}

}
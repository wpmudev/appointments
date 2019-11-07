<?php

class App_Appointments_Google_Calendar_Importer {
	/**
	 * @var App_Appointments_Google_Calendar $gcal_api
	 */
	private $gcal_api;

	public function __construct( $gcal_api ) {
		$this->gcal_api = $gcal_api;
	}



	function export( $offset ) {
		$per_page = 2;

		$args = array( 'status' => $this->gcal_api->get_syncable_status() );
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

			$worker_id = $app->worker;
			if ( ! appointments_get_worker( $worker_id ) ) {
				$worker_id = false;
			}

			if ( $worker_id && $this->gcal_api->switch_to_worker( $worker_id ) ) {
				// Update the worker calendar
				$this->gcal_api->update_event( $app->ID );
				$this->gcal_api->restore_to_default();
			}
			else {
				// Update the General calendar
				$this->gcal_api->update_event( $app->ID );
			}
			$counter++;
		}

		$offset += $per_page;

		return $offset;
	}


	/**
	 * @return array|WP_Error
	 */
	function import( $worker_id = false ) {
		if ( $worker_id && ! appointments_get_worker( $worker_id ) ) {
			return array(
				'inserted' => 0,
				'updated' => 0,
				'deleted' => 0
			);
		}

		$events = $this->gcal_api->get_events_list();
		if ( is_wp_error( $events ) ) {
			return $events;
		}

		$current_gcal_event_ids = appointments_get_gcal_ids( $worker_id );

		$updated = array();
		$inserted = array();
		$deleted = array();

		// Create a list of event_id's
		/** @var Appointments_Google_Service_Calendar_Event $event */
		foreach ( $events as $event ) {
			$result = $this->import_event( $event, $worker_id );
			if ( 'updated' === $result ) {
				$updated[] = $event->getId();
			}
			elseif ( 'inserted' === $result ) {
				$inserted[] = $event->getId();
			}
		}

		$processed = array_merge( $updated, $inserted );
		foreach ( $current_gcal_event_ids as $gcal_event_id ) {
			if ( ! in_array( $gcal_event_id, $processed ) ) {
				// The event is no longer in the calendar, let's delete it locally
				$app = appointments_get_appointment_by_gcal_id( $gcal_event_id );
				if ( $app && 'reserved' === $app->status ) {
					appointments_delete_appointment( $app->ID );
					$deleted[] = $gcal_event_id;
				}
			}
		}


		return array_map( 'count', compact( 'inserted', 'updated', 'deleted' ) );
	}

	/**
	 * @param Appointments_Google_Service_Calendar_Event $event
	 *
	 * @return bool|string
	 */
	public function import_event( $event, $worker_id = 0 ) {
		$current_gmt_time = current_time( 'timestamp', true );

		$event_id = $event->getId();
		$app = appointments_get_appointment_by_gcal_id( $event_id );

		if ( $app && ! $worker_id ) {
			$worker = appointments_get_worker( $app->worker );
			if ( $worker ) {
				$worker_id = $worker->ID;
			}
		}

		$worker_id = apply_filters( 'appointments_import_gcal_event_worker_id', $worker_id, $event );

		$service_id = false;
		if ( $app ) {
			$service = appointments_get_service( $app->service );
			if ( $service ) {
				$service_id = $service->ID;
			}
		}

		$worker = appointments_get_worker( $worker_id );
		if ( ! $service_id && $worker && $services = $worker->get_services() ) {
			// Get the min service ID for this worker
			$default_service = current( $services );
			$service_id = $default_service->ID;
		}

		if ( ! $service_id ) {
			// Service ID is not important as we will use this record for blocking our time slots only
			$service_id = apply_filters( 'appointments_import_gcal_event_service_id', appointments_get_services_min_id(), $event );
		}


		$event_start               = $event->getStart();
		$event_start_datetime      = $event_start->getDateTime();
		$event_start_gmt_timestamp = strtotime( $event_start->getDateTime() );
		$event_start_date_gmt = gmdate('Y-m-d H:i:s', $event_start_gmt_timestamp );
		$event_start_date = get_date_from_gmt( $event_start_date_gmt );


		$event_end = $event->getEnd();
		$event_end_gmt_timestamp = strtotime( $event_end->getDateTime() );

		$event_updated = $event->getUpdated();
		$event_updated_gmt_date = gmdate( 'Y-m-d H:i:s', strtotime( $event_updated ) );
		$event_updated_date = get_date_from_gmt( $event_updated_gmt_date );

		$duration = ( $event_end_gmt_timestamp - $event_start_gmt_timestamp ) / 60;

		if ( $event_start_gmt_timestamp > $current_gmt_time && $event_end_gmt_timestamp > $current_gmt_time ) {
			// We can add it
			$args = array(
				'service' => $service_id,
				'worker' => $worker_id ? $worker_id : false,
				'duration' => $duration,
				'gcal_ID' => $event_id,
				'gcal_updated' => $event_updated_date,
			);

			if ( empty( $app->note ) ) {
				// Override not only if is empty
				$args['note'] = $event->getSummary();
			}

			if ( ! $app ) {
				// New Appointment
				$args['date'] = strtotime( $event_start_date );
				$args['status'] = 'reserved';
				$app_id = appointments_insert_appointment( $args );
				$app = appointments_get_appointment( $app_id );
				$result = 'inserted';
			}
			else {
				// Update Appointment
				$args['datetime'] = strtotime( $event_start_date );
				appointments_update_appointment( $app->ID, $args );
				$result = 'updated';
			}

			$description = $event->getDescription();
			if ( $description ) {
				appointments_update_appointment_meta( $app->ID, 'gcal_description', $description );
			}

			do_action( 'appointments_gcal_import_event', $app, $result, $event );

			return $result;
		}

		return false;

	}

}

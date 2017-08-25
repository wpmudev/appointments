<?php

/**
 * Manages all related to Google Calendar integration,
 * import, export and sync appointments
 *
 * Class Appointments_Google_Calendar
 */
class Appointments_Google_Calendar {

	public $errors = array();

	public $admin;

	public $api_mode = 'none';

	private $access_code;

	private $description = '';

	private $summary = '';

	public $worker_id = false;

	public function __construct() {
		if ( ! apply_filters( 'appointments_load_gcal', true ) ) {
			return;
		}

		if ( ! defined( 'APP_GCAL_MAX_RESULTS_LIMIT' ) ) {
			define( 'APP_GCAL_MAX_RESULTS_LIMIT', 500, true );
		}

		include_once( 'gcal/class-app-gcal-admin.php' );
		$this->admin = new Appointments_Google_Calendar_Admin( $this );

		add_action( 'wp_ajax_app_gcal_export', array( $this, 'export_batch' ) );
		add_action( 'wp_ajax_app_gcal_import', array( $this, 'import' ) );

		add_action( 'appointments_gcal_sync', array( $this, 'maybe_sync' ) );
		add_action( 'wp_ajax_appointments_gcal_sync', array( $this, 'maybe_sync' ) );

		add_action( 'app-appointments_list-edit-client', array( $this, 'edit_inline_gcal_fields' ), 10, 2 );
		$options = appointments_get_options();

		if ( isset( $options['gcal_api_mode'] ) ) {
			$this->api_mode = $options['gcal_api_mode'];
		}

		$this->setup_cron();

		// Appointments Hooks
		$this->add_appointments_hooks();

		if ( isset( $_GET['gcal-sync-now'] ) && is_admin() && App_Roles::current_user_can( 'manage_options', App_Roles::CTX_STAFF ) ) {
			$this->maybe_sync();
		}
	}

	public function __get( $name ) {
		if ( 'api_manager' === $name && empty( $this->api_manager ) ) {
			$this->load_api();
			return $this->api_manager;
		}
		return null;
	}

	public function load_api() {
		$options = appointments_get_options();
		include_once( 'gcal/class-app-gcal-api-manager.php' );
		$this->api_manager = new Appointments_Google_Calendar_API_Manager();


		$default_creds = array();
		if ( ! empty( $options['gcal_client_id'] ) && ! empty( $options['gcal_client_secret'] ) ) {
			$default_creds['client_id'] = $options['gcal_client_id'];
			$default_creds['client_secret'] = $options['gcal_client_secret'];
			$this->api_manager->set_client_id_and_secret( $options['gcal_client_id'], $options['gcal_client_secret'] );
		}

		if ( ! empty( $options['gcal_token'] ) ) {
			$default_creds['token'] = $options['gcal_token'];
			$result = $this->api_manager->set_access_token( $options['gcal_token'] );
			if ( is_wp_error( $result ) ) {
				$this->errors[] = array( 'message' => sprintf( __( 'Error validating the access token: %s', 'appointments' ), $result->get_error_message() ) );
			}
		}

		if ( ! empty( $options['gcal_token'] ) && ! $this->is_connected() ) {
			// The token is set but appears not to be valid, let's reset everything
			$options['gcal_token'] = '';
			$options['gcal_access_code'] = '';
			$default_creds['token'] = $options['gcal_token'];
			appointments_update_options( $options );
		}

		if ( ! empty( $options['gcal_access_code'] ) ) {
			$this->access_code = $options['gcal_access_code'];
			$default_creds['access_code'] = $options['gcal_access_code'];
		}

		if ( ! empty( $options['gcal_selected_calendar'] ) ) {
			$default_creds['calendar_id'] = $options['gcal_selected_calendar'];
			$this->api_manager->set_calendar( $options['gcal_selected_calendar'] );
		}

		$this->description = ! empty( $options['gcal_description'] ) ? $options['gcal_description'] : '';
		$this->summary = ! empty( $options['gcal_summary'] ) ? $options['gcal_summary'] : '';

		$this->api_manager->set_default_credentials( $default_creds );

		add_action( 'shutdown', array( $this, 'save_new_token' ) );
	}

	public function add_appointments_hooks() {
		add_action( 'wpmudev_appointments_insert_appointment', array( $this, 'on_insert_appointment' ), 200 );
		add_action( 'wpmudev_appointments_update_appointment', array( $this, 'on_update_appointment' ), 200, 3 );
		add_action( 'appointments_delete_appointment', array( $this, 'on_delete_appointment' ) );
	}

	public function remove_appointments_hooks() {
		remove_action( 'wpmudev_appointments_insert_appointment', array( $this, 'on_insert_appointment' ), 200 );
		remove_action( 'wpmudev_appointments_update_appointment', array( $this, 'on_update_appointment' ), 200 );
		remove_action( 'appointments_delete_appointment', array( $this, 'on_delete_appointment' ) );
	}

	public function setup_cron() {
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );

		$sync_modes = array( 'sync', 'gcal2app' );
		// Schedule a cron.
		if ( in_array( $this->get_api_mode(), $sync_modes ) ) {
			$scheduled = wp_next_scheduled( 'appointments_gcal_sync' );
			if ( ! $scheduled ) {
				wp_schedule_event( current_time( 'timestamp' ) + 600, 'app-gcal', 'appointments_gcal_sync' );
			}
		} elseif ( $this->workers_allowed() ) { // Schedule cron if admin has allowed workers to set up gcal sync.
			$workers = appointments_get_workers();

			foreach ( $workers as $worker ) {
				$switched = $this->switch_to_worker( $worker->ID );
				if ( $switched ) {
					if ( in_array( $this->get_api_mode(), $sync_modes ) ) {
						$scheduled = wp_next_scheduled( 'appointments_gcal_sync' );
						if ( ! $scheduled ) {
							wp_schedule_event( current_time( 'timestamp' ) + 600, 'app-gcal', 'appointments_gcal_sync' );
							$this->restore_to_default();
							return;
						} else {
							// Don't need to keep looping through workers if cron already scheduled.
							$this->restore_to_default();
							return;
						}
					}
					$this->restore_to_default();
				}
			}
		} else {
			$scheduled = wp_next_scheduled( 'appointments_gcal_sync' );
			if ( $scheduled ) {
				wp_unschedule_event( $scheduled, 'appointments_gcal_sync' );
			}
		}
	}

	public function cron_schedules( $schedules ) {
		$schedules['app-gcal'] = array( 'interval' => HOUR_IN_SECONDS / 6,    'display' => __( 'Every 10 minutes' ) );
		return $schedules;
	}

	public function edit_inline_gcal_fields( $deprecated, $app ) {
		if ( ! isset( $app->gcal_ID ) || ! $app->gcal_ID ) {
			return;
		}

		$html = '';
		$show = false;
		$description = '';
		if ( $app->worker && $this->switch_to_worker( $app->worker ) ) {
			// Looks like it's in worker's calendar
			$description = appointments_get_appointment_meta( $app->ID, 'gcal_description' );
			if ( ! $description ) {
				$description = '';
			}
			$show = true;
		}
		elseif ( $this->is_connected() && $this->api_manager->get_calendar() ) {
			// General calendar
			$show = true;
			$description = appointments_get_appointment_meta( $app->ID, 'gcal_description' );
			if ( ! $description ) {
				$description = '';
			}
		}

		if ( $show ) {
			$html .= '<label class="title">'.__( 'Google Calendar Description', 'appointments' );
			$html .= '<textarea class="widefat" rows="10" disabled="disabled">' . esc_textarea( $description ) . '</textarea>';
			$html .= '</label>';
		}
		echo $html;
	}


	public function maybe_sync() {
		$appointments = appointments();
		$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

		include_once( 'gcal/class-app-gcal-importer.php' );

		$processed_event_ids = array();

		$start_time = current_time( 'timestamp' );
		$end_time = $start_time + ( 3600 * 24 * $appointments->get_app_limit() );

		// First, let's sync worker's calendars
		if ( $this->workers_allowed() ) {
			$workers = appointments_get_workers();

			foreach ( $workers as $worker ) {
				$switched = $this->switch_to_worker( $worker->ID );
				if ( $switched ) {
					$api_mode = $this->get_api_mode();
					if ( 'sync' != $api_mode ) {
						$this->restore_to_default();
						continue;
					}

					$events = $this->get_events_list();
					if ( is_wp_error( $events ) ) {
						$this->restore_to_default();
						continue;
					}

					$events_ids = array_map( array( $this, '_get_event_id' ), $events );

					/** @var Google_Service_Calendar_Event $event */
					foreach ( $events as $event ) {
						if ( $event_id = $this->sync_event( $event, $worker->ID ) ) {
							$processed_event_ids[] = $event_id;
						}
					}

					$current_worker_gcal_ids = appointments_get_gcal_ids( $worker->ID );

					// Delete those appointments that are not anymore in Worker's Google Calendar
					foreach ( $current_worker_gcal_ids as $gcal_id ) {
						if ( ! in_array( $gcal_id, $events_ids ) && ! in_array( $gcal_id, $processed_event_ids ) ) {
							$this->remove_appointments_hooks();
							if ( $app = appointments_get_appointment_by_gcal_id( $gcal_id ) ) {
								if ( ! in_array( $app->status, array( 'completed', 'pending' ) ) ) {
									appointments_update_appointment( $app->ID,
										array(
											'status' => 'removed',
											'gcal_ID' => ''
										)
									);
								}
							}
							$this->add_appointments_hooks();
						}
					}

					$this->restore_to_default();
				}
			}

		}

		$api_mode = $this->get_api_mode();
		$sync_modes = array( 'sync', 'gcal2app' );
		if ( ! in_array( $api_mode, $sync_modes ) || ! $this->is_connected() || ! $this->api_manager->get_calendar() ) {
			if ( $doing_ajax ) {
				wp_send_json_error();
			}
			else {
				return;
			}
		}

		$current_gcal_ids = appointments_get_gcal_ids();
		$events = $this->get_events_list();
		if ( is_wp_error( $events ) ) {
			if ( $doing_ajax ) {
				wp_send_json_error();
			}
			else {
				return;
			}
		}

		$events_ids = array_map( array( $this, '_get_event_id' ), $events );

		// Import or sync Google Calendar Events
		/** @var Google_Service_Calendar_Event $event */
		foreach ( $events as $event ) {
			if ( $event_id = $this->sync_event( $event ) ) {
				$processed_event_ids[] = $event_id;
			}
		}

		// Delete those appointments that are not anymore in Google Calendar
		foreach ( $current_gcal_ids as $gcal_id ) {
			if ( ! in_array( $gcal_id, $events_ids ) && ! in_array( $gcal_id, $processed_event_ids ) ) {
				$this->remove_appointments_hooks();
				if ( $app = appointments_get_appointment_by_gcal_id( $gcal_id ) ) {
					if ( 'no_preference' === $this->get_api_scope() && appointments_get_worker( $app->worker ) ) {
						// If scope is set to No Preference and there's a worker assigned to it, do not sync this app
						continue;
					}

					if ( ! in_array( $app->status, array( 'completed', 'pending', 'removed' ) ) ) {
						// So the event is not in our list but is it on GCal?
						// Maybe the time has passed
						$event = $this->get_event( $app->ID );
						if ( $event ) {
							if( $event->status == 'cancelled' ) {
								appointments_update_appointment_status( $app->ID, 'removed' );
							}
							else{
								// The event is in GCal but the time has passed
								// Let's move it to completed
								appointments_update_appointment_status( $app->ID, apply_filters( 'appointments_gcal_change_status_on_completed_event', 'completed' ) );
							}
						}
						else {
							appointments_update_appointment_status( $app->ID, 'removed' );
						}
					}
				}
				$this->add_appointments_hooks();
			}
		}
		if( isset( $_POST['return_result'] ) && $_POST['return_result'] == 'yes' ){
			if ( $doing_ajax ) {
				wp_send_json_success();
			}
			else {
				return;
			}
		}
		return;
	}

	/**
	 * @param Google_Service_Calendar_Event $event
	 *
	 * @return mixed
	 */
	public function _get_event_id( $event ) {
		return $event->getID();
	}

	/**
	 * @param Google_Service_Calendar_Event $event
	 * @param integer $worker_id
	 *
	 * @return string|bool
	 */
	private function sync_event( $event, $worker_id = 0 ) {
		$event_id = $event->getId();

		$app = appointments_get_appointment_by_gcal_id( $event_id );
		if ( $app && ! in_array( $app->status, $this->get_syncable_status() ) ) {
			return false;
		}

		$importer = new Appointments_Google_Calendar_Importer( $this );

		$this->remove_appointments_hooks();
		$importer->import_event( $event, $worker_id );
		$this->add_appointments_hooks();
		return $event_id;
	}


	public function is_connected() {
		$access_token = json_decode( $this->api_manager->get_access_token() );
		if ( ! $access_token ) {
			return false;
		}

		$options = appointments_get_options();

		if ( ! $this->worker_id && empty( $options['gcal_access_code'] ) ) {
			$options['gcal_access_code'] = '';
			appointments_update_options( $options );
			return false;
		}

		if ( empty( $options['gcal_client_id'] ) || empty( $options['gcal_client_secret'] ) ) {
			// No client secret and no client ID, why do we have a token then?
			$this->api_manager->set_access_token('{"access_token":0}');
			$options['gcal_token'] = '';
			$options['gcal_client_id'] = '';
			$options['gcal_client_secret'] = '';
			$options['gcal_access_code'] = '';
			appointments_update_options( $options );
			return false;
		}

		if ( ( ! isset( $access_token->access_token ) ) || ( isset( $access_token->access_token ) && ! $access_token->access_token ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Cast an Appointments_Appointment to a Google Event format
	 *
	 * @param $app_id
	 *
	 * @return Google_Service_Calendar_Event|bool
	 */
	public function appointment_to_gcal_event( $app_id ) {
		global $appointments;

		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return false;
		}

		$event = new Google_Service_Calendar_Event();

		$options = appointments_get_options();

		// Event summary
		$summary = apply_filters(
			'app-gcal-set_summary',
			$appointments->_replace(
				$this->get_summary(),
				$app->name,
				$appointments->get_service_name( $app->service ),
				appointments_get_worker_name( $app->worker ),
				$app->start,
				$app->price,
				$appointments->get_deposit( $app->price ),
				$app->phone,
				$app->note,
				$app->address,
				$app->email,
				$app->city
			),
			$app
		);

		// Event description
		$description = apply_filters(
			'app-gcal-set_description',
			$appointments->_replace(
				$this->get_description(),
				$app->name,
				$appointments->get_service_name($app->service),
				appointments_get_worker_name($app->worker),
				$app->start,
				$app->price,
				$appointments->get_deposit( $app->price ),
				$app->phone,
				$app->note,
				$app->address,
				$app->email,
				$app->city
			),
			$app
		);

		// Location
		if ( isset( $options["gcal_location"] ) && '' != trim( $options["gcal_location"] ) ) {
			$location = str_replace( array( 'ADDRESS', 'CITY' ), array(
				$app->address,
				$app->city
			), $options["gcal_location"] );
		} else {
			$location = get_bloginfo( 'description' );
		}

		$location = apply_filters( 'appointments_gcal_event_location', $location, $app );

		// Dates
		$start = new Google_Service_Calendar_EventDateTime();
		$start->setDateTime( $app->get_start_gmt_date( "Y-m-d\TH:i:s\Z" ) );
		$end = new Google_Service_Calendar_EventDateTime();
		$end->setDateTime( $app->get_end_gmt_date( "Y-m-d\TH:i:s\Z" ) );

		// Email
		$email = $app->get_email();

		// The first atendee will be the one with this email
		$attendee1 = new Google_Service_Calendar_EventAttendee();
		$attendee1->setEmail( $email );
		$attendees = array( $attendee1 );

		$event->setSummary( $summary );
		$event->setAttendees( $attendees );
		$event->setLocation( $location );
		$event->setStart( $start );
		$event->setEnd( $end );
		$event->setDescription( $description );

		// Alright, now deal with event sequencing
		if ( ! empty( $app->gcal_ID ) ) {
			$tmp = $this->api_manager->get_event( $app->gcal_ID );

			if ( ! is_wp_error( $tmp ) ) {
				if ( is_object( $tmp ) && ! empty( $tmp->sequence ) ) {
					$event->setSequence( $tmp->sequence );
				}
				elseif ( is_array( $tmp ) && ! empty( $tmp['sequence'] ) ) {
					$event->setSequence( $tmp['sequence'] );
				}
			}
		}

		return $event;
	}

	/**
	 * Sometimes Google will refresh the token.
	 * If so, we'll save it
	 */
	public function save_new_token() {
		$current_token = $this->api_manager->get_access_token();
		if ( ! $current_token ) {
			return;
		}

		$options = appointments_get_options();
		if ( $options['gcal_token'] != $current_token ) {
			$options['gcal_token'] = $current_token;
			appointments_update_options( $options );
		}
	}


	public function export_batch() {
		include_once( 'gcal/class-app-gcal-importer.php' );
		$importer = new Appointments_Google_Calendar_Importer( $this );
		$offset = absint( $_POST['offset'] );
		$offset = $importer->export( $offset );

		if ( false === $offset ) {
			// Finished
			wp_send_json_success();
		}

		wp_send_json_error( array( 'offset' => $offset ) );
	}

	public function import() {
		$import_modes = array( 'sync', 'gcal2app' );

		if ( ! in_array( $this->get_api_mode(), $import_modes ) ) {
			wp_send_json( array( 'message' => 'Error' ) );
		}

		include_once( 'gcal/class-app-gcal-importer.php' );
		$importer = new Appointments_Google_Calendar_Importer( $this );
		$this->remove_appointments_hooks();
		$results = $importer->import();
		$this->add_appointments_hooks();

		if ( is_wp_error( $results ) ) {
			wp_send_json( array( 'message' => $results->get_error_message() ) );
		}

		if ( $this->workers_allowed() ) {
			$workers = appointments_get_workers();
			foreach ( $workers as $worker ) {
				$switched = $this->switch_to_worker( $worker->ID );
				if ( $switched ) {
					$worker_results = $importer->import( $worker->ID );
					if ( ! is_wp_error( $worker_results ) ) {
						$results['inserted'] += $worker_results['inserted'];
						$results['updated'] += $worker_results['updated'];
						$results['deleted'] += $worker_results['deleted'];
					}
					$this->restore_to_default();
				}

			}
		}


		wp_send_json( array( 'message' => sprintf( __( '%d updated, %d new inserted and %d deleted', 'appointments' ), $results['updated'], $results['inserted'], $results['deleted'] ) ) );
	}




	function get_apps_to_export_count() {
		$apps_count = appointments_count_appointments();
		$count = 0;
		foreach ( $this->get_syncable_status() as $status ) {
			$count += $apps_count[ $status ];
		}

		return $count;
	}




	/**
	 * Return GCal API mode (none, app2gcal or sync )
	 *
	 * @return string
	 */
	function get_api_mode() {
		return $this->api_mode;
	}

	public function get_access_code() {
		return $this->access_code;
	}

	public function get_api_scope() {
		$options = appointments_get_options();
		return isset( $options['gcal_api_scope'] ) ? $options['gcal_api_scope'] : 'all';
	}

	/**
	 * Check if workers are allowed to use their own calendar
	 *
	 * @return bool
	 */
	public function workers_allowed() {
		$options = appointments_get_options();
		return isset( $options['gcal_api_allow_worker'] ) && 'yes' === $options['gcal_api_allow_worker'];
	}

	private function _is_writable_mode() {
		$mode = $this->get_api_mode();
		return ! in_array( $mode, array( 'gcal2app', 'none' ) );
	}

	public function get_syncable_status () {
		return apply_filters( 'app-gcal-syncable_status', array( 'paid', 'confirmed', 'reserved' ) );
	}

	public function is_syncable_status( $status ) {
		$syncable_status = $this->get_syncable_status();
		return in_array( $status, $syncable_status );
	}

	public function switch_to_worker( $worker_id, $check_connection = true ) {
		if ( ! $this->workers_allowed() ) {
			return false;
		}

		if ( ! $this->is_connected() ) {
			return false;
		}

		$worker = appointments_get_worker( $worker_id );
		if ( ! $worker ) {
			return false;
		}

		$this->worker_id = $worker->ID;

		$worker_api_mode = get_user_meta( $worker_id, 'app_api_mode', true );
		if ( ! $worker_api_mode ) {
			$worker_api_mode = 'none';
		}

		$this->access_code = get_user_meta( $worker_id, 'app_gcal_access_code', true );
		$worker_description = get_user_meta( $worker_id, 'app_gcal_description', true );
		$this->description = $worker_description;

		$worker_summary = get_user_meta( $worker_id, 'app_gcal_summary', true );
		$this->summary = $worker_summary;

		// Set the API Mode
		$this->api_mode = $worker_api_mode;
		$this->api_manager->switch_to_worker( $worker_id );

		if ( $check_connection && ! $this->is_connected() ) {
			$this->restore_to_default();
			return false;
		}

		return true;
	}

	public function restore_to_default() {
		$options = appointments_get_options();

		// Set the API Mode
		$this->api_mode = $options['gcal_api_mode'];
		$this->description = $options['gcal_description'];
		$this->summary = $options['gcal_summary'];
		$this->api_manager->restore_to_default();
		$this->worker_id = false;

		return true;
	}


	/**
	 * Return GCal Summary (name of Event)
	 *
	 * @since 1.2.1
	 *
	 *
	 * @return string
	 */
	public function get_summary() {
		if ( empty( $this->summary ) ) {
			$this->summary = __('SERVICE Appointment','appointments');
		}
		return $this->summary;
	}

	public function set_summary( $summary ) {
		$this->summary = $summary;
	}

	/**
	 * Return GCal description
	 *
	 * @since 1.2.1
	 *
	 *
	 * @return string
	 */
	public function get_description() {
		if ( empty( $this->description ) ) {
			$this->description = __("Client Name: CLIENT\nService Name: SERVICE\nService Provider Name: SERVICE_PROVIDER\n", "appointments");
		}

		return $this->description;
	}

	public function set_description( $description ) {
		$this->description = $description;
	}



	// Appointments Hooks
	public function on_insert_appointment( $app_id ) {
		if ( ! $this->is_connected() ) {
			return;
		}

		$app = appointments_get_appointment( $app_id );
		if ( $app->gcal_ID ) {
			// Prevent from creating the same appointment twice
			$_app = appointments_get_appointment_by_gcal_id( $app->gcal_ID );
			if ( $_app->ID == $app->ID ) {
				$this->on_update_appointment( $app->ID, array(), $app );
				return;
			}

		}
		$worker = appointments_get_worker( $app->worker );

		if ( $this->workers_allowed() && $worker && $this->switch_to_worker( $worker->ID ) ) {
			// The worker has a calendar assigned, let's insert
			$this->insert_event( $app_id );
			$this->restore_to_default();
		}
		elseif ( ( 'all' === $this->get_api_scope() ) || ( 'no_preference' === $this->get_api_scope() && ! $worker ) ) {
			// Insert in general calendar
			$this->insert_event( $app->ID );
		}

	}

	public function on_delete_appointment( $app ) {
		if ( ! $this->is_connected() ) {
			return;
		}

		if ( $app->gcal_ID ) {
			$this->delete_event( $app->gcal_ID );
		}
	}

	public function on_update_appointment( $app_id, $args, $old_app ) {
		if ( ! $this->is_connected() ) {
			return;
		}

		$app = appointments_get_appointment( $app_id );

		if ( ! $app->gcal_ID ) {
			// No GCal reference, let's insert
			$this->on_insert_appointment( $app->ID );
			return;
		}

		//Update GCal on status change
		$old_status = $old_app->status;
		$new_status = isset( $args['status'] ) ? $args['status'] : $old_status;

		if ( $old_status != $new_status ) {

			if ( $new_status == 'removed' || $new_status == 'pending' ) {
				$this->delete_event( $app->gcal_ID, $app_id );
			}

			if ( $old_status == 'removed' && $new_status != 'pending' ) {
				$this->insert_event( $app_id );
			}
		}

		if ( ! $this->workers_allowed() ) {
			// Just the general calendar
			$this->update_event( $app_id );
			return;
		}


		$old_worker_id = $old_app->worker;
		$worker_id = $app->worker;
		$worker_has_changed = ( absint( $app->worker ) != absint( $old_app->worker ) );

		if ( $worker_has_changed ) {
			// The worker has changed

			if ( $this->switch_to_worker( $old_worker_id ) ) {
				$event = $this->get_event( $app_id );
				if ( $event ) {
					// Remove the event from the previous worker
					$this->delete_event( $app->ID );
				}
				$this->restore_to_default();

				if ( $this->switch_to_worker( $worker_id ) ) {
					$event = $this->get_event( $app_id );
					if ( $event ) {
						// Update event in new worker
						$this->update_event( $app->ID );
					}
					else {
						// Insert in new worker
						$this->insert_event( $app_id );
					}
					$this->restore_to_default();
					return;
				}
				else {
					$event = $this->get_event( $app_id );
					if ( $event ) {
						// Update event in general calendar
						$this->update_event( $app->ID );
					}
					else {
						// Insert in general calendar
						$this->insert_event( $app_id );
					}
					return;
				}
			}
			else {
				if ( $this->switch_to_worker( $worker_id ) ) {
					$event = $this->get_event( $app_id );
					if ( $event ) {
						// Update event in new worker
						$this->update_event( $app->ID );
					}
					else {
						// Insert in new worker
						$this->insert_event( $app_id );
					}
					$this->restore_to_default();
					return;
				}
				else {
					$event = $this->get_event( $app_id );
					if ( $event ) {
						// Update event in general calendar
						$this->update_event( $app->ID );
					}
					else {
						// Insert in general calendar
						$this->insert_event( $app_id );
					}
					return;
				}
			}
		}
		else {
			if ( $this->switch_to_worker( $worker_id ) ) {
				$event = $this->get_event( $app_id );
				if ( $event ) {
					// Update event in new worker
					$this->update_event( $app->ID );
				}
				else {
					// Insert in new worker
					$this->insert_event( $app_id );
				}
				$this->restore_to_default();
			}
			else {
				$event = $this->get_event( $app_id );
				if ( $event ) {
					// Update event in general calendar
					$this->update_event( $app->ID );
				}
				else {
					// Insert in general calendar
					$this->insert_event( $app_id );
				}
			}
		}
	}


	// CRED functions
	public function get_event( $app_id ) {
		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return false;
		}

		if ( ! $app->gcal_ID ) {
			return false;
		}

		$event = $this->api_manager->get_event( $app->gcal_ID );

		if ( ! is_wp_error( $event ) ) {
			return $event;
		}

		return false;

	}

	public function insert_event( $app_id ) {
		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return false;
		}

		if ( ! $this->_is_writable_mode() ) {
			// We don't need to insert events on this mode
			return false;
		}

		if ( ! $this->is_syncable_status( $app->status ) ) {
			return false;
		}

		$event = $this->appointment_to_gcal_event( $app );

		/**
		 * Allow filtering a Google_Service_Calendar_Event object before being updated
		 * on GCal
		 *
		 * Google_Service_Calendar_Event $event
		 * Appointments_Appointment $app The related appointment to this event
		 */
		$event = apply_filters( 'appointments_gcal_insert_event', $event, $app );

		if ( ! $event ) {
			return false;
		}

		$result = $this->api_manager->insert_event( $event );

		if ( is_wp_error( $result ) ) {
			return false;
		}
		else {
			$args = array( 'gcal_updated' => current_time( 'mysql' ), 'gcal_ID' => $result );
			$this->remove_appointments_hooks();
			appointments_update_appointment( $app_id, $args );
			$this->add_appointments_hooks();
		}

		return $result;
	}

	public function delete_event( $event_id, $app_id = false ) {
		if ( ! $this->_is_writable_mode() ) {
			// We don't need to delete events on this mode
			return false;
		}

		if( $app_id && $this->workers_allowed() ){
			$app = appointments_get_appointment( $app_id );
			$this->api_manager->switch_to_worker( $app->worker ); 
		}

		$this->api_manager->delete_event( $event_id );

		$app = appointments_get_appointment_by_gcal_id( $event_id );
		if ( $app ) {
			$this->remove_appointments_hooks();
			appointments_update_appointment( $app->ID, array( 'gcal_ID' => '' ) );
			$this->add_appointments_hooks();
		}

		$this->restore_to_default();

		return true;

	}

	public function update_event( $app_id ) {
		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return false;
		}

		if ( ! $this->_is_writable_mode() ) {
			// We don't need to insert events on this mode
			return false;
		}

		if ( ! $this->is_syncable_status( $app->status ) ) {
			return false;
		}

		$event_id = $app->gcal_ID;
		if ( ! $event_id ) {

			// Insert it!
			$result = $this->insert_event( $app_id );
			if ( ! $result ) {
				return false;
			}

			return true;
		}

		// Only update some of the fields
		$event = $this->get_event( $app->ID );
		if ( is_wp_error( $event ) ) {
			return false;
		}

		$options = appointments_get_options();
		$gcal_overwrite = $options['gcal_overwrite'];

		// Location
		if ( isset( $options["gcal_location"] ) && '' != trim( $options["gcal_location"] ) ) {
			$location = str_replace( array( 'ADDRESS', 'CITY' ), array(
				$app->address,
				$app->city
			), $options["gcal_location"] );
		} else {
			$location = get_bloginfo( 'description' );
		}

		$location = apply_filters( 'appointments_gcal_event_location', $location, $app );

		if ( $gcal_overwrite ) {
			// Overwrite title and description
			$event = $this->appointment_to_gcal_event( $app );
		}
		else {
			$start = new Google_Service_Calendar_EventDateTime();
			$start->setDateTime( $app->get_start_gmt_date( "Y-m-d\TH:i:s\Z" ) );
			$end = new Google_Service_Calendar_EventDateTime();
			$end->setDateTime( $app->get_end_gmt_date( "Y-m-d\TH:i:s\Z" ) );
			$event->setStart( $start );
			$event->setEnd( $end );
		}

		$event->setLocation( $location );

		/**
		 * Allow filtering a Google_Service_Calendar_Event object before being updated
		 * on GCal
		 *
		 * Google_Service_Calendar_Event $event
		 * Appointments_Appointment $app The related appointment to this event
		 */
		$event = apply_filters( 'appointments_gcal_update_event', $event, $app );

		if ( ! $event ) {
			return false;
		}

		$result = $this->api_manager->update_event( $event_id, $event );


		if ( is_wp_error( $result ) ) {
			return false;
		}
		else {
			$args = array( 'gcal_updated' => current_time( 'mysql' ) );
			$this->remove_appointments_hooks();
			appointments_update_appointment( $app_id, $args );
			$this->add_appointments_hooks();
		}

		return true;
	}


	public function get_events_list() {
		global $appointments;

		$current_time = current_time( 'timestamp' ) - ( 3600 * 1 ); // Let's get also appointments that were 1 hours ago
		$args = array(
			'timeMin' => apply_filters( 'app_gcal_time_min', date_i18n( DATE_ATOM, $current_time ) ),
			'timeMax' => apply_filters( 'app_gcal_time_max', date_i18n( DATE_ATOM, $current_time + ( 3600 * 24 * $appointments->get_app_limit() ) ) ),
			'singleEvents' => apply_filters( 'app_gcal_single_events', true ),
			'maxResults'   => apply_filters( 'app_gcal_max_results', APP_GCAL_MAX_RESULTS_LIMIT ),
			'orderBy'      => apply_filters( 'app_gcal_orderby', 'startTime' ),
			'timeZone' => 'GMT'
		);

		$events = $this->api_manager->get_events_list( $args );

		return $events;
	}



}

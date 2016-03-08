<?php
/**
 * Created by PhpStorm.
 * User: ignacio
 * Date: 2/3/16
 * Time: 13:43
 */

class Appointments_Google_Calendar {

	public $api_manager = false;

	public $errors = array();

	public $admin;

	private $api_mode = 'none';

	private $access_code;

	private $description = '';

	private $summary = '';

	public $worker_id = false;

	/**
	 * Checks if we are set to a worker calendar
	 *
	 * @var bool
	 */
	private $worker_calendar = false;

	public function __construct() {
		$appointments = appointments();

		// Try to start a session. If cannot, log it.
		if ( ! session_id() && ! @session_start() ) {
			$appointments->log( __( 'Session could not be started. This may indicate a theme issue.', 'appointments' ) );
		}

		include_once( 'gcal/class-app-gcal-admin.php' );
		$this->admin = new Appointments_Google_Calendar_Admin( $this );

		add_action( 'wp_ajax_app_gcal_export', array( $this, 'export_batch' ) );
		add_action( 'wp_ajax_app_gcal_import', array( $this, 'import' ) );

		$options = appointments_get_options();

		if ( isset( $options['gcal_api_mode'] ) ) {
			$this->api_mode = $options['gcal_api_mode'];
		}

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

		// Appointments Hooks
		$this->add_appointments_hooks();
	}

	public function add_appointments_hooks() {
		if ( ! $this->is_connected() ) {
			return;
		}

		add_action( 'wpmudev_appointments_insert_appointment', array( $this, 'insert_event' ) );
		add_action( 'wpmudev_appointments_update_appointment_status', array( $this, 'on_update_appointment' ) );
		add_action( 'wpmudev_appointments_update_appointment', array( $this, 'on_update_appointment' ) );
		add_action( 'appointments_delete_appointment', array( $this, 'delete_event' ) );
	}

	public function remove_appointments_hooks() {
		remove_action( 'wpmudev_appointments_insert_appointment', array( $this, 'insert_event' ) );
		remove_action( 'wpmudev_appointments_update_appointment_status', array( $this, 'on_update_appointment' ) );
		remove_action( 'wpmudev_appointments_update_appointment', array( $this, 'on_update_appointment' ) );
		remove_action( 'appointments_delete_appointment', array( $this, 'delete_event' ) );
	}

	public function is_connected() {
		$access_token = json_decode( $this->api_manager->get_access_token() );
		if ( ! $access_token ) {
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
		if ( 'sync' != $this->get_api_mode() ) {
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
					if ( ! is_wp_error( $results ) ) {
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
		return apply_filters( 'app-gcal-syncable_status', array( 'paid', 'confirmed' ) );
	}

	public function is_syncable_status( $status ) {
		$syncable_status = $this->get_syncable_status();
		return in_array( $status, $syncable_status );
	}

	public function switch_to_worker( $worker_id ) {
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

		if ( ! $this->is_connected() ) {
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
	public function on_update_appointment( $app_id ) {
		$app = appointments_get_appointment( $app_id );

		if ( ( 'pending' == $app->status || 'removed' == $app->status || 'completed' == $app->status ) ) {
			$this->delete_event( $app_id );
		}
		else {
			$this->update_event( $app_id );
		}
	}


	// CRED functions
	public function insert_event( $app_id ) {
		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return false;
		}

		$switched = false;
		if ( $app->worker && appointments_get_worker( $app->worker ) ) {
			$switched = $this->switch_to_worker( $app->worker );
		}

		if ( ! $this->_is_writable_mode() ) {
			// We don't need to insert events on this mode
			return false;
		}

		if ( ! $this->is_syncable_status( $app->status ) ) {
			return false;
		}

		$event = $this->appointment_to_gcal_event( $app );

		$result = $this->api_manager->insert_event( $event );

		if ( $switched ) {
			$this->restore_to_default();
		}

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

	public function delete_event( $app_id ) {
		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return false;
		}

		$switched = false;
		if ( $app->worker && appointments_get_worker( $app->worker ) ) {
			$switched = $this->switch_to_worker( $app->worker );
		}

		if ( ! $this->_is_writable_mode() ) {
			// We don't need to insert events on this mode
			return false;
		}

		$event_id = $app->gcal_ID;
		if ( ! $event_id ) {
			return false;
		}

		$this->api_manager->delete_event( $event_id );

		if ( $switched ) {
			$this->restore_to_default();
		}

		return true;

	}

	public function update_event( $app_id ) {
		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return false;
		}

		$switched = false;
		if ( $app->worker && appointments_get_worker( $app->worker ) ) {
			$switched = $this->switch_to_worker( $app->worker );
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

			if ( $switched ) {
				$this->restore_to_default();
			}

			// Insert it!
			$result = $this->insert_event( $app_id );
			if ( ! $result ) {
				return false;
			}

			return true;
		}

		$event = $this->appointment_to_gcal_event( $app );
		$result = $this->api_manager->update_event( $event_id, $event );

		if ( $switched ) {
			$this->restore_to_default();
		}

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

		$current_time = current_time( 'timestamp' );
		$args = array(
			'timeMin'      => apply_filters( 'app_gcal_time_min', date( "c", $current_time ) ),
			'timeMax'      => apply_filters( 'app_gcal_time_max', date( "c", $current_time + ( 3600 * 24 * $appointments->get_app_limit() ) ) ),
			'singleEvents' => apply_filters( 'app_gcal_single_events', true ),
			'maxResults'   => apply_filters( 'app_gcal_max_results', APP_GCAL_MAX_RESULTS_LIMIT ),
			'orderBy'      => apply_filters( 'app_gcal_orderby', 'startTime' ),
		);

		$events = $this->api_manager->get_events_list( $args );

		return $events;
	}



}
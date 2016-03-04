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

	private $api_mode = 'none';

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

		add_action( 'admin_init', array( &$this, 'save_settings' ), 12 );
		add_action( 'admin_init', array( &$this, 'reset_settings' ), 12 );

		$options = appointments_get_options();

		if ( isset( $options['gcal_api_mode'] ) ) {
			$this->api_mode = $options['gcal_api_mode'];
		}

		include_once( 'class-app-gcal-api-manager.php' );
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

		if ( ! empty( $options['gcal_access_code'] ) ) {
			$default_creds['access_code'] = $options['gcal_access_code'];
		}

		if ( ! empty( $options['gcal_selected_calendar'] ) ) {
			$default_creds['calendar_id'] = $options['gcal_selected_calendar'];
			$this->api_manager->set_calendar( $options['gcal_selected_calendar'] );
		}

		$this->api_manager->set_default_credentials( $default_creds );

		add_action( 'shutdown', array( $this, 'save_new_token' ) );

		// Appointments Hooks
		$this->add_appointments_hooks();
	}

	public function add_appointments_hooks() {
		if ( ! $this->is_connected() ) {
			return false;
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
		if ( ! $this->api_manager->get_access_token() ) {
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
				self::get_summary( $app->worker ),
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
				self::get_description( $app->worker ),
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

	/**
	 * Revoke access and reset Google Calendar settings
	 */
	public function reset_settings() {
		if ( ! isset( $_POST['app-reset-gcalendar'] ) ) {
			return;
		}

		check_admin_referer( 'app-submit-gcalendar' );

		$result = $this->api_manager->revoke_token();
		if ( ! is_wp_error( $result ) ) {
			$options = appointments_get_options();
			$options['gcal_client_id'] = '';
			$options['gcal_client_secret'] = '';
			$options['gcal_accesss_code'] = '';
			$options['gcal_selected_calendar'] = '';
			$options['gcal_token'] = '';
			appointments_update_options( $options );
		}
		else {
			add_settings_error( 'app-gcalendar', $result->get_error_code(), $result->get_error_message() );
		}

	}

	public function save_settings() {
		if ( ! isset( $_POST['app-submit-gcalendar'] ) ) {
			return;
		}

		check_admin_referer( 'app-submit-gcalendar' );

		$options = appointments_get_options();
		$action = $_POST['action'];
		if ( 'step-1' === $action ) {
			if ( empty( $_POST['client_id'] ) || empty( $_POST['client_secret'] ) ) {
				add_settings_error( 'app-gcalendar', 'empty-fields', __( 'All fields are mandatory', 'appointments' ) );
				return;
			}

			$options['gcal_client_id'] = $_POST['client_id'];
			$options['gcal_client_secret'] = $_POST['client_secret'];
			$this->api_manager->set_client_id_and_secret( $options['gcal_client_id'], $options['gcal_client_secret'] );
			appointments_update_options( $options );
		}
		elseif ( 'step-2' === $action ) {
			if ( empty( $_POST['access_code'] ) ) {
				add_settings_error( 'app-gcalendar', 'empty-fields', __( 'All fields are mandatory', 'appointments' ) );
				return;
			}

			$result = $this->api_manager->authenticate( $_POST['access_code'] );
			if ( is_wp_error( $result ) ) {
				add_settings_error( 'app-gcalendar', $result->get_error_code(), sprintf( __( 'Authentication failed: %s', 'appointments' ), $result->get_error_message() ) );
				return;
			}

			$options['gcal_access_code'] = $_POST['access_code'];
			$options['gcal_token'] = $this->api_manager->get_access_token();
			appointments_update_options( $options );
		}
		elseif ( 'step-3' === $action ) {
			$calendar_id = ! empty( $_POST['gcal_selected_calendar'] ) ? $_POST['gcal_selected_calendar'] : '';
			if ( ! $calendar_id ) {
				return;
			}

			$options['gcal_selected_calendar'] = $calendar_id;
			appointments_update_options( $options );
			$calendar = $this->api_manager->get_calendar();

			if ( is_wp_error( $calendar ) ) {
				add_settings_error( 'app-gcalendar', 'calendar-not-exist', __( 'The selecter calendar does not exist', 'appointments' ) );
				$options['gcal_selected_calendar'] = '';
				appointments_update_options( $options );
			}


		}
	}

	public function render_tab() {
		$options = appointments_get_options();
		$client_id = isset( $options['gcal_client_id'] ) ? $options['gcal_client_id'] : '';
		$client_secret = isset( $options['gcal_client_secret'] ) ? $options['gcal_client_secret'] : '';
		$errors = array_merge( get_settings_errors( 'app-gcalendar' ), $this->errors );
		$token = $this->api_manager->get_access_token();
		if ( ! empty( $errors ) ) {
			?>
			<div class="error">
				<ul>
					<?php foreach ( $errors as $error ): ?>
						<li><?php echo $error['message']; ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
		}

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			?>
			<form name="input" action="" method="post">
				<h3>Create a new Google Application</h3>
				<ol>
					<li>Go to <a target=_blank" href="https://console.developers.google.com/project">Google Developer Console Projects</a> and create a new project. i.e. "Appointments APP"</li>
					<li>Once in Dashboard, click on Enable and manage APIs, click on Calendar API and then, enable.</li>
					<li>On the left side, click on Credentials and then OAuth consent screen tab</li>
					<li>Choose a product name shown to users, i.e. "Appointments +"</li>
					<li>click on Credentials tab > Create Credentials > OAuth Client ID</li>
					<li>Select "Other" Application type with any name</li>
					<li>Take note of the client ID and client secret and fill the form below</li>
				</ol>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="app-client-id"><?php _e( 'Client ID', 'appointments' ); ?></label>
						</th>
						<td>
							<input type="text" class="widefat" name="client_id" id="app-client-id" value="">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="app-client-secret"><?php _e( 'Client Secret', 'appointments' ); ?></label>
						</th>
						<td>
							<input type="text" name="client_secret" class="widefat" id="app-client-secret" value="">
						</td>
					</tr>
				</table>

				<?php wp_nonce_field( 'app-submit-gcalendar' ); ?>
				<input type="hidden" name="action" value="step-1">
				<?php submit_button( __( 'Submit', 'appointments' ), 'primary', 'app-submit-gcalendar' ); ?>
			</form>
			<?php
		}
		elseif ( $client_id && $client_secret && ! $token ) {
			// No token yet
			?>
			<form name="input" action="" method="post">
				<h3>Authorize access to your Google Application</h3>
				<ol>
					<li><a href="<?php echo esc_url( $this->api_manager->create_auth_url() ); ?>" target="_blank"><?php _e( 'Generate your access code', 'appointments' ); ?></a></li>
					<li><?php _e( 'Fill the form below', 'appointments' ); ?></li>
				</ol>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="app-access-code"><?php _e( 'Access code', 'appointments' ); ?></label>
						</th>
						<td>
							<input type="text" class="widefat" name="access_code" id="app-access-code" value="">
						</td>
					</tr>
				</table>

				<?php wp_nonce_field( 'app-submit-gcalendar' ); ?>
				<input type="hidden" name="action" value="step-2">
				<p class="submit">
					<?php submit_button( __( 'Submit', 'appointments' ), 'primary', 'app-submit-gcalendar', false ); ?>
					<?php submit_button( __( 'Reset', 'appointments' ), 'secondary', 'app-reset-gcalendar', false ); ?>
				</p>
			</form>
			<?php
		}
		else {
			$calendars = $this->api_manager->get_calendars_list();
			$selected_calendar = $this->api_manager->get_calendar();
			?>
			<form name="input" action="" method="post">
				<h3>Select Your Calendar</h3>
				<p><?php _e( 'Select the Calendar you want to work with Appointments', 'appointments' ); ?></p>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="app-calendar"><?php _e( 'Calendar', 'appointments' ); ?></label>
						</th>
						<td>
							<select name="gcal_selected_calendar" id="app-calendar">
								<option value=""><?php _e( '-- Select a Calendar --', 'appointments' ); ?></option>
								<?php foreach ( $calendars as $calendar ): ?>
									<option value="<?php echo esc_attr( $calendar['id'] ); ?>" <?php selected( $selected_calendar, $calendar['id'] ); ?>>
										<?php echo $calendar['summary']; ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>

				<?php wp_nonce_field( 'app-submit-gcalendar' ); ?>
				<input type="hidden" name="action" value="step-3">
				<p class="submit">
					<?php submit_button( __( 'Submit', 'appointments' ), 'primary', 'app-submit-gcalendar', false ); ?>
					<?php submit_button( __( 'Reset', 'appointments' ), 'secondary', 'app-reset-gcalendar', false ); ?>
				</p>
			</form>
			<?php
		}
	}


	/**
	 * Return GCal API mode (none, app2gcal or sync )
	 *
	 * @return string
	 */
	function get_api_mode() {
		return $this->api_mode;
	}

	private function _is_writable_mode() {
		$mode = $this->get_api_mode();
		return ! in_array( $mode, array( 'gcal2app', 'none' ) );
	}

	private function _get_syncable_status () {
		return apply_filters( 'app-gcal-syncable_status', array( 'paid', 'confirmed' ) );
	}

	public function is_syncable_status( $status ) {
		$syncable_status = $this->_get_syncable_status();
		return in_array( $status, $syncable_status );
	}

	public function switch_to_worker( $worker_id ) {
		$worker = appointments_get_worker( $worker_id );
		if ( ! $worker ) {
			return false;
		}

		$worker_api_mode = get_user_meta( $worker_id, 'app_api_mode', true );
		if ( ! $worker_api_mode ) {
			$worker_api_mode = 'none';
		}

		// Set the API Mode
		$this->api_mode = $worker_api_mode;
		$this->api_manager->switch_to_worker( $worker_id );

		$this->worker_calendar = true;

		return true;
	}

	public function restore_to_default() {
		$options = appointments_get_options();

		// Set the API Mode
		$this->api_mode = $options['gcal_api_mode'];
		$this->api_manager->restore_to_default();

		$this->worker_calendar = false;

		return true;
	}


	/**
	 * Return GCal Summary (name of Event)
	 *
	 * @since 1.2.1
	 *
	 * @param integer $worker_id Optional worker ID whose data will be restored
	 *
	 * @return string
	 */
	public static function get_summary( $worker_id = 0 ) {
		$options = appointments_get_options();
		$text = '';
		if ( $worker_id ) {
			$text = get_user_meta( $worker_id, 'app_gcal_summary', true );
		}
		if ( empty( $text ) ) {
			$text = ! empty( $options['gcal_summary'] )
				? $options['gcal_summary']
				: '';
		}

		return $text;
	}

	/**
	 * Return GCal description
	 *
	 * @since 1.2.1
	 *
	 * @param integer $worker_id Optional worker ID whose data will be restored
	 *
	 * @return string
	 */
	public static function get_description( $worker_id = 0 ) {
		$options = appointments_get_options();

		$text = '';
		if ( $worker_id && ! empty( $options['gcal_api_allow_worker'] ) && 'yes' == $options['gcal_api_allow_worker'] ) {
			$text = get_user_meta( $worker_id, 'app_gcal_description', true );
		}
		if ( empty( $text ) ) {
			$text = ! empty( $options['gcal_description'] )
				? $options['gcal_description']
				: '';
		}

		return $text;
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
	public function get_event( $app_id ) {
		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return false;
		}

		$event_id = $app->gcal_ID;
		if ( ! $event_id ) {
			return false;
		}

		$event = $this->api_manager->get_event( $event_id );

		if ( is_wp_error( $event ) ) {
			return false;
		}

		return $event;
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

	public function delete_event( $app_id ) {
		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return false;
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

		$event = $this->appointment_to_gcal_event( $app );
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

		$current_time = current_time( 'timestamp' );
		$args = array(
			'timeMin'      => apply_filters( 'app_gcal_time_min', date( "c", $current_time ) ),
			'timeMax'      => apply_filters( 'app_gcal_time_max', date( "c", $current_time + ( 3600 * 24 * $appointments->get_app_limit() ) ) ),
			'singleEvents' => apply_filters( 'app_gcal_single_events', true ),
			'maxResults'   => apply_filters( 'app_gcal_max_results', APP_GCAL_MAX_RESULTS_LIMIT ),
			'orderBy'      => apply_filters( 'app_gcal_orderby', 'startTime' ),
		);

		$result = $this->api_manager->get_events_list( $args );

		if ( is_wp_error( $result ) ) {
			return array();
		}

		return $events;


	}

}
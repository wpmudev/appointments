<?php


class Appointments_Google_Calendar_API_Manager {

	/**
	 * @var bool|Google_Client
	 */
	private $client = false;

	/**
	 * @var bool|Google_Service_Calendar
	 */
	private $service = false;

	/**
	 * Default credentials
	 *
	 * useful when we need to swtich to worker credentials and
	 * then set the defaults back
	 *
	 * @var array
	 */
	private $default_creds = array(
		'client_id' => '',
		'client_secret' => '',
		'token' => '',
		'access_code' => '',
		'calendar_id' => ''
	);

	/**
	 * Calendar ID
	 *
	 * @var string
	 */
	private $calendar = '';

	public function __construct() {
		include_once( appointments_plugin_dir() . 'includes/external/google/autoload.php' );
		$this->client = new Google_Client();
		$this->client->setApplicationName( "Appointments +" );
		$this->client->setScopes( 'https://www.googleapis.com/auth/calendar' );
		$this->client->setAccessType( 'offline' );
		$this->client->setRedirectUri( 'urn:ietf:wg:oauth:2.0:oob' );

		$this->service = new Google_Service_Calendar( $this->client );
	}

	/**
	 * Return the Google Client Instance
	 *
	 * @return Google_Client
	 */
	public function get_client() {
		return $this->client;
	}

	/**
	 * Return the Google Client Instance
	 *
	 * @return Google_Client
	 */
	public function get_service() {
		return $this->service;
	}

	/**
	 * Return the selected Calendar ID
	 *
	 * @return string
	 */
	public function get_calendar() {
		return $this->calendar;
	}

	/**
	 * Save the default credentials
	 *
	 * useful when we need to swtich to worker credentials and
	 * then set the defaults back
	 *
	 * @param array $args
	 */
	public function set_default_credentials( $args = array() ) {
		$this->default_creds = wp_parse_args( $args, $this->default_creds );
	}

	/**
	 * Sets the Client ID and Client Secret for this session
	 *
	 * @param string $client_id
	 * @param string $client_secret
	 */
	public function set_client_id_and_secret( $client_id, $client_secret ) {
		$this->client->setClientId( $client_id );
		$this->client->setClientSecret( $client_secret );
	}

	/**
	 * Sets the access token for this session
	 *
	 * @param string $token JSON string
	 *
	 * @return bool|WP_Error
	 */
	public function set_access_token( $token ) {
		try {
			$this->client->setAccessToken( $token );
		}
		catch ( Exception $e ) {
			return new WP_Error( 'app-gcal-set-token', $e->getMessage() );
		}

		return true;

	}

	/**
	 * Get the current session token
	 *
	 * @return string JSON string
	 */
	public function get_access_token() {
		return $this->client->getAccessToken();
	}


	/**
	 * Revoke the current session token
	 *
	 * @return bool|WP_Error
	 */
	public function revoke_token() {
		if ( $this->client->getAccessToken() ) {
			try {
				$this->client->revokeToken();
			}
			catch ( Exception $e ) {
				return new WP_Error( $e->getCode(), $e->getMessage() );
			}
		}

		return true;
	}

	public function is_token_expired() {
		return $this->client->isAccessTokenExpired();
	}

	/**
	 * Try to authenticate into Google by passing an access code
	 *
	 * @param string $access_code
	 *
	 * @return bool|WP_Error
	 */
	public function authenticate( $access_code ) {
		try {
			$this->client->authenticate( $access_code );
		}
		catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}

		return true;
	}

	/**
	 * Set the current selected calendar ID
	 *
	 * @param string $calendar_id
	 */
	public function set_calendar( $calendar_id ) {
		$this->calendar = $calendar_id;
	}

	/**
	 * Generate an authorization URL where the user can allow Appointments to access Google API
	 *
	 * @return string|WP_Error
	 */
	public function create_auth_url() {
		return $this->client->createAuthUrl();
	}

	/**
	 * Return an array of calendars in user's Google account
	 *
	 * @return array|WP_Error
	 */
	public function get_calendars_list() {
		try {
			$calendars = $this->service->calendarList->listCalendarList();
		}
		catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}

		return $calendars->getItems();
	}


	/**
	 * Get the Google Calendar Data
	 *
	 * @return mixed|WP_Error
	 */
	public function get_calendar_details() {
		if ( ! $this->get_calendar() ) {
			return new WP_Error( 'calendar-error', __( 'There is not any Calendar selected', 'appointments' ) );
		}

		try {
			$details = $this->service->calendars->get( $this->get_calendar() );
		}
		catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}

		return $details;
	}

	/**
	 * Get a Google Event
	 *
	 * @param string $event_id
	 *
	 * @return Google_Service_Calendar_Event|WP_Error
	 */
	public function get_event( $event_id ) {
		try {
			$event = $this->service->events->get( $this->get_calendar(), $event_id );
		}
		catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}

		return $event;
	}

	/**
	 * Insert a new event
	 *
	 * @param $event
	 *
	 * @return WP_Error|string
	 */
	public function insert_event( $event ) {
		try {
			$created_event = $this->service->events->insert( $this->get_calendar(), $event );
			return $created_event->getId();
		}
		catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Update an event
	 *
	 * @param string $event_id
	 * @param Google_Service_Calendar_Event $event
	 *
	 * @return WP_Error|string
	 */
	public function update_event( $event_id, $event ) {
		try {
			$updated_event = $this->service->events->update( $this->get_calendar(), $event_id, $event );
			return $updated_event->getId();
		}
		catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Update an event
	 *
	 * @param $event
	 *
	 * @return WP_Error|string
	 */
	public function delete_event( $event_id ) {
		try {
			$this->service->events->delete( $this->get_calendar(), $event_id );
			return true;
		}
		catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	public function get_events_list( $args ) {
		try {
			$events = $this->service->events->listEvents( $this->get_calendar(), $args );
			return $events->getItems();
		}
		catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}


	public function switch_to_worker( $worker_id ) {
		$worker = appointments_get_worker( $worker_id );
		if ( ! $worker ) {
			return false;
		}

		$worker_calendar_id = 0;
		$worker_client_id = '';
		$worker_secret_id = '';
		$worker_token = '';
		$this->set_calendar( $worker_calendar_id );
		$this->set_client_id_and_secret( $worker_client_id, $worker_secret_id );
		$this->set_access_token( $worker_token );
	}

	public function restore_to_default() {
		$this->set_calendar( $this->default_creds['calendar_id'] );
		$this->set_client_id_and_secret( $this->default_creds['client_id'], $this->default_creds['client_secret'] );
		$this->set_access_token( $this->default_creds['token'] );
	}



}
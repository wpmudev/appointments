<?php
/**
 * Created by PhpStorm.
 * User: ignacio
 * Date: 2/3/16
 * Time: 13:43
 */

class Appointments_Google_Calendar {

	public function __construct() {
		$appointments = appointments();

		// Try to start a session. If cannot, log it.
		if ( ! session_id() && ! @session_start() ) {
			$appointments->log( __( 'Session could not be started. This may indicate a theme issue.', 'appointments' ) );
		}

		// Create the folder in case it does not exist yet
		$this->create_key_file_folder();

		add_action( 'init', array( &$this, 'init' ), 12 );


	}

	public function init() {
		$options = appointments_get_options();
		include_once( 'external/google/autoload.php' );

		$worker_id = 0;
		$calendar_id = $options['gcal_selected_calendar'];

		try {
			$credentials = new Google_Auth_AssertionCredentials(
				$options['gcal_service_account'],
				array( 'https://www.googleapis.com/auth/sqlservice.admin' ),
				$this->get_key_file_contents()
			);

			$client = new Google_Client();
			$client->setApplicationName( "Appointments+" );
			$client->setAssertionCredentials( $credentials );

			if ( $client->getAuth()->isAccessTokenExpired() ) {
				$client->getAuth()->refreshTokenWithAssertion();
			}

			$service = new Google_Service_Calendar( $client );
			$calendars = $service->events->listEvents( $calendar_id );
		}
		catch ( Exception $e ) {
			$message = $e->getMessage();
		}

	}

	/**
	 * Return GCal API mode (none, app2gcal or sync )
	 *
	 * @param integer $worker_id Optional worker ID whose data will be restored
	 * @return string
	 */
	function get_api_mode( $worker_id = 0 ) {

		if ( ! $worker_id ) {
			$options = appointments_get_options();
			if ( isset( $options['gcal_api_mode'] ) ) {
				return $options['gcal_api_mode'];
			} else {
				return 'none';
			}
		}
		else {
			$meta = get_user_meta( $worker_id, 'app_api_mode', true );
			if ( $meta ) {
				return $meta;
			} else {
				return 'none';
			}
		}
	}

	/**
	 * Try to create an encrypted key file folder
	 * @return string
	 * @since 1.2.2
	 */
	function create_key_file_folder( ) {
		if ( $this->is_key_file_folder_created() ) {
			//return;
		}

		$path = $this->get_key_file_folder();
		@mkdir( $path );
		@copy( appointments_plugin_dir() . 'includes/gcal/key/index.php', $this->get_key_file_folder() . 'index.php' );

	}

	/**
	 * Return key file folder name
	 * @return string
	 * @since 1.2.2
	 */
	function get_key_file_folder() {
		$uploads = wp_upload_dir();
		$base = trailingslashit( $uploads["basedir"] );
		if ( defined( 'AUTH_KEY' ) ) {
			$kff = $base . md5( 'AUTH_KEY' ) . '/' ;
			if ( is_dir( $kff ) ) {
				return $kff;
			}

		}
		return $base . '__app/';
	}

	function is_key_file_folder_created() {
		return is_dir( $this->get_key_file_folder() );
	}

	function get_key_file_contents() {
		$options = appointments_get_options();
		if ( ! isset( $options['gcal_key_file'] ) ) {
			return false;
		}

		$file = $this->get_key_file_folder() . $options['gcal_key_file'] . '.p12';
		if ( ! is_readable( $file ) ) {
			return false;
		}

		return file_get_contents( $file );
	}
}
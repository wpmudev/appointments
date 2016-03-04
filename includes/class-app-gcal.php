<?php
/**
 * Created by PhpStorm.
 * User: ignacio
 * Date: 2/3/16
 * Time: 13:43
 */

class Appointments_Google_Calendar {

	private $client = false;

	private $calendar = false;

	private $service = false;

	public $api_manager = false;

	public $errors = array();

	public function __construct() {
		$appointments = appointments();

		// Try to start a session. If cannot, log it.
		if ( ! session_id() && ! @session_start() ) {
			$appointments->log( __( 'Session could not be started. This may indicate a theme issue.', 'appointments' ) );
		}

		// Create the folder in case it does not exist yet
		$this->create_key_file_folder();

		add_action( 'admin_init', array( &$this, 'save_settings' ), 12 );
		add_action( 'admin_init', array( &$this, 'reset_settings' ), 12 );

		//add_action( 'init', array( &$this, 'init' ), 12 );

		$options = appointments_get_options();

		include_once( 'class-app-gcal-api-manager.php' );
		$this->api_manager = new Appointments_Google_Calendar_API_Manager();

		if ( ! empty( $options['gcal_client_id'] ) && ! empty( $options['gcal_client_secret'] ) ) {
			$this->api_manager->set_client_id_and_secret( $options['gcal_client_id'], $options['gcal_client_secret'] );
		}

		if ( ! empty( $options['gcal_token'] ) ) {
			$result = $this->api_manager->set_access_token( $options['gcal_token'] );
			if ( is_wp_error( $result ) ) {
				$this->errors[] = array( 'message' => sprintf( __( 'Error validating the access token: %s', 'appointments' ), $result->get_error_message() ) );
			}
		}

		if ( ! empty( $options['gcal_selected_calendar'] ) ) {
			$this->api_manager->set_calendar( $options['gcal_selected_calendar'] );
		}

		add_action( 'shutdown', array( $this, 'save_new_token' ) );
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
			$calendar = $this->get_selected_calendar( 'all' );

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

	public function get_calendar_service() {
		if ( ! $this->service && $this->client ) {
			$this->service = new Google_Service_Calendar( $this->client );
		}

		return $this->service;
	}


	/**
	 * Return the selected calendar
	 *
	 * @param string $field id|all
	 *
	 * @return bool|Google_Client
	 */
	public function get_selected_calendar( $field = 'id' ) {
		$options = appointments_get_options();
		if ( empty( $options['gcal_selected_calendar'] ) ) {
			return false;
		}

		if ( $field === 'id' ) {
			return $options['gcal_selected_calendar'];
		}

		$service = $this->get_calendar_service();
		try {
			$calendar = $service->calendarList->get( $options['gcal_selected_calendar'] );
		}
		catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
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
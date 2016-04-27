<?php

class Appointments_Google_Calendar_Admin {

	/**
	 * Appointments_Google_Calendar_Admin constructor.
	 *
	 * @param Appointments_Google_Calendar $gcal_api
	 */
	public function __construct( $gcal_api ) {
		$this->gcal_api = $gcal_api;

		add_action( 'admin_init', array( $this, 'save_settings' ), 12 );
		add_action( 'admin_init', array( $this, 'reset_settings' ), 12 );

		add_action( 'show_user_profile', array( $this, 'show_profile') );
		add_action( 'edit_user_profile', array( $this, 'show_profile') );

		add_action( 'personal_options_update', array( $this, 'save_profile') );
		add_action( 'edit_user_profile_update', array( $this, 'save_profile') );
	}

	public function save_settings() {
		if ( ! isset( $_POST['app-submit-gcalendar'] ) ) {
			return;
		}

		check_admin_referer( 'app-submit-gcalendar' );

		$options = appointments_get_options();

		$options['gcal_location'] = sanitize_text_field( $_POST['gcal_location'] );
		$options['gcal_same_window'] = isset( $_POST['gcal_same_window'] );
		$options['gcal'] = $_POST['gcal'] === 'yes';
		appointments_update_options( $options );

		$action = $_POST['action'];
		if ( 'step-1' === $action ) {
			if ( empty( $_POST['client_id'] ) || empty( $_POST['client_secret'] ) ) {
				add_settings_error( 'app-gcalendar', 'empty-fields', __( 'All fields are mandatory', 'appointments' ) );
				return;
			}

			$options['gcal_client_id'] = $_POST['client_id'];
			$options['gcal_client_secret'] = $_POST['client_secret'];
			$this->gcal_api->api_manager->set_client_id_and_secret( $options['gcal_client_id'], $options['gcal_client_secret'] );
			appointments_update_options( $options );
		}
		elseif ( 'step-2' === $action ) {
			if ( empty( $_POST['access_code'] ) ) {
				add_settings_error( 'app-gcalendar', 'empty-fields', __( 'All fields are mandatory', 'appointments' ) );
				return;
			}

			$result = $this->gcal_api->api_manager->authenticate( $_POST['access_code'] );
			if ( is_wp_error( $result ) ) {
				add_settings_error( 'app-gcalendar', $result->get_error_code(), sprintf( __( 'Authentication failed: %s', 'appointments' ), $result->get_error_message() ) );
				return;
			}

			$options['gcal_access_code'] = $_POST['access_code'];
			$options['gcal_token'] = $this->gcal_api->api_manager->get_access_token();
			appointments_update_options( $options );
		}
		elseif ( 'step-3' === $action ) {
			$calendar_id = ! empty( $_POST['gcal_selected_calendar'] ) ? $_POST['gcal_selected_calendar'] : '';
			$options['gcal_selected_calendar'] = $calendar_id;
			$this->gcal_api->api_manager->set_calendar( $calendar_id );
			$options['gcal_api_mode'] = $_POST['gcal_api_mode'];
			$this->gcal_api->api_mode = $options['gcal_api_mode'];
			$options['gcal_api_allow_worker'] = $_POST['gcal_api_allow_worker'];
			$options['gcal_api_scope'] = $_POST['gcal_api_scope'];
			$options['gcal_description'] = $_POST['gcal_description'];
			$options['gcal_summary'] = $_POST['gcal_summary'];
			$this->gcal_api->set_description( $options['gcal_description'] );
			$this->gcal_api->set_summary( $options['gcal_summary'] );
			appointments_update_options( $options );
		}

		wp_redirect( add_query_arg( 'updated', 'true' ) );
		exit;
	}

	/**
	 * Revoke access and reset Google Calendar settings
	 */
	public function reset_settings() {
		if ( ! isset( $_POST['app-reset-gcalendar'] ) ) {
			return;
		}

		check_admin_referer( 'app-submit-gcalendar' );

		$result = $this->gcal_api->api_manager->revoke_token();
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

	private function render_errors( $errors ) {
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
	}

	public function render_tab() {
		$options = appointments_get_options();
		$client_id = isset( $options['gcal_client_id'] ) ? $options['gcal_client_id'] : '';
		$client_secret = isset( $options['gcal_client_secret'] ) ? $options['gcal_client_secret'] : '';
		$errors = array_merge( get_settings_errors( 'app-gcalendar' ), $this->gcal_api->errors );
		$token = $this->gcal_api->api_manager->get_access_token();

		$this->render_errors( $errors );

		?>
		<form name="input" action="" method="post">
		<?php

		$gcal_location = isset( $options['gcal_location'] ) ? $options['gcal_location'] : '';
		include_once( 'views/settings-gcal-general.php' );

		$gcal = isset( $options['gcal'] ) && $options['gcal'] == 'yes';
		$gcal_same_window = isset( $options['gcal_same_window'] ) && $options['gcal_same_window'];
		include_once( 'views/settings-gcal-button.php' );

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			include_once( 'views/settings-gcal-step-1.php' );
		}
		elseif ( $client_id && $client_secret && ! $this->gcal_api->is_connected() ) {
			// No token yet
			$auth_url = $this->gcal_api->api_manager->create_auth_url();
			include_once( 'views/settings-gcal-step-2.php' );
		}
		else {
			$calendars = $this->gcal_api->api_manager->get_calendars_list();
			$selected_calendar = $this->gcal_api->api_manager->get_calendar();
			$api_mode = $this->gcal_api->get_api_mode();
			$apps_count = $this->gcal_api->get_apps_to_export_count();
			$allow_worker = $this->gcal_api->workers_allowed();
			$api_scope = $this->gcal_api->get_api_scope();
			$gcal_description = $this->gcal_api->get_description();
			$gcal_summary = $this->gcal_api->get_summary();

			include_once( 'views/settings-gcal-step-3.php' );
		}

		?>
			</form>
		<?php
	}

	public function save_profile( $user_id ) {
		$switched = $this->gcal_api->switch_to_worker( $user_id, false );
		if ( ! $switched ) {
			return;
		}

		$gcal_action = isset( $_POST['gcal_action'] ) ? $_POST['gcal_action'] : false;
		if ( 'access-code' === $gcal_action ) {
			if ( ! empty( $_POST['access_code'] ) ) {
				$result = $this->gcal_api->api_manager->authenticate( $_POST['access_code'] );
				if ( is_wp_error( $result ) ) {
					$this->gcal_api->restore_to_default();
					wp_die( sprintf( __( 'Authentication failed: %s', 'appointments' ), $result->get_error_message() ) );
					return;
				}
				else {
					$token = $this->gcal_api->api_manager->get_access_token();
					update_user_meta( $user_id, 'app_gcal_access_code', $_POST['access_code'] );
					update_user_meta( $user_id, 'app_gcal_token', $token );
				}
			}

		}
		elseif ( 'gcal-settings' === $gcal_action ) {
			update_user_meta( $user_id, 'app_api_mode', $_POST['gcal_api_mode'] );
			update_user_meta( $user_id, 'app_selected_calendar', $_POST['gcal_selected_calendar'] );
			update_user_meta( $user_id, 'app_gcal_summary', sanitize_text_field( $_POST['gcal_summary'] ) );
			update_user_meta( $user_id, 'app_gcal_description', $_POST['gcal_description'] );
		}
		$this->gcal_api->restore_to_default();
	}

	public function show_profile( $profileuser ) {
		if ( ! appointments_is_worker( $profileuser->ID ) ) {
			return;
		}

		if ( isset( $_GET['reset-user-gcal'] ) ) {
			delete_user_meta( $profileuser->ID, 'app_gcal_access_code' );
			delete_user_meta( $profileuser->ID, 'app_gcal_token' );
		}

		$access_token = $this->gcal_api->api_manager->get_access_token();
		if ( ! $access_token ) {
			return;
		}

		$general_is_connected = $this->gcal_api->is_connected();

		$switched = $this->gcal_api->switch_to_worker( $profileuser->ID, false );
		if ( ! $switched ) {
			return;
		}

		$worker_is_connected = $this->gcal_api->is_connected();

		?>
		<h3><?php _e( 'Appointments +: Google Calendar API', 'appointments' ); ?></h3>
		<?php

		if ( ! $general_is_connected || ! $worker_is_connected ) {
			$auth_url = $this->gcal_api->api_manager->create_auth_url();
			include_once( 'views/profile-gcal-not-connected.php' );
		}
		else {
			$calendars = $this->gcal_api->api_manager->get_calendars_list();
			$selected_calendar = $this->gcal_api->api_manager->get_calendar();
			$api_mode = $this->gcal_api->get_api_mode();
			$gcal_description = $this->gcal_api->get_description();
			$gcal_summary = $this->gcal_api->get_summary();
			include_once( 'views/profile-gcal-connected.php' );

		}

		$this->gcal_api->restore_to_default();

	}


}
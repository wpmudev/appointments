<?php

/**
 * Hidden menu to export/import settings, workers, etc...
 *
 * Class Appointments_Admin_Import_Export_Settings_Page
 */
class Appointments_Admin_Import_Export_Settings_Page {
	public function __construct() {
		$this->page_id = add_submenu_page(
			'',
			'Appointments Import Export',
			'Appointments Import Export',
			App_Roles::get_capability( 'manage_options', App_Roles::CTX_PAGE_SETTINGS ),
			"app_import_export_settings",
			array( &$this, 'render' )
		);
		add_action( 'load-' . $this->page_id, array( $this, 'on_load' ) );
	}

	public function on_load() {
		if ( isset( $_POST['app_export'] ) ) {
			check_admin_referer( 'app_export_import_settings' );
			$options = appointments_get_options();
			$options['gcal'] = '';
			$options['gcal_location'] = '';
			$options['gcal_description'] = '';
			$options['gcal_summary'] = '';
			$options['merchant_email'] = '';
			$options['google-client_id'] = '';
			$options['gcal_service_account'] = '';
			$options['gcal_key_file'] = '';
			$options['gcal_selected_calendar'] = '';
			$options['gcal_client_id'] = '';
			$options['gcal_client_secret'] = '';
			$options['gcal_accesss_code'] = '';
			$options['gcal_token'] = '';
			$options['gcal_access_code'] = '';
			$options['google-client_secret'] = '';
			$options['twitter-app_id'] = '';
			$options['twitter-app_secret'] = '';
			$options['facebook-app_id'] = '';

			$services = appointments_get_services();
			$_workers = appointments_get_workers();
			$workers = array();
			foreach ( $_workers as $worker ) {
				if ( $user = get_userdata( $worker->ID ) ) {
					$worker->user = array();
					$worker->user['user_login'] = $user->user_login;
					$worker->user['role'] = $user->roles[0];
					$worker->user['ID'] = $user->ID;
					$workers[] = $worker;
				}
			}

			global $wpdb;
			$table = $wpdb->prefix . 'app_working_hours';
			$working_hours = $wpdb->get_results( "SELECT * FROM $table" );

			$all = array(
				'services' => $services,
				'workers' => $workers,
				'settings' => $options,
				'addons' => get_option( 'app_activated_plugins', array() ),
				'working_hours' => $working_hours
			);

			echo wp_json_encode( $all );
			die();
		}

		if ( isset( $_POST['app_import'] ) ) {
			$data = json_decode( stripslashes_deep( $_POST['import'] ), true );
			if ( ! is_array( $data ) || empty( $data ) ) {
				wp_die( "NOT A JSON STRING." );
			}

			$workers = appointments_get_workers();
			foreach ( $workers as $worker ) {
				appointments_delete_worker( $worker->ID );
			}
			$services = appointments_get_services();
			foreach ( $services as $service ) {
				appointments_delete_service( $service->ID );
			}

			delete_option( 'appointments_options' );

			update_option( 'appointments_options', (array)$data['settings'] );

			foreach ( $data['services'] as $service ) {
				appointments_insert_service( (array)$service );
			}

			$mapped_workers_ids = array();
			foreach ( $data['workers'] as $worker ) {
				$user = get_user_by( 'login', $worker['user']['user_login'] );
				if ( ! $user ) {
					$user_id = wp_insert_user( array( 'user_login' => $worker['user']['user_login'] ) );
				}
				else {
					$user_id = $user->ID;
				}

				if ( ! $user_id ) {
					continue;
				}

				$user_id = $user->ID;
				$mapped_workers_ids[ $worker['user']['ID'] ] = $user_id;
				if ( is_multisite() ) {
					add_user_to_blog( get_current_blog_id(), $user_id, $worker['user']['role'] );
				}

				$worker['ID'] = $user_id;
				appointments_insert_worker( (array)$worker );
			}

			update_option( 'app_activated_plugins', $data['addons'] );

			global $wpdb;
			$table = $wpdb->prefix . 'app_working_hours';
			$wpdb->query( "DELETE FROM $table" );
			foreach ( $data['working_hours'] as $working_hours ) {
				$user_id = $working_hours[ 'worker' ];
				if ( isset( $mapped_workers_ids[ $user_id ] ) ) {
					$working_hours[ 'worker' ] = $mapped_workers_ids[ $user_id ];
				}
				$wpdb->insert( $table, $working_hours );
			}
			appointments_clear_cache();

		}
	}

	public function render() {
		?>
		<div class="wrap">
			It will not export any sensible data like emails, passwords, Google Calendar settings...
			<form action="" method="post">
				<input type="hidden" name="app_export">
				<?php wp_nonce_field( 'app_export_import_settings' ); ?>
				<?php submit_button( 'Export Settings' ); ?>
			</form>

			<form action="" method="post">
				<p>Paste your JSON here</p>

				<p>Make sure that there's no new lines at the beggining/end of the string</p>
				<p style="color:red">This will delete ALL services and settings. It could create some workers too.</p>
				<input type="hidden" name="app_import">
				<textarea name="import" id="import" cols="30" rows="10" class="widefat"></textarea>
				<?php wp_nonce_field( 'app_export_import_settings' ); ?>
				<?php submit_button( 'Import Settings' ); ?>
			</form>
		</div>
		<?php
	}
}
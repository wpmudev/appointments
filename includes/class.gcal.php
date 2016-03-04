<?php
/**
 * Class for Google Calendar API for Appointments+
 * @author: Hakan Evin
 * @note: This may be the first WordPress compatible class that uses Google Calendar API as a service,
 * that is, when calendar owner is not required to be online
 * V1.2.8
 *
 * Google Apps reference: http://premium.wpmudev.org/forums/topic/cant-use-google-calendar#post-437790
 * Google Apps reference: http://premium.wpmudev.org/forums/topic/appointments-will-not-select-services-or-providers#post-437785
 *
 * @since 1.2.0
 */

if (!defined('APP_GCAL_MAX_RESULTS_LIMIT')) define('APP_GCAL_MAX_RESULTS_LIMIT', 500);

if ( !class_exists( 'AppointmentsGcal' ) ) {
class AppointmentsGcal {

	function __construct() {
		global $wpdb, $appointments;

		$this->local_time	= current_time('timestamp');
		$this->options = get_option( 'appointments_options' );

		// DB version
		$this->db_version 	= get_option( 'app_db_version' );
		$this->app_table 	= $wpdb->prefix . "app_appointments";

		$this->plugin_dir 	= $appointments->plugin_dir;
		$this->plugin_url 	= $appointments->plugin_url;

		require_once $this->plugin_dir . '/includes/external/google/Client.php';

		// Try to start a session. If cannot, log it.
		if ( !session_id() && !@session_start() ) {
			$appointments->log( __('Session could not be started. This may indicate a theme issue.', 'appointments' ) );
		}

		// Let A+ main settings saved first
		add_action( 'admin_init', array( &$this, 'save_settings' ), 12 );
		add_action( 'init', array( &$this, 'init' ), 12 );

		// Add a custom column on users page
		add_filter( 'manage_users_custom_column', array( &$this, 'users_custom_column' ), 10, 3 );
		add_filter( 'manage_users_columns', array( &$this, 'users_columns' ) );

		// Deal with export requests
		add_action('wp_ajax_app-gcal-export_and_update', array($this, 'json_export_appointments'));

		// Prevent exceptions to kill the page
		if ( ( isset( $_GET['gcal_api_test'] ) && 1 == $_GET['gcal_api_test'] )
			|| ( isset( $_GET['gcal_import_now'] ) && $_GET['gcal_import_now'] ) )
			set_exception_handler( array( &$this, 'exception_error_handler' ) );

		// Set log file location
		$uploads = wp_upload_dir();
		if ( isset( $uploads["basedir"] ) )
			$this->uploads_dir 	= $uploads["basedir"] . "/";
		else
			$this->uploads_dir 	= WP_CONTENT_DIR . "/uploads/";

		//add_action( 'wpmudev_appointments_update_appointment_status', array( $this, 'update_appointment_status' ), 10, 2 );
	}

	/**
	 * Triggered when an Appointment change its status
	 */
	function update_appointment_status( $app_id, $new_status ) {
		if ( $this->is_syncable_status( $new_status ) ) {
			$this->update( $app_id );
		}
	}

	/**
	 * Exports existing appointments to GCal.
	 * @since v1.4.6
	 */
	function json_export_appointments () {
		$msg = __('All done!', 'appointments');
		$export = get_option('app_tmp_appointments_to_export', array());
		if (empty($export)) {
			// Fetching the export appointments, since the cache is empty
			global $wpdb;
			$status = $this->_get_syncable_status();
			if (is_array($status) && !empty($status)) {
				$clean_stat = "'" . join("','", array_values(array_filter(array_map('trim', $status)))) . "'";
				$export = $wpdb->get_col("SELECT ID FROM {$this->app_table} WHERE status IN({$clean_stat})");
			}
		}

		if (!empty($export)) {
			$app_id = array_pop($export);
			if (is_numeric($app_id)) $this->update($app_id);
			if (!empty($export)) $msg = sprintf(__('Processing, %d appointments left.'), count($export));
		}

		update_option('app_tmp_appointments_to_export', $export);
		wp_send_json(array(
			'msg' => $msg,
			'remaining' => count($export),
		));
		die;
	}

	/**
	* Add a custom column for GCal mode
	* @since V1.2.7.1
	*/
	function users_columns( $columns ) {

		// Nothing to do if providers are not allowed to set GCal API
		if ( 'yes' != @$this->options['gcal_api_allow_worker'] )
			return $columns;

		$columns['gcal_mode'] = __( 'GCal Mode','appointments' );
		return $columns;
	}

	/**
	* Add text inside a custom user column
	* @since V1.2.7.1
	*/
	function users_custom_column( $text, $column_name, $user_id ) {

		// Nothing to do if providers are not allowed to set GCal API
		if ( 'yes' != @$this->options['gcal_api_allow_worker'] || 'gcal_mode' != $column_name )
			return $text;

		global $appointments;
		if ( ! appointments_is_worker( $user_id ) )
			return ' - ';

		$mode = $this->get_api_mode( $user_id );

		switch ( $mode ) {
			case 'none':		return __( 'None', 'appointments'); break;
			case 'gcal2app':	return __( 'A+<-GCal', 'appointments'); break;
			case 'app2gcal':	return __( 'A+->GCal', 'appointments'); break;
			case 'sync':		return __( 'A+<->GCal', 'appointments'); break;
			default:			return ' - '; break;
		}
	}

	/**
	 * Refresh the page with the exception as GET parameter, so that page is not killed
	 */
	function exception_error_handler( $exception ) {
		// If we don't remove these GETs there will be an infinite loop
		if ( !headers_sent() ) {
			wp_redirect(esc_url(add_query_arg(array('gcal_api_test_result' => urlencode($exception), 'gcal_import_now' => false, 'gcal_api_test' => false, 'gcal_api_pre_test' => false))));
		} else {
			// We cannot display it, so we save it
			global $appointments;
			$appointments->log( $exception );
		}
	}

	/**
	 * Outputs Google Calendar tab on admin settings page
	 */
	function render_tab( $worker_id=0 ) {

		$this->create_key_file_folder();

		// Set correct worker_id for test connection and import&update now
		if ( !$worker_id && isset( $_GET['gcal_api_worker_id'] ) )
			$worker_id = $_GET['gcal_api_worker_id'];

		$this->display_nag( $worker_id );
		?>

		<div id="poststuff" class="metabox-holder">
		<span class="description"><?php _e('Appointments+ can integrate with Google Calendar accounts by 2 different ways: 1) Google Calendar Button 2) Google Calendar API.', 'appointments') ?></span>
		<br />
		<br />
		<span class="description"><?php _e('Google Calendar Button method is simple to implement, but it is semi-automatic, that is, client or service provider should click the button to submit the appointment to his Google calendar account.', 'appointments') ?></span>
		<br />
		<br />
		<span class="description"><?php _e('Setting of Google Calendar API is sophisticated because of requirements and security measures of Google itself, but once it is correctly set, appointments are automatically sent to the Google calendar account. Synchronization (automatic import of Google Calendar events to Appointments+) is also possible with the API method. Clients cannot use API method; only website itself and/or service providers can use it. Note: Each service provider should carry out the below setting steps, if they want to follow their appointments on their own calendars.', 'appointments') ?></span>
		<br />
		<br />
			<form method="post" action="<?php echo esc_url(add_query_arg(array('gcal_api_test'=>false, 'gcal_api_test_result'=>false, 'gcal_api_pre_test'=>false, 'gcal_import_now'=>false))) ?>" >

			<div class="postbox">

			<h3 class='hndle'><span><?php _e('Google Calendar General Setting', 'appointments') ?></span></h3>
				<div class="inside">
					<table class="form-table">

						<tr valign="top">
							<th scope="row" ><?php _e('Google Calendar Location','appointments')?></th>
							<td colspan="2">
							<input type="text" style="width:400px" name="gcal_location" value="<?php if (isset($this->options["gcal_location"])) echo $this->options["gcal_location"] ?>" />
							<br /><span class="description"><?php _e('Enter the text that will be used as location field in Google Calendar. If left empty, your website description is sent instead. Note: You can use ADDRESS and CITY placeholders which will be replaced by their real values.', 'appointments')?></span>
							</td>
						</tr>


					</table>
				</div>
			</div>

			<div class="postbox">

			<h3 class='hndle'><span><?php _e('Google Calendar Button Settings', 'appointments') ?></span></h3>
				<div class="inside">
					<table class="form-table">

						<tr valign="top">
							<th scope="row" ><?php _e('Add Google Calendar Button', 'appointments')?></th>
							<td colspan="2">
							<select name="gcal">
							<option value="no" <?php if ( @$this->options['gcal'] != 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
							<option value="yes" <?php if ( @$this->options['gcal'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
							</select>
							<br />
							<span class="description"><?php _e('Whether to let client access his Google Calendar account using Google Calendar button. Button is inserted in the confirmation area, as well as My Appointments shortcode and user page/tab if applicable.', 'appointments') ?></span>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row" ><?php _e('Open Google Calendar in the Same Window', 'appointments')?></th>
							<td colspan="2">
							<input type="checkbox" name="gcal_same_window" <?php if ( isset( $this->options["gcal_same_window"] ) && $this->options["gcal_same_window"] ) echo 'checked="checked"' ?> />&nbsp;&nbsp;&nbsp;
							<span class="description"><?php _e('As default, Google Calendar is opened in a new tab or window. If you check this option, user will be redirected to Google Calendar from the appointment page, without opening a new tab or window. Note: While applying for the appointment, this is effective if payment is not required, or price is zero (Otherwise payment button/form would be lost).', 'appointments') ?></span>
							</td>
						</tr>

					</table>
				</div>
			</div>

			<div class="postbox">
				<h3 class='hndle'><span><?php _e('Google Calendar API Settings - BETA', 'appointments') ?></span></h3>
				<div class="inside">
				<?php _e( '<b>Important Note:</b> Google Calendar API for Appointments+ is in Beta stage, because as of February 2013, Google itself does not officially list Google Calendar in its supported "Service Accounts" which this plugin needs. However, we tested it on several websites and saw that it is working. There may be some restrictions by Google that we may not know. Use this option carefully and please give us feedback about the results.', 'appointments') ?>
				<br />
				<?php _e( '<b>Note on Google account/calendar that will be used:</b> A namely selected service provider is connected to the calendar selected in his profile page and "No preference" service provider is connected to the calendar selected on this page (also possible to have a copy of all new appointments in this calendar if you select "All" below)', 'appointments' ) ?>
				<br />
				<?php _e( '<b>Note for Google Business Account users:</b> GCal API usage is not free of charge for business accounts. You should first contact Google sales department to make it available or use an individual account instead.', 'appointments') ?>
				<br />
				<?php _e( '<b>Note on deleting an appointment or event:</b> To prevent authority conflicts, deletion is only possible from the side that appointment/event created in the first place. For example, you cannot delete an appointment using Google Calendar if it has been saved there by A+. You must delete the appointment using A+ admin pages, which will automatically remove the event associated with it. But deleting such an event in Google Calendar will NOT remove the appointment.', 'appointments') ?>

					<table class="form-table">

						<tr valign="top">
							<th scope="row" ><?php _e('Allow Service Providers for Google Calendar API Integration', 'appointments')?></th>
							<td colspan="2">
							<select name="gcal_api_allow_worker">
							<option value="no" <?php if ( @$this->options['gcal_api_allow_worker'] != 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
							<option value="yes" <?php if ( @$this->options['gcal_api_allow_worker'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
							</select>
							<br />
							<span class="description"><?php _e('Whether you let your service providers to integrate with their own Google Calendar account using their profile page. Note: Each of them will need to set up their accounts following the steps as listed in Instructions below (will also be shown in their profile pages) and you will need to upload their key files yourself using FTP.', 'appointments') ?></span>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row" ><?php _e('Appointments will be sent to Google Calendar for', 'appointments')?></th>
							<td colspan="2">
							<select name="gcal_api_scope">
							<option value="all" <?php if ( @$this->options['gcal_api_scope'] == 'all' ) echo "selected='selected'"?>><?php _e('All', 'appointments')?></option>
							<option value="no_preference" <?php if ( @$this->options['gcal_api_scope'] != 'all' ) echo "selected='selected'"?>><?php _e('No preference case', 'appointments')?></option>
							</select>
							<br />
							<span class="description"><?php _e('If you select "All", any appointment made from this website will be sent to the selected calendar. If you select "No preference case", only appointments which do not have an assigned service provider will be sent.', 'appointments') ?></span>
							</td>
						</tr>

					<?php $this->display_settings( $worker_id ); ?>

					</table>
				</div>
			</div>

				<input type="hidden" name="action_api" value="save_general" />
				<?php wp_nonce_field( 'update_api_settings', 'api_nonce' ); ?>
				<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Settings', 'appointments') ?>" />
				</p>

			</form>

		</div>
	<?php
	}

	/**
	 * Displays nag
	 * @param worker_id: Check and display this for the user profile page
	 */
	function display_nag( $worker_id=0 ) {
		$error = false;
		$message = '';

		if ( isset( $_GET['gcal_api_test'] ) && 1 == $_GET['gcal_api_test'] ) {
			if ( $result = $this->is_not_suitable( $worker_id ) ) {
				$message .= $result;
				if ( __('Your server installation meets requirements.','appointments') != $result )
					$error = true;
			}
			else {
				// Insert a test event
				$result = $this->insert_event( 0, true, $worker_id );
				if ( $result )
					$message .= __('Test is successful. Please REFRESH your Google Calendar and check that test appointment has been saved.','appointments');
				else {
					$message .= __('Test failed. Please inspect your log for more info.', 'appointments');
					$error = true;
				}
			}
		}
		if ( isset( $_GET['gcal_import_now'] ) && 1 == $_GET['gcal_import_now'] ) {
			$this->updated = $this->inserted = $this->deleted = 0;
			$result = $this->import_and_update_events( $worker_id );
			if ( $result )
				$message .= $result;
			else
				$message .= __('No future new events are found in your Google calendar. Thus no events are imported and no updates and deletions made.','appointments');
		}

		if ( $message )
			$this->show_message( $message, $error );

		if ( isset( $_GET['gcal_api_test_result'] ) && '' != $_GET['gcal_api_test_result'] ) {
			$m = stripslashes(urldecode($_GET['gcal_api_test_result'] ));
			// Get rid of unnecessary information
			if ( strpos( $m, 'Stack trace' ) !== false ) {
				$temp = explode( 'Stack trace', $m );
				$m = $temp[0];
			}
			if ( strpos( $this->get_selected_calendar( $worker_id ), 'group.calendar.google.com' ) === false )
				$add = '<br />'. __('Do NOT use your primary Google calendar, but create a new one.', 'appointments' );
			else
				$add = '';
			$this->show_message( __('The following error has been reported by Google Calendar API:<br />', 'appointments') . $m .
				'<br />' . __('<b>Recommendation:</b> Please double check your settings.' . $add, 'appointments'), true );
		}
	}

	/**
	 * Displays Google API settings
	 * @param worker_id: Check and display this for the user profile page
	 */
	function display_settings( $worker_id=0 ) {
		global $current_user, $pagenow;

		$gcal_api_mode			= $this->get_api_mode( $worker_id );
		$gcal_service_account	= $this->get_service_account( $worker_id );
		$gcal_key_file			= $this->get_key_file( $worker_id );
		$gcal_selected_calendar	= $this->get_selected_calendar( $worker_id );
		$gcal_summary			= $this->get_summary( $worker_id );
		$gcal_description		= $this->get_description( $worker_id );

		if ( !$worker_id )
			$gcal_api_worker_id = false;
		else
			$gcal_api_worker_id = $worker_id;

		if ( 'admin.php' == $pagenow || $current_user->ID == $worker_id )
			$is_readonly = '';
		else
			$is_readonly = ' readonly="readonly"';

		$this->instructions();

		?>
		<tr valign="top">
			<th scope="row" ><?php _e('Integration Mode', 'appointments')?></th>
			<td colspan="2">
			<select name="gcal_api_mode">
			<option value="none" ><?php _e('Integration disabled', 'appointments')?></option>
			<option value="gcal2app" <?php if ( $gcal_api_mode == 'gcal2app' ) echo "selected='selected'"?>><?php _e('A+ <- GCal (Only import appointments)', 'appointments')?></option>
			<option value="app2gcal" <?php if ( $gcal_api_mode == 'app2gcal' ) echo "selected='selected'"?>><?php _e('A+ -> GCal (Only export appointments)', 'appointments')?></option>
			<option value="sync" <?php if ( $gcal_api_mode == 'sync' ) echo "selected='selected'"?>><?php _e('A+ <-> GCal (Synchronization)', 'appointments')?></option>
			</select>
			<br />
			<span class="description"><?php _e('Select method of integration. A+ -> GCal setting sends appointments to your selected Google calendar, but events in your Google Calendar account are not imported to Appointments+ and thus they do not reserve your available working times. A+ <-> GCal setting works in both directions. This synchronization is not immediate; it requires at least some traffic to your website and not handled less than 10 minutes intervals. To update it manually use "Import and Update Events Now" link which is only visible if the settings let it so.', 'appointments') ?></span>
			</td>
		</tr>

		<?php if ( 'sync' == $gcal_api_mode && $gcal_service_account && $gcal_key_file && $gcal_selected_calendar && !$is_readonly ) {

		  ?>
		 <tr>
			<th scope="row">&nbsp;</th>
			<td>
				<?php print "<a href='".esc_url(add_query_arg(array('gcal_import_now'=>1,'gcal_api_test'=>false, 'gcal_api_test_result'=>false, 'gcal_api_worker_id' => $gcal_api_worker_id)))."'>Import and Update Events from GCal Now</a>"; ?>
				<br />
				<span class="description"><?php _e('Clicking this link will manually import and update your Events from the selected calendar without waiting for 10 minutes. Note: Maximum 500 future events that will start until appointment limit setting are imported in the order of their starting time. Past events and all day events are not imported.', 'appointments') ?></span>
			</td>
		</tr>
		<tr>
			<th scope="row">&nbsp;</th>
			<td>
				<?php print "<a href='#export' class='app-gcal-export_and_update'>Export and Update Events to GCal Now</a>"; ?>
				<br />
				<span class="description"><?php _e('Clicking this link will manually export and update your existing appointments to the selected calendar. Past appointments will not be exported.', 'appointments') ?></span>
				<div class="app-gcal-result"></div>
			</td>
		</tr>
			<?php
		}
		?>
		<tr>
			<th scope="row"><?php _e('Key file name', 'appointments') ?></th>
			<td>
			<input value="<?php echo $gcal_key_file; ?>" size="90" name="gcal_key_file" type="text"  <?php echo $is_readonly ?> />
			<br />
			<span class="description"><?php _e('Enter key file name here without extention, e.g. ab12345678901234567890-privatekey', 'appointments') ?></span>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php _e('Service account email address', 'appointments') ?></th>
			<td>
			<input value="<?php echo $gcal_service_account; ?>" size="90" name="gcal_service_account" type="text"  <?php echo $is_readonly ?> />
			<br />
			<p><?php _e( 'Obtain your service account email address:', 'appointments' ); ?></p>
			<ol>
				<li><?php printf( __( 'Go to <a href="%s" target="_blank">Service Accunts section</a>', 'appointments' ), 'https://console.developers.google.com/projectselector/permissions/serviceaccounts' ); ?></li>
				<li><?php _e( 'Select your project', 'appointments' ); ?></li>
				<li><?php _e( 'Click on Service Accounts tab', 'appointments' ); ?></li>
				<li><?php _e( 'Copy your account email address', 'appointments' ); ?></li>
			</ol>
			<span class="description"><?php _e('Enter Service account email address here', 'appointments') ?></span>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php _e('Calendar to be used', 'appointments') ?></th>
			<td>
			<input value="<?php echo $gcal_selected_calendar; ?>" size="90" name="gcal_selected_calendar" type="text"  <?php echo $is_readonly ?> />
			<p><?php _e( 'Get your calendar ID:', 'appointments' ); ?></p>
			<ol>
				<li><?php printf( __( 'Go to <a href="%s" target="_blank">your calendar page</a>' , 'appointments' ), 'https://calendar.google.com/calendar' ); ?></li>
				<li><?php _e( 'Create a new calendar or select an existing one. try not to use your main calendar.' ); ?></li>
				<li><?php _e( 'Click on the down arrow at the right of the created Calendar and click on Calendar Settings.' ); ?></li>
				<li><?php _e( 'Find "Calendar ID" (it\'s not so obvious but is there) and copy the value.' ); ?></li>
			</ol>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php _e('Event summary (name)', 'appointments') ?></th>
			<td>
			<input value="<?php echo $gcal_summary; ?>" size="90" name="gcal_summary" type="text"  <?php echo $is_readonly ?> />
			</td>
		</tr>

		<tr>
			<th scope="row"><?php _e('Event description', 'appointments') ?></th>
			<td>
			<textarea cols="90" name="gcal_description"  <?php echo $is_readonly ?> ><?php echo $gcal_description ?></textarea>
			<br />
			<span class="description">
				<?php _e('For the above 2 fields, you can use the following placeholders which will be replaced by their real values:', 'appointments') ?>&nbsp;SITE_NAME, CLIENT, SERVICE, SERVICE_PROVIDER, DATE_TIME, PRICE, DEPOSIT, PHONE, NOTE, ADDRESS, EMAIL <?php _e("(Client's email)", "appointments")?>
				<br />
				<?php _e('Please be careful about privacy if your calendar is public.', 'appointments'); ?>
			</span>
			</td>
		</tr>

		<?php if ( $gcal_service_account && $gcal_key_file && $gcal_selected_calendar && 'none' != $gcal_api_mode ) {
				// Don't let admin test connection of a a user, other than himself
				if ( !$is_readonly ) {
		  ?>
		 <tr>
			<th scope="row">&nbsp;</th>
			<td>
			<?php print "<a href='".esc_url(add_query_arg(array('gcal_api_test'=>1, 'gcal_api_test_result'=>false, 'gcal_api_pre_test'=>false, 'gcal_api_worker_id' => $gcal_api_worker_id)))."'>" . __('Test Connection', 'appointments' ) . "</a>"; ?>
			<br />
			<span class="description"><?php _e('Clicking this link will attempt to connect your GCal account and write a sample appointment which lasts 30 minutes and starts 10 minutes after your current server time. If there are some setting or connection errors, you will be informed about them.', 'appointments') ?></span>
			</td>
		</tr>
			<?php
				}
			}
	}

	/**
	 * Display instructions for setup
	 */
	function instructions( ) {
		?>
		<tr>
			<th scope="row"><?php _e('Instructions', 'appointments') ?></th>
			<td>
			<?php _e('To set up Google Calendar API, please click the "i" icon and carefully follow these steps:', 'appointments') ?>
			<span class="description"><a href="#gcal-instructions" data-target="api-instructions" class="app-info_trigger" title="<?php _e('Click to toggle instructions', 'appointments')?>"><?php _e('Show me how', 'appointments'); ?></a></span>
			<div class="description app-info_target api-instructions">
				<?php printf( __('Tip: There is a video tutorial showing these steps %s.', 'appointments'), '<a href="http://youtu.be/hul60oJ1Eiw" target="_blank">' . __('here', 'appointments'). '</a>') ?>
				<ul style="list-style-type:decimal;">
					<li><?php printf( __('Google Calendar API requires php V5.3+ and some php extensions. Click this link to check if your server installation meets those requirements: %s', 'appointments'), "<a href='".esc_url(add_query_arg(array( 'gcal_api_test'=>1, 'gcal_api_test_result'=>false, 'gcal_api_pre_test'=>1)))."'>" . __('Check Requirements', 'appointments' ) . "</a>") ?></li>
					<li><?php printf( __('Open the <a href="%s" target="_blank">Credentials page</a>. Login to your Google account if you are not already logged in.', 'appointments'), 'https://console.developers.google.com/project/_/apiui/credential' ); ?></li>
					<li><?php _e('Create a new project. Name the project &quot;Appointments&quot; (or use your chosen name instead)', 'appointments') ?></li>
					<li><?php _e('Click on Create credentials > Service.', 'appointments') ?></li>
					<li><?php _e('Choose to download the service account\'s public/private key as a standard P12 file', 'appointments') ?></li>
					<li><?php _e('Your new public/private key pair is generated and downloaded to your machine', 'appointments') ?></li>
					<li><?php _e('Save the file and the private key password that appears on screen.', 'appointments') ?></li>
					<li><?php printf( __('Now go to <a href="%s" target="_blank">APIs library</a> and make sure that Google Calendar API is active.', 'appointments'), 'https://console.developers.google.com/apis/library' ); ?></li>
					<li><?php printf( __('Using your FTP client program, copy this key file to folder: %s . This file is required as you will grant access to your Google Calendar account even if you are not online. So this file serves as a proof of your consent to access to your Google calendar account. Note: This file cannot be uploaded in any other way. If you do not have FTP access, ask the website admin to do it for you.', 'appointments'), '<strong>' . $this->key_file_folder() . '</strong>' ) ?></li>
					<li><?php _e( 'Fill the form below.', 'appointments' ); ?></li>
					<li><?php  _e('Select the desired Integration mode: A+->GCal or A+<->GCal.', 'appointments') ?></li>
					<li><?php  _e('Click "Save Settings" on Appointments+ settings.', 'appointments') ?></li>
					<li><?php  _e('After these stages, you have set up Google Calendar API. To test the connection, click the "Test Connection" link which should be visible after you clicked save settings button.', 'appointments') ?></li>
					<li><?php  _e('If you get a success message, you should see a test event inserted to the Google Calendar and you are ready to go. If you get an error message, double check your settings.', 'appointments') ?></li>
				</ul>
			</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Show results of the test
	 * @param message: Message to be displayed
	 * @param error: If this is an error message set as true
	 */
	function show_message( $message, $error=false ) {
		if ( $error )
			$class = 'error';
		else
			$class = 'updated';
		echo '<div class="'.$class.'"><p>' .
				sprintf( __('<b>[Appointments+]</b> %s', 'appointments'), $message ) .
			'</p></div>';
	}

	function get_key_file_folder_path() {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . '/appointments/';
	}


	/**
	 * Save admin settings
	 */
	function save_settings() {
		global $appointments;
		if ( isset($_POST["action_api"]) && !wp_verify_nonce($_POST['api_nonce'],'update_api_settings') ) {
			add_action( 'admin_notices', array( $appointments, 'warning' ) );
			return;
		}

		if ( !isset($_POST["action_api"]) || 'save_general' != $_POST["action_api"] ) {
			return;
		}

		if (!current_user_can('manage_options')) {
			wp_die( __('You do not have sufficient permissions to access this page.','appointments') );
		}

		$kff = $this->key_file_folder( );
		$kfn = $this->get_key_file(). '.p12';
		// Copy key file to uploads folder
		if ( is_dir( $kff ) && !file_exists( $kff . $kfn ) && file_exists( $this->plugin_dir . '/includes/gcal/key/' . $kfn ) )
			copy( $this->plugin_dir . '/includes/gcal/key/' . $kfn, $kff . $kfn );

		$options["gcal"]					= $_POST["gcal"];
		$options["gcal_same_window"]		= isset( $_POST["gcal_same_window"] );
		$options["gcal_location"]			= stripslashes( @$_POST["gcal_location"] );

		$options['gcal_api_allow_worker']	= $_POST['gcal_api_allow_worker'];
		$options['gcal_api_scope']			= $_POST['gcal_api_scope'];
		$options['gcal_api_mode']			= $_POST['gcal_api_mode'];
		$options['gcal_service_account']	= trim( $_POST['gcal_service_account'] );
		$options['gcal_key_file']			= trim( str_replace( '.p12', '', $_POST['gcal_key_file'] ) );
		$options['gcal_selected_calendar']	= trim( $_POST['gcal_selected_calendar'] );

		$options['gcal_summary']			= stripslashes( $_POST['gcal_summary'] );
		$options['gcal_description']		= stripslashes( $_POST['gcal_description'] );

		$this->options = array_merge( $this->options, $options );

		if ( update_option('appointments_options', $this->options )  ) {
			add_action( 'admin_notices', array( $appointments, 'saved' ) );
			$appointments->options = $this->options;
		}
	}

	/**
	 * Set some default settings related to GCal
	 * @since 1.2.1
	 */
	function init( ) {
		if ( 'none' != $this->get_api_mode() ) {
			// Try to create key file folder if it doesn't exist
			$this->create_key_file_folder( );
			$kff = $this->key_file_folder( );
			// Copy index.php to this folder and to uploads folder
			if ( is_dir( $kff ) && !file_exists( $kff . 'index.php') )
				@copy( $this->plugin_dir . '/includes/gcal/key/index.php', $kff . 'index.php' );
			if ( is_dir( $this->uploads_dir ) && !file_exists( $this->uploads_dir . 'index.php') )
				@copy( $this->plugin_dir . '/includes/gcal/key/index.php', $this->uploads_dir . 'index.php' );

			// Copy key file to uploads folder
			$kfn = $this->get_key_file(). '.p12';
			if ( $kfn && is_dir( $kff ) && !file_exists( $kff . $kfn ) && file_exists( $this->plugin_dir . '/includes/gcal/key/' . $kfn ) )
				@copy( $this->plugin_dir . '/includes/gcal/key/' . $kfn, $kff . $kfn );
		}

		$gcal_description = __("Client Name: CLIENT\nService Name: SERVICE\nService Provider Name: SERVICE_PROVIDER\n", "appointments");

		$this->options = get_option( 'appointments_options' );

		$changed = false;
		if ( !isset( $this->options['gcal_description'] ) ) {
			$this->options['gcal_description'] = $gcal_description;
			$changed = true;
		}
		if ( !isset( $this->options['gcal_summary'] ) ) {
			$this->options['gcal_summary'] = __('SERVICE Appointment','appointments');
			$changed = true;
		}
		if ( $changed )
			update_option( 'appointments_options', $this->options );
	}

	/**
	 * Return GCal API mode (none, app2gcal or sync )
	 * @param worker_id: Optional worker ID whose data will be restored
	 * @return string
	 */
	function get_api_mode( $worker_id=0 ) {
		if ( !$worker_id ) {
			if ( isset( $this->options['gcal_api_mode'] ) )
				return $this->options['gcal_api_mode'];
			else
				return 'none';
		}
		else {
			$meta = get_user_meta( $worker_id, 'app_api_mode', true );
			if ( $meta )
				return $meta;
			else
				return 'none';
		}
	}

	/**
	 * Return GCal service account
	 * @param worker_id: Optional worker ID whose data will be restored
	 * @return string
	 */
	function get_service_account( $worker_id=0 ) {
		if ( !$worker_id ) {
			if ( isset( $this->options['gcal_service_account'] ) )
				return $this->options['gcal_service_account'];
			else
				return '';
		}
		else
			return get_user_meta( $worker_id, 'app_service_account', true );
	}

	/**
	 * Return GCal key file name without the extension
	 * @param worker_id: Optional worker ID whose data will be restored
	 * @return string
	 */
	function get_key_file( $worker_id=0 ) {
		if ( !$worker_id ) {
			if ( isset( $this->options['gcal_key_file'] ) )
				return $this->options['gcal_key_file'];
			else
				return '';
		}
		else
			return get_user_meta( $worker_id, 'app_key_file', true );
	}

	/**
	 * Return GCal selected calendar ID
	 * @param worker_id: Optional worker ID whose data will be restored
	 * @return string
	 */
	function get_selected_calendar( $worker_id=0 ) {
		if ( !$worker_id ) {
			if ( isset( $this->options['gcal_selected_calendar'] ) )
				return $this->options['gcal_selected_calendar'];
			else
				return '';
		}
		else
			return get_user_meta( $worker_id, 'app_selected_calendar', true );
	}

	/**
	 * Return GCal Summary (name of Event)
	 * @param worker_id: Optional worker ID whose data will be restored
	 * @since 1.2.1
	 * @return string
	 */
	function get_summary( $worker_id=0 ) {
		$text = '';
		if ($worker_id) {
			$text = get_user_meta($worker_id, 'app_gcal_summary', true);
		}
		if (empty($text)) $text = !empty($this->options['gcal_summary'])
			? $this->options['gcal_summary']
			: ''
		;
		return $text;
		/*
		if ( !$worker_id ) {
			if ( isset( $this->options['gcal_summary'] ) )
				return $this->options['gcal_summary'];
			else
				return '';
		}
		else
			return get_user_meta( $worker_id, 'app_gcal_summary', true );
		*/
	}

	/**
	 * Return GCal description
	 * @param worker_id: Optional worker ID whose data will be restored
	 * @since 1.2.1
	 * @return string
	 */
	function get_description( $worker_id=0 ) {
		$text = '';
		if ($worker_id && !empty($this->options['gcal_api_allow_worker']) && 'yes' == $this->options['gcal_api_allow_worker']) {
			$text = get_user_meta($worker_id, 'app_gcal_description', true);
		}
		if (empty($text)) $text = !empty($this->options['gcal_description'])
			? $this->options['gcal_description']
			: ''
		;
		return $text;
		/*
		if ( !$worker_id ) {
			if ( isset( $this->options['gcal_description'] ) )
				return $this->options['gcal_description'];
			else
				return '';
		}
		else
			return get_user_meta( $worker_id, 'app_gcal_description', true );
		*/
	}

	/**
	 * Checks if php version and extentions are correct
	 * @param worker_id: Optional worker ID whose account is to be checked
	 * @return string (Empty string means suitable)
	 */
	function is_not_suitable( $worker_id=0 ) {

		if ( version_compare( $this->db_version, '1.2.0', '<' ) )
			return __('You have to update the Appointments+ tables. To do so, deactivate and reactivate the plugin.','appointments');

		if ( version_compare(PHP_VERSION, '5.3.0', '<') )
			return __('Google PHP API Client requires at least PHP 5.3','appointments');

		// Disabled for now
		if ( false && memory_get_usage() < 31000000 )
			return sprintf( __('Google PHP API Client requires at least 32 MByte Server RAM. Please check this link how to increase it: %s','appointments'), '<a href="http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP" target="_blank">'.__('Increasing_memory_allocated_to_PHP','appointments').'</a>');	 	 	 	 	   		 	 			

		if (!function_exists('curl_init'))
			return __('Google PHP API Client requires the CURL PHP extension','appointments' );

		if (!function_exists('json_decode'))
			return __('Google PHP API Client requires the JSON PHP extension','appointments');

		if ( !function_exists('http_build_query'))
			return __('Google PHP API Client requires http_build_query()','appointments');

		// Dont continue further if this is pre check
		if ( isset( $_GET['gcal_api_pre_test'] ) && 1== $_GET['gcal_api_pre_test'] )
			return __('Your server installation meets requirements.','appointments');

		if ( !$this->_file_exists( $worker_id ) )
			return __('Key file does not exist','appointments');

		return '';
	}

	/**
	 * Checks if key file exists
	 * @param worker_id: Optional worker ID whose account is to be checked
	 * @return bool
	 * @since 1.2.2
	 */
	function _file_exists( $worker_id=0 ) {
		if ( file_exists( $this->key_file_folder( ). $this->get_key_file( $worker_id ) . '.p12' ) )
			return true;
		else if ( file_exists( $this->plugin_dir . '/includes/gcal/key/'. $this->get_key_file( $worker_id ) . '.p12' ) )
			return true;
		return false;
	}

	/**
	 * Get contents of the key file
	 * @param worker_id: Optional worker ID whose account is to be checked
	 * @return string
	 * @since 1.2.2
	 */
	function _file_get_contents( $worker_id=0 ) {
		if ( file_exists( $this->key_file_folder( ). $this->get_key_file( $worker_id ) . '.p12' ) )
			return @file_get_contents( $this->key_file_folder( ). $this->get_key_file( $worker_id ) . '.p12' );
		else if ( file_exists( $this->plugin_dir . '/includes/gcal/key/'. $this->get_key_file( $worker_id ) . '.p12' ) )
			return @file_get_contents( $this->plugin_dir . '/includes/gcal/key/'. $this->get_key_file( $worker_id ) . '.p12' );
		return '';
	}

	/**
	 * Try to create an encrypted key file folder
	 * @return string
	 * @since 1.2.2
	 */
	function create_key_file_folder( ) {
		if ( defined( 'AUTH_KEY' ) ) {
			$kff = $this->uploads_dir . md5( 'AUTH_KEY' ) . '/' ;
			if ( is_dir( $kff ) )
				return;
			else
				@mkdir( $kff );

			if ( is_dir( $kff ) )
				return;
		}
		if ( !is_dir( $this->uploads_dir . '__app/' ) )
			@mkdir( $this->uploads_dir . '__app/' );
	}

	/**
	 * Return key file folder name
	 * @return string
	 * @since 1.2.2
	 */
	function key_file_folder( ) {
		if ( defined( 'AUTH_KEY' ) ) {
			$kff = $this->uploads_dir . md5( 'AUTH_KEY' ) . '/' ;
			if ( is_dir( $kff ) )
				return $kff;
		}
		return $this->uploads_dir . '__app/';
	}


	/**
	 * Checks for settings and prerequisites
	 * @param worker_id: Optional worker ID whose account is to be checked
	 * @return bool
	 */
	function is_active( $worker_id=0 ) {

		// If integration is disabled, nothing to do
		if ( 'none' == $this->get_api_mode( $worker_id ) || !$this->get_api_mode( $worker_id ) )
			return false;

		if ( $this->is_not_suitable( $worker_id ) )
			return false;

		if ( $this->get_key_file( $worker_id ) &&  $this->get_service_account( $worker_id ) && $this->get_selected_calendar( $worker_id ) )
			return true;

		// None of the other cases are allowed
		return false;
	}

	/**
	 * Connects to GCal API
	 */
	function connect( $worker_id=0 ) {
		// Disallow faulty plugins to ruin what we are trying to do here
		@ob_start();

		if ( !$this->is_active( $worker_id ) )
			return false;

		// Just in case
		require_once $this->plugin_dir . '/includes/external/google/Client.php';
		//require_once $this->plugin_dir . '/includes/external/google/AppointmentsGoogleConfig.php';

//		$config = new App_Google_AppointmentsGoogleConfig(apply_filters('app-gcal-client_parameters', array(
//			//'cache_class' => 'App_Google_Cache_Null', // For an example
//		)));
		$this->client = new Google_Client();
		$this->client->setApplicationName("Appointments+");
		//$this->client->setUseObjects(true);
		$key = $this->_file_get_contents( $worker_id );
		$this->client->setAssertionCredentials(new Google_Auth_AssertionCredentials(
			$this->get_service_account( $worker_id),
			array('https://www.googleapis.com/auth/calendar'),
			$key)
		);

		$this->service = new Google_Service_Calendar($this->client);

		return true;
	}

	/**
	 * Creates a Google Event object and set its parameters
	 * @param app: Appointment object to be set as event
	 */
	function set_event_parameters( $app, $worker_id=0 ) {
		global $appointments;
		$a = $appointments;
		
		$summary = sprintf(__('%s Appointment', 'appointments'), $a->get_service_name($app->service));

		if (isset($this->options["gcal_location"] ) && '' != trim( $this->options["gcal_location"])) {
			$location = str_replace( array('ADDRESS', 'CITY'), array($app->address, $app->city), $this->options["gcal_location"] );
		} else {
			$location = get_bloginfo('description');
		}

		// Find time difference from Greenwich as GCal asks UTC
		if (!current_time('timestamp')) $tdif = 0;
		else $tdif = current_time('timestamp') - time();

		$start = new Google_Service_Calendar_EventDateTime();
		$start->setDateTime(date("Y-m-d\TH:i:s\Z", strtotime($app->start) - $tdif));

		$end = new Google_Service_Calendar_EventDateTime();
		$end->setDateTime(date("Y-m-d\TH:i:s\Z", strtotime($app->end) - $tdif));

		// An email is always required
		if (!$app->email) $email = $a->get_worker_email($app->worker);
		else $email = $app->email;

		if (!$email) $email = $a->get_admin_email( );

		$attendee1 = new Google_Service_Calendar_EventAttendee();
		$attendee1->setEmail( $email );
		$attendees = array($attendee1);

		$this->event = new Google_Service_Calendar_Event();
		$this->event->setSummary( $summary );
		$this->event->setLocation( $location );
		$this->event->setStart( $start );
		$this->event->setEnd( $end );
		$this->event->setSummary(apply_filters(
			'app-gcal-set_summary',
			$a->_replace( $this->get_summary( $worker_id ), $app->name, $a->get_service_name($app->service), appointments_get_worker_name($app->worker), $app->start, $app->price, $a->get_deposit($app->price), $app->phone, $app->note, $app->address, $app->email, $app->city ),
			$app
		));
		$this->event->setDescription(apply_filters(
			'app-gcal-set_description',
			$a->_replace( $this->get_description( $worker_id ), $app->name, $a->get_service_name($app->service), appointments_get_worker_name($app->worker), $app->start, $app->price, $a->get_deposit($app->price), $app->phone, $app->note, $app->address, $app->email, $app->city ),
			$app
		));
		$this->event->attendees = $attendees;

		// Alright, now deal with event sequencing
		if (!empty($app->gcal_ID)) {
			$tmp = $this->service->events->get($this->get_selected_calendar( $worker_id ), $app->gcal_ID);
			$sequence = is_object($tmp) && !empty($tmp->sequence)
				? $tmp->sequence
				: (is_array($tmp) && !empty($tmp['sequence']) ? $tmp['sequence'] : false)
			;
			if (!empty($sequence)) $this->event->sequence = $sequence; // Add sequence if we have it
		}
	}

	private function _is_writable_mode ($worker_id=false) {
		$mode = $this->get_api_mode($worker_id);
		if ($worker_id && empty($mode)) $mode = $this->get_api_mode(); // Fallback to site default if no option selected.
		$mode = empty($mode)
			? 'none'
			: $mode
		; // If we still don't have a mode, then we don't want to sync.
		return !in_array($mode, array(
			// Non-writable modes
			'gcal2app',
			'none'
		));
	}

	/**
	 * Handle insertion of events depending on settings
	 * @param app_id: Appointment ID to be inserted
	 */
	function insert( $app_id ) {
		$app = appointments_get_appointment( $app_id );
		$worker_id = $app->worker;

		// No preference case
		if ( !$worker_id ) {
			if ($this->_is_writable_mode()) $this->insert_event($app_id);
		} else {
			// So, first up, let's go with worker
			if (isset($this->options['gcal_api_allow_worker']) && 'yes' == $this->options['gcal_api_allow_worker'] && $this->_is_writable_mode($worker_id)) {
				$this->insert_event($app_id, false, $worker_id);
			}
			// Add this to general calendar if selected so
			if (isset($this->options['gcal_api_scope']) && 'all' == $this->options['gcal_api_scope'] && $this->_is_writable_mode()) {
				$this->insert_event($app_id);
			}
		}
	}

	/**
	 * Inserts an appointment to the selected calendar as event
	 * @param app_id: Appointment ID to be inserted
	 * @test: Insert a test appointment
	 */
	function insert_event( $app_id, $test=false, $worker_id=0 ) {
		if (!$this->connect($worker_id)) return false;

		global $appointments, $wpdb;
		if ($test) {
			$app = new stdClass();
			$app->name = __('Test client name', 'appointments');
			$app->phone = __('Test phone', 'appointments');
			$app->address = __('Test address', 'appointments');
			$app->city = __('Test city', 'appointments');
			$app->worker = 0;
			$app->price = 123;
			$app->start = date( 'Y-m-d H:i:s', $this->local_time + 600 );
			$app->end = date( 'Y-m-d H:i:s', $this->local_time + 2400 );
			$app->service = appointments_get_services_min_id();
			$app->email = $appointments->get_admin_email( );
			$app->note = __('This is a test appointment inserted by Appointments+', 'appointments');
		} else {
			$app = appointments_get_appointment( $app_id );
		}


		// Create Event object and set parameters
		$this->set_event_parameters( $app, $app->worker );

		// Insert event
		try {
			$createdEvent = $this->service->events->insert( $this->get_selected_calendar( $worker_id ), $this->event );
			if ($createdEvent && !is_object($createdEvent) && class_exists('Google_Service_Calendar_CalendarListEntry')) $createdEvent = new Google_Service_Calendar_CalendarListEntry($createdEvent);

			// Write Event ID to database
			$gcal_ID = $createdEvent->getId();
			if ( $gcal_ID && !$test ) {

				$args = array(
					'gcal_updated' => date( "Y-m-d H:i:s", $this->local_time ),
					'gcal_ID' => $gcal_ID
				);
				appointments_update_appointment( $app_id, $args );
			} else {
				$appointments->log("The insert did not create a real result we can work with");
			}
			// Test result successful
			if ( $gcal_ID ) return true;
		} catch (Exception $e) {
			$appointments->log("Insert went wrong: " . $e->getMessage());
			return false;
		}
	}

	/**
	 * Handle update of events, if not exists insert
	 * @param app_id: Appointment ID to be updated
	 */
	function update( $app_id ) {
		global $appointments;
		$app = appointments_get_appointment( $app_id );
		$worker_id = $app->worker;
		if ( $app->gcal_ID ) {
			// Update this event from general calendar
			if ($this->_is_writable_mode()) $this->update_event( $app_id );
			// Also update service provider event if we have a provider
			if ($worker_id && $this->_is_writable_mode($worker_id)) $this->update_event( $app_id, $worker_id );
		} else {
			// First up update general calendar
			if ($this->_is_writable_mode()) $this->insert_event( $app_id, false );
			// Also insert for this service provider, if needed
			if ($worker_id && $this->_is_writable_mode($worker_id)) $this->insert_event( $app_id, false, $worker_id );
		}
	}

	/**
	 * Update event as appointment is modified
	 * @param app_id: Appointment ID to be updated
	 */
	function update_event( $app_id, $worker_id=0 ) {
		if ( !$this->connect( $worker_id ) )
			return false;

		global $appointments, $wpdb;
		$app = appointments_get_appointment( $app_id );
		if ( $app->gcal_ID ) {
			$this->set_event_parameters( $app, $worker_id );

			// Update event
			try {
				$updatedEvent = $this->service->events->update( $this->get_selected_calendar( $worker_id ), $app->gcal_ID, $this->event );
				if ($updatedEvent && !is_object($updatedEvent) && class_exists('Google_Service_Calendar_CalendarListEntry')) $updatedEvent = new Google_Service_Calendar_CalendarListEntry($updatedEvent);

				// Update Time of database
				$gcal_ID = $updatedEvent->getId();
				if ( $gcal_ID && $gcal_ID == $app->gcal_ID ) {
					appointments_update_appointment( $app_id, array( 'gcal_updated' => date ("Y-m-d H:i:s", $this->local_time ) ) );
				}

			} catch (Exception $e) {
				$appointments->log("Update went wrong: " . $e->getMessage());
			}
		}
	}

	/**
	 * Handle deletion of events
	 * @param app_id: Appointment ID that has been deleted
	 */
	function delete( $app_id ) {
		$app = appointments_get_appointment( $app_id );
//$appointments->log(sprintf("Attempting to delete the appointment %s from GCal", $app_id));
		$worker_id = $app->worker;
		// In any case delete this event from general calendar
		if ($this->_is_writable_mode()) $this->delete_event( $app_id );
		// Also delete service provider event if we have a provider
		if ($worker_id && $this->_is_writable_mode($worker_id)) $this->delete_event( $app_id, $worker_id );
	}

	/**
	 * Delete event as appointment is either removed, completed or set as pending
	 * @param app_id: Appointment ID that has been deleted
	 */
	function delete_event( $app_id, $worker_id=0 ) {
		global $appointments;
		if ( !$this->connect( $worker_id ) )
			return false;

		$app = appointments_get_appointment( $app_id );
//$appointments->log(sprintf("Deleting the appointment %s from GCal (worker id: %s)", $app_id, $worker_id));
		if ($app->gcal_ID) {
			try {
				$result = $this->service->events->delete( $this->get_selected_calendar( $worker_id ), $app->gcal_ID );
			} catch (Exception $e) {
				$appointments->log("Deleting went wrong: " . $e->getMessage());
			}
//$appointments->log(sprintf("The appointment %s (worker id: %s, GCal id: %s) deleted.", $app_id, $worker_id, $app->gcal_ID));
		} else {
//$appointments->log(sprintf("Deleting the appointment %s (worker id: %s) FAILED, unknown GCal id.", $app_id, $worker_id));
		}
	}

	/**
	 * Handle import and update of all Google Calendars
	 */
	function import_and_update( ) {
		global $appointments, $wpdb;
		// Check for general
		$this->updated = $this->inserted = $this->deleted = 0;
		if ('sync' === $this->get_api_mode()) $this->import_and_update_events();

		// Check for providers
		if ( isset( $this->options['gcal_api_allow_worker'] ) && 'yes' == $this->options['gcal_api_allow_worker'] ) {
			$results = $wpdb->get_results( "SELECT user_id FROM " . $wpdb->usermeta . " WHERE meta_key='app_api_mode' AND meta_value='sync' " );
			if ( $results ) {
				foreach( $results as $result ) {
					$this->import_and_update_events( $result->user_id );
				}
			}
		}
	}

	/**
	 * Import the list of events for the selected calendar for the worker and update them
	 * @param worker_id: ID of the worker whose list will be gotten
	 */
	function import_and_update_events( $worker_id=0 ) {
		global $appointments, $wpdb;
//$appointments->log("start import");
		if (!$this->connect($worker_id)) return false;
//$appointments->log(sprintf("connected to worker ID %d", $worker_id));


		// Find time difference from Greenwich as GCal time will be converted to UTC, but we want local time
		if (!current_time('timestamp')) $tdif = 0;
		else $tdif = current_time('timestamp') - time();

		$arguments = apply_filters('app_gcal_args', array(
			'timeMin' => apply_filters('app_gcal_time_min', date("c", $this->local_time)),
			'timeMax' => apply_filters('app_gcal_time_max', date("c", $this->local_time + 3600 * 24 * $appointments->get_app_limit())),
			'singleEvents' => apply_filters( 'app_gcal_single_events', true),
			'maxResults' => apply_filters('app_gcal_max_results', APP_GCAL_MAX_RESULTS_LIMIT),
			'orderBy' => apply_filters('app_gcal_orderby', 'startTime'),
		));
		// Get only future events and limit them with appointment limit setting and 500 events
		$error_code = false;
		$events = array();
		try {
			$events = $this->service->events->listEvents($this->get_selected_calendar($worker_id), $arguments);
		} catch (Exception $e) {
			$error_code = is_callable(array($e, 'getCode'))
				? $e->getCode() 
				: 'Unknown'
			;
		}

		// Simulate `finally` keyword
		if (!empty($error_code)) {
			$appointments->log(sprintf("Error fetching Google events: %s", $error_code));
		}
//$appointments->log(sprintf("got back some events: %d", ($events ? 1 : 0)));

		if ($events && class_exists('Google_Service_Calendar_Events') && !($events instanceof Google_Service_Calendar_Events)) {
			$events = new Google_Service_Calendar_Events;
			$events->setItems($events);
		}
		$message = '';
		$event_ids = array();
		$values = array();

		$present_events = $wpdb->get_col("SELECT DISTINCT gcal_ID FROM {$this->app_table} WHERE gcal_ID IS NOT NULL");
		$to_update = array();

		// Drop all previously reserved GCALs, because we'll reimport them now
		if (version_compare($this->db_version, '1.2.3', '<') && version_compare($this->db_version, '1.2.2', '=')) {
			$wpdb->query("DELETE FROM {$this->app_table} WHERE status='reserved'");
			$this->db_version = '1.2.3';
			update_option('app_db_version', $this->db_version);
		}

		if ( $events && is_array( $events->getItems()) ) {
			/** @var Google_Service_Calendar_Event $event */
			// Service ID is not important as we will use this record for blocking our time slots only
			$service_id = appointments_get_services_min_id();

			// Create a list of event_id's
			foreach ($events->getItems() as $event) {
				$event_id = $event->getID();
				// Add the ID to the list
				$event_ids[] = !empty($event_id) ? "'{$event_id}'" : '';

				$event_start = $event->getStart();
				$event_end = $event->getEnd();
				$event_updated = $event->getUpdated();

				$event_start_timestamp = strtotime($event_start->dateTime) + $tdif;
				$event_end_timestamp = strtotime($event_end->dateTime) + $tdif;
				$event_updated_timestamp = strtotime($event_updated) + $tdif; // This is not datetime object

				// Check start and end times as in case of all day events this field is empty
				if ( $event_id && $event_start_timestamp > $this->local_time && $event_end_timestamp > $this->local_time ) {
						if (!in_array($event_id, $present_events)) {
							$present_events[] = $event_id;
							$values[] = "('". date( "Y-m-d H:i:s", $this->local_time ) ."',". $service_id .",". $worker_id .",'reserved','". date( "Y-m-d H:i:s", strtotime($event_start->dateTime) + $tdif )
								."','". date( "Y-m-d H:i:s", $event_end_timestamp ) ."','". $wpdb->escape($event->getSummary()) ."','". $event_id ."','". date( "Y-m-d H:i:s", $event_updated_timestamp ) ."')";
						} else {
							$to_update[] = "start='" . date("Y-m-d H:i:s", $event_start_timestamp) . "', end='" . date("Y-m-d H:i:s", $event_end_timestamp) . "', gcal_updated='" . date("Y-m-d H:i:s", $event_updated_timestamp) . "' WHERE gcal_ID='" . $event_id . "' LIMIT 1";
						}
				}
			}

			// Insert new events
			if ( !empty( $values ) ) {
				// Try to adjust auto increment value
				$this->adjust_auto_increment();
				// Insert and update all events with a single query
				$result = $wpdb->query( "INSERT INTO " . $this->app_table . " (created,service,worker,status,start,end,note,gcal_ID,gcal_updated)
					VALUES ". implode(',',$values).  "
					ON DUPLICATE KEY UPDATE start=VALUES(start), end=VALUES(end), gcal_updated=VALUES(gcal_updated)" ); // Key is autoincrement, it'll never update!

				if ( $result ) {
					$message = sprintf( __('%s appointment record(s) affected.','appointments'), $result ). ' ';
				}
			}

			// Update existing events
			if (!empty($to_update)) {
				$result = (int)$result;
				foreach ($to_update as $upd) {
					$res2 = $wpdb->query("UPDATE {$this->app_table} SET {$upd}");
					if ($res2) $result++;
				}
				$message = sprintf( __('%s appointment record(s) affected.','appointments'), $result ). ' ';
			}
		}

		// Next step - deal with the previously imported appointments removal
		$r3 = false;
		if (!empty($event_ids)) {
			// Delete unlisted events for the selected worker
			$event_ids_range = '(' . join(array_filter($event_ids), ',') . ')';
			$r3 = $wpdb->query($wpdb->prepare("DELETE FROM {$this->app_table} WHERE status='reserved' AND worker=%d AND gcal_ID NOT IN {$event_ids_range}", $worker_id));
		} else { // In case we have existing reserved events that have been removed from GCal and nothing else
			$r3 = $wpdb->query($wpdb->prepare("DELETE FROM {$this->app_table} WHERE status='reserved' AND worker=%d AND gcal_ID IS NOT NULL", $worker_id));
		}
		if ($r3) { // We have deleted some appointments in previous step.
			$this->deleted++;
			$this->adjust_auto_increment();
		}

		if ( $this->deleted )
			$message .= sprintf( __('%s appointment(s) deleted.','appointments'), $this->deleted ). ' ';

		wp_cache_flush();
		delete_transient( 'app_timetables' );
		return $message;
	}

	/**
	 * Prevent auto increment go to too high values because of INSERT INTO DUPLICATE KEY clause or multiple deletes
	 * @since 1.2.4
	 */
	function adjust_auto_increment() {
		global $wpdb;
		$max = $wpdb->get_var( "SELECT MAX(ID) FROM " . $this->app_table . " " );
		if ( $max )
			 $wpdb->query( "ALTER TABLE " . $this->app_table ." AUTO_INCREMENT=". ($max+1). " " );
	}

	private function _get_syncable_status () {
		return apply_filters('app-gcal-syncable_status', array('paid', 'confirmed'));
	}

	public function is_syncable_status ($status=false) {
		$syncable_status = $this->_get_syncable_status();
		return in_array($status, $syncable_status);
	}
}

}

<?php

class Appointments_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_init' ) ); 						// Creates admin settings window
		add_action( 'admin_notices', array( $this, 'admin_notices' ) ); 				// Warns admin
		add_action( 'admin_print_scripts', array( $this, 'admin_scripts') );			// Load scripts
		add_action( 'admin_print_styles', array( $this, 'admin_css') );

		//@TODO: This filter is deprecated
		add_action( 'dashboard_glance_items', array($this, 'add_app_counts') );

		add_action( 'show_user_profile', array( $this, 'show_profile') );
		add_action( 'edit_user_profile', array( $this, 'show_profile') );
		add_action( 'personal_options_update', array( $this, 'save_profile') );
		add_action( 'edit_user_profile_update', array( $this, 'save_profile') );

	}

	/**
	 * Saves working hours from user profile
	 */
	function save_profile( $profileuser_id ) {
		global $current_user, $wpdb, $appointments;

		// Copy key file to uploads folder
		if ( is_object( $appointments->gcal_api ) ) {
			$kff = $appointments->gcal_api->key_file_folder( ); // Key file folder
			$kfn = $appointments->gcal_api->get_key_file( $profileuser_id ). '.p12'; // Key file name
			if ( $kfn && is_dir( $kff ) && !file_exists( $kff . $kfn ) && file_exists( $appointments->plugin_dir . '/includes/gcal/key/' . $kfn ) )
				copy( $appointments->plugin_dir . '/includes/gcal/key/' . $kfn, $kff . $kfn );
		}

		// Only user himself can save his data
		if ( $current_user->ID != $profileuser_id )
			return;

		// Save user meta
		if ( isset( $_POST['app_name'] ) )
			update_user_meta( $profileuser_id, 'app_name', $_POST['app_name'] );
		if ( isset( $_POST['app_email'] ) )
			update_user_meta( $profileuser_id, 'app_email', $_POST['app_email'] );
		if ( isset( $_POST['app_phone'] ) )
			update_user_meta( $profileuser_id, 'app_phone', $_POST['app_phone'] );
		if ( isset( $_POST['app_address'] ) )
			update_user_meta( $profileuser_id, 'app_address', $_POST['app_address'] );
		if ( isset( $_POST['app_city'] ) )
			update_user_meta( $profileuser_id, 'app_city', $_POST['app_city'] );

		// Save Google API settings
		if ( isset( $_POST['gcal_api_mode'] ) )
			update_user_meta( $profileuser_id, 'app_api_mode', $_POST['gcal_api_mode'] );
		if ( isset( $_POST['gcal_service_account'] ) )
			update_user_meta( $profileuser_id, 'app_service_account', trim( $_POST['gcal_service_account'] ) );
		if ( isset( $_POST['gcal_key_file'] ) )
			update_user_meta( $profileuser_id, 'app_key_file', trim( str_replace( '.p12', '', $_POST['gcal_key_file'] ) ) );
		if ( isset( $_POST['gcal_selected_calendar'] ) )
			update_user_meta( $profileuser_id, 'app_selected_calendar', trim( $_POST['gcal_selected_calendar'] ) );
		if ( isset( $_POST['gcal_summary'] ) ) {
			if ( !trim( $_POST['gcal_summary'] ) )
				$summary = __('SERVICE Appointment','appointments');
			else
				$summary = $_POST['gcal_summary'];
			update_user_meta( $profileuser_id, 'app_gcal_summary', $summary );
		}
		if ( isset( $_POST['gcal_description'] ) ) {
			if ( !trim( $_POST['gcal_description'] ) ) {
				$gcal_description = __("Client Name: CLIENT\nService Name: SERVICE\nService Provider Name: SERVICE_PROVIDER\n", "appointments");
			} else {
				$gcal_description = $_POST['gcal_description'];
			}
			update_user_meta( $profileuser_id, 'app_gcal_description', $gcal_description );
		}

		// Cancel appointment
		if ( isset( $appointments->options['allow_cancel'] ) && 'yes' == $appointments->options['allow_cancel'] &&
		     isset( $_POST['app_cancel'] ) && is_array( $_POST['app_cancel'] ) && !empty( $_POST['app_cancel'] ) ) {
			foreach ( $_POST['app_cancel'] as $app_id=>$value ) {
				if ( $appointments->change_status( 'removed', $app_id ) ) {
					$appointments->log( sprintf( __('Client %s cancelled appointment with ID: %s','appointments'), $appointments->get_client_name( $app_id ), $app_id ) );
					$appointments->send_notification( $app_id, true );

					if (!empty($appointments->gcal_api) && is_object($appointments->gcal_api)) $appointments->gcal_api->delete($app_id); // Drop the cancelled appointment
					else if (!defined('APP_GCAL_DISABLE')) $appointments->log("Unable to issue a remote call to delete the remote appointment.");

					// Do we also do_action app-appointments-appointment_cancelled?
				}
			}
		}

		// Only user who is a worker can save the rest
		if ( ! appointments_is_worker( $profileuser_id ) )
			return;

		// Confirm an appointment using profile page
		if ( isset( $_POST['app_confirm'] ) && is_array( $_POST['app_confirm'] ) && !empty( $_POST['app_confirm'] ) ) {
			foreach ( $_POST['app_confirm'] as $app_id=>$value ) {
				if ( $appointments->change_status( 'confirmed', $app_id ) ) {
					$appointments->log( sprintf( __('Service Provider %s manually confirmed appointment with ID: %s','appointments'), $appointments->get_worker_name( $current_user->ID ), $app_id ) );
					$appointments->send_confirmation( $app_id );
				}
			}
		}

		// Save working hours table
		// Do not save these if we are coming from BuddyPress confirmation tab
		if ( isset($appointments->options["allow_worker_wh"]) && 'yes' == $appointments->options["allow_worker_wh"] && isset( $_POST['open'] ) && isset( $_POST['closed'] ) ) {
			$result = $result2 = false;
			$location = 0;
			foreach ( array( 'closed', 'open' ) as $stat ) {
				$count = $wpdb->get_var($wpdb->prepare(
					"SELECT COUNT(*) FROM {$appointments->wh_table} WHERE location=%d AND worker=%d AND status=%s",
					$location, $profileuser_id, $stat
				));

				if ( $count > 0 ) {
					$result = $wpdb->update( $appointments->wh_table,
						array( 'hours'=>serialize($_POST[$stat]), 'status'=>$stat ),
						array( 'location'=>$location, 'worker'=>$profileuser_id, 'status'=>$stat ),
						array( '%s', '%s' ),
						array( '%d', '%d', '%s' )
					);
				}
				else {
					$result = $wpdb->insert( $appointments->wh_table,
						array( 'location'=>$location, 'worker'=>$profileuser_id, 'hours'=>serialize($_POST[$stat]), 'status'=>$stat ),
						array( '%d', '%d', '%s', '%s' )
					);
				}
				// Save exceptions
				$count2 = $wpdb->get_var($wpdb->prepare(
					"SELECT COUNT(*) FROM {$appointments->exceptions_table} WHERE location=%d AND worker=%d AND status=%s",
					$location, $profileuser_id, $stat
				));

				if ( $count2 > 0 ) {
					$result2 = $wpdb->update( $appointments->exceptions_table,
						array(
							'days'		=> $_POST[$stat]["exceptional_days"],
							'status'	=> $stat
						),
						array(
							'location'	=> $location,
							'worker'	=> $profileuser_id,
							'status'	=> $stat ),
						array( '%s', '%s' ),
						array( '%d', '%d', '%s' )
					);
				}
				else {
					$result2 = $wpdb->insert( $appointments->exceptions_table,
						array( 'location'	=> $location,
						       'worker'	=> $profileuser_id,
						       'days'		=> $_POST[$stat]["exceptional_days"],
						       'status'	=> $stat
						),
						array( '%d', '%d', '%s', '%s' )
					);
				}


			}
			if ( $result || $result2 ) {
				$message = sprintf( __('%s edited his working hours.', 'appointments'), $appointments->get_worker_name( $profileuser_id ) );
				$appointments->log( $message );
				// Employer can be noticed here
				do_action( "app_working_hour_update", $message, $profileuser_id );
				// Also clear cache
				$appointments->flush_cache();
			}
		}
	}

	/**
	 * Displays appointment schedule on the user profile
	 */
	function show_profile( $profileuser ) {
		global $current_user, $appointments;

		// Only user or admin can see his data
		if ( $current_user->ID != $profileuser->ID && !App_Roles::current_user_can('list_users', CTX_STAFF) )
			return;

		// For other than user himself, display data as readonly
		if ( $current_user->ID != $profileuser->ID )
			$is_readonly = ' readonly="readonly"';
		else
			$is_readonly = '';

		$is_readonly = apply_filters( 'app_show_profile_readonly', $is_readonly, $profileuser );

		if ( isset( $appointments->options["gcal"] ) && 'yes' == $appointments->options["gcal"] )
			$gcal = ''; // Default is already enabled
		else
			$gcal = ' gcal="0"';
		?>
		<h3><?php _e("Appointments+", 'appointments'); ?></h3>

		<table class="form-table">
			<tr>
				<th><label><?php _e("My Name", 'appointments'); ?></label></th>
				<td>
					<input type="text" style="width:25em" name="app_name" value="<?php echo get_user_meta( $profileuser->ID, 'app_name', true ) ?>" <?php echo $is_readonly ?> />
				</td>
			</tr>

			<tr>
				<th><label><?php _e("My email for A+", 'appointments'); ?></label></th>
				<td>
					<input type="text" style="width:25em" name="app_email" value="<?php echo get_user_meta( $profileuser->ID, 'app_email', true ) ?>" <?php echo $is_readonly ?> />
				</td>
			</tr>

			<tr>
				<th><label><?php _e("My Phone", 'appointments'); ?></label></th>
				<td>
					<input type="text" style="width:25em" name="app_phone" value="<?php echo get_user_meta( $profileuser->ID, 'app_phone', true ) ?>"<?php echo $is_readonly ?> />
				</td>
			</tr>

			<tr>
				<th><label><?php _e("My Address", 'appointments'); ?></label></th>
				<td>
					<input type="text" style="width:50em" name="app_address" value="<?php echo get_user_meta( $profileuser->ID, 'app_address', true ) ?>" <?php echo $is_readonly ?> />
				</td>
			</tr>

			<tr>
				<th><label><?php _e("My City", 'appointments'); ?></label></th>
				<td>
					<input type="text" style="width:25em" name="app_city" value="<?php echo get_user_meta( $profileuser->ID, 'app_city', true ) ?>" <?php echo $is_readonly ?> />
				</td>
			</tr>

			<?php if ( ! appointments_is_worker( $profileuser->ID ) ) { ?>
				<tr>
					<th><label><?php _e("My Appointments", 'appointments'); ?></label></th>
					<td>
						<?php echo do_shortcode("[app_my_appointments allow_cancel=1 client_id=".$profileuser->ID." ".$gcal."]") ?>
					</td>
				</tr>
			<?php
			if ( isset( $appointments->options['allow_cancel'] ) && 'yes' == $appointments->options['allow_cancel'] ) { ?>
				<script type='text/javascript'>
					jQuery(document).ready(function($){
						$('#your-profile').submit(function() {
							if ( $('.app-my-appointments-cancel').is(':checked') ) {
								if ( !confirm('<?php echo esc_js( __("Are you sure to cancel the selected appointment(s)?","appointments") ) ?>') )
								{return false;}
							}
						});
					});
				</script>
			<?php
			}
			}
			else { ?>
				<tr>
					<th><label><?php _e("My Appointments as Provider", 'appointments'); ?></label></th>
					<td>
						<?php echo do_shortcode("[app_my_appointments status='pending,confirmed,paid' _allow_confirm=1 provider_id=".$profileuser->ID."  provider=1 ".$gcal."]") ?>
					</td>
				</tr>
			<?php
			if ( isset( $appointments->options['allow_worker_confirm'] ) && 'yes' == $appointments->options['allow_worker_confirm'] ) { ?>
				<script type='text/javascript'>
					jQuery(document).ready(function($){
						$('#your-profile').submit(function() {
							if ( $('.app-my-appointments-confirm').is(':checked') ) {
								if ( !confirm('<?php echo esc_js( __("Are you sure to confirm the selected appointment(s)?","appointments") ) ?>') )
								{return false;}
							}
						});
					});
				</script>
			<?php
			}
			if ( isset($appointments->options["allow_worker_wh"]) && 'yes' == $appointments->options["allow_worker_wh"] ) { ?>
			<?php
			// A little trick to pass correct lsw variables to the related function
			$_REQUEST["app_location_id"] = 0;
			$_REQUEST["app_provider_id"] = $profileuser->ID;

			$appointments->get_lsw();

			$result = array();
			$result_open = $appointments->get_exception( $appointments->location, $appointments->worker, 'open' );
			if ( $result_open )
				$result["open"] = $result_open->days;
			else
				$result["open"] = null;

			$result_closed = $appointments->get_exception( $appointments->location, $appointments->worker, 'closed' );
			if ( $result_closed )
				$result["closed"] = $result_closed->days;
			else
				$result["closed"] = null;
			?>
				<tr>
					<th><label><?php _e("My Working Hours", 'appointments'); ?></label></th>
					<td>
						<?php echo $appointments->working_hour_form('open') ?>
					</td>
				</tr>
				<tr>
					<th><label><?php _e("My Break Hours", 'appointments'); ?></label></th>
					<td>
						<?php echo $appointments->working_hour_form('closed') ?>
					</td>
				</tr>
				<tr>
					<th><label><?php _e("My Exceptional Working Days", 'appointments'); ?></label></th>
					<td>
						<input class="datepick" id="open_datepick" type="text" style="width:100%" name="open[exceptional_days]" value="<?php if (isset($result["open"])) echo $result["open"]?>" />
					</td>
				</tr>
				<tr>
					<th><label><?php _e("My Holidays", 'appointments'); ?></label></th>
					<td>
						<input class="datepick" id="closed_datepick" type="text" style="width:100%" name="closed[exceptional_days]" value="<?php if (isset($result["closed"])) echo $result["closed"]?>" />
					</td>
				</tr>
				<script type="text/javascript">
					jQuery(document).ready(function($){
						$("#open_datepick").datepick({dateFormat: 'yyyy-mm-dd',multiSelect: 999, monthsToShow: 2});
						$("#closed_datepick").datepick({dateFormat: 'yyyy-mm-dd',multiSelect: 999, monthsToShow: 2});
					});
				</script>
			<?php } ?>
			<?php } ?>
			<?php if ( isset($appointments->options["gcal_api_allow_worker"]) && 'yes' == $appointments->options["gcal_api_allow_worker"] && appointments_is_worker( $profileuser->ID ) ) { ?>
				<tr>
					<th><label><?php _e("Appointments+ Google Calendar API", 'appointments'); ?></label></th>
					<td>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<?php
						if ( is_object( $appointments->gcal_api ) )
							$appointments->gcal_api->display_nag( $profileuser->ID ); ?>
					</td>
				</tr>
				<?php
				if ( is_object( $appointments->gcal_api ) )
					$appointments->gcal_api->display_settings( $profileuser->ID );
			} ?>
		</table>
		<?php
	}

	/**
	 * Add app status counts in admin Right Now Dashboard box
	 * http://codex.wordpress.org/Plugin_API/Action_Reference/right_now_content_table_end
	 */
	function add_app_counts( $items ) {

		global $wpdb, $appointments;

		$new_items = array();

		$num_active = $wpdb->get_var("SELECT COUNT(ID) FROM " . $appointments->app_table . " WHERE status='paid' OR status='confirmed' " );

		if ( $num_active ) {
			$num = number_format_i18n( $num_active );
			$text = sprintf( _n( '%d Active Appointment', '%d Active Appointments', intval( $num_active ) ), $num );
			if ( App_Roles::current_user_can( 'manage_options', App_Roles::CTX_DASHBOARD ) )
				$items[] = '<a class="app-active" href="admin.php?page=appointments">' . $text . '</a>';
			else
				$items[] = $text;
		}

		$num_pending = $wpdb->get_var("SELECT COUNT(ID) FROM " . $appointments->app_table . " WHERE status='pending' " );

		if ( $num_pending > 0 ) {
			$num = number_format_i18n( $num_pending );
			$text = sprintf( _n( '%d Pending Appointment', '%d Pending Appointments', intval( $num_pending ) ), $num );
			if ( App_Roles::current_user_can( 'manage_options', App_Roles::CTX_DASHBOARD ) )
				$items[] = '<a class="app-pending" href="admin.php?page=appointments&type=pending">' . $text . '</a>';
			else
				$items[] = $text;
		}

		return $items;
	}

	function admin_css() {
		global $appointments;
		wp_enqueue_style( "appointments-admin", $appointments->plugin_url . "/css/admin.css", false, $appointments->version );

		$screen = get_current_screen();
		$title = sanitize_title(__('Appointments', 'appointments'));

		$allow_profile = !empty($appointments->options['allow_worker_wh']) && 'yes' == $appointments->options['allow_worker_wh'];

		if (empty($screen->base) || (
				!preg_match('/(^|\b|_)appointments($|\b|_)/', $screen->base)
				&&
				!preg_match('/(^|\b|_)' . preg_quote($title, '/') . '($|\b|_)/', $screen->base) // Super-weird admin screen base being translatable!!!
				&&
				(!$allow_profile || !preg_match('/profile/', $screen->base) || !(defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE))
			)) return;

		wp_enqueue_style( 'jquery-colorpicker-css', $appointments->plugin_url . '/css/colorpicker.css', false, $appointments->version);
		wp_enqueue_style( "jquery-datepick", $appointments->plugin_url . "/css/jquery.datepick.css", false, $appointments->version );
		wp_enqueue_style( "jquery-multiselect", $appointments->plugin_url . "/css/jquery.multiselect.css", false, $appointments->version );
		wp_enqueue_style( "jquery-ui-smoothness", $appointments->plugin_url . "/css/smoothness/jquery-ui-1.8.16.custom.css", false, $appointments->version );
		do_action('app-admin-admin_styles');
	}

	// Enqeue js on admin pages
	function admin_scripts() {
		global $appointments;
		$screen = get_current_screen();
		$title = sanitize_title(__('Appointments', 'appointments'));

		$allow_profile = !empty($appointments->options['allow_worker_wh']) && 'yes' == $appointments->options['allow_worker_wh'];

		if (empty($screen->base) || (
				!preg_match('/(^|\b|_)appointments($|\b|_)/', $screen->base)
				&&
				!preg_match('/(^|\b|_)' . preg_quote($title, '/') . '($|\b|_)/', $screen->base) // Super-weird admin screen base being translatable!!!
				&&
				(!$allow_profile || !preg_match('/profile/', $screen->base) || !(defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE))
			)) return false;

		wp_enqueue_script( 'jquery-colorpicker', $appointments->plugin_url . '/js/colorpicker.js', array('jquery'), $appointments->version);
		wp_enqueue_script( 'jquery-datepick', $appointments->plugin_url . '/js/jquery.datepick.min.js', array('jquery'), $appointments->version);
		wp_enqueue_script( 'jquery-multiselect', $appointments->plugin_url . '/js/jquery.multiselect.min.js', array('jquery-ui-core','jquery-ui-widget', 'jquery-ui-position'), $appointments->version);
		// Make a locale check to update locale_error flag
		$date_check = $appointments->to_us( date_i18n( $appointments->safe_date_format(), strtotime('today') ) );

		// Localize datepick only if not defined otherwise
		if (
			!(defined('APP_FLAG_SKIP_DATEPICKER_L10N') && APP_FLAG_SKIP_DATEPICKER_L10N)
			&&
			$file = $appointments->datepick_localfile()
		) {
			//if ( !$this->locale_error ) wp_enqueue_script( 'jquery-datepick-local', $this->plugin_url . $file, array('jquery'), $this->version);
			wp_enqueue_script( 'jquery-datepick-local', $appointments->plugin_url . $file, array('jquery'), $appointments->version);
		}
		if ( empty($appointments->options["disable_js_check_admin"]) )
			wp_enqueue_script( 'app-js-check', $appointments->plugin_url . '/js/js-check.js', array('jquery'), $appointments->version);

		wp_enqueue_script("appointments-admin", $appointments->plugin_url . "/js/admin.js", array('jquery'), $appointments->version);
		wp_localize_script("appointments-admin", "_app_admin_data", array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'strings' => array(
				'preparing_export' => __('Preparing for export, please hold on...', 'appointments'),
			),
		));
		do_action('app-admin-admin_scripts');
	}

	/**
	 *	Dismiss warning messages for the current user for the session
	 *	@since 1.1.7
	 */
	function dismiss() {
		global $current_user;
		if ( isset( $_REQUEST['app_dismiss'] ) ) {
			update_user_meta( $current_user->ID, 'app_dismiss', true );
			?><div class="updated fade"><p><?php _e('Notice dismissed.', 'appointments'); ?></p></div><?php
		}
		if ( isset( $_REQUEST['app_dismiss_google'] ) ) {
			update_user_meta( $current_user->ID, 'app_dismiss_google', true );
			?><div class="updated fade"><p><?php _e('Notice dismissed.', 'appointments'); ?></p></div><?php
		}
		if ( isset( $_REQUEST['app_dismiss_confirmation_lacking'] ) ) {
			update_user_meta( $current_user->ID, 'app_dismiss_confirmation_lacking', true );
			?><div class="updated fade"><p><?php _e('Notice dismissed.', 'appointments'); ?></p></div><?php
		}
	}

	/**
	 *	Warn admin if no services defined or duration is wrong
	 */
	function admin_notices() {
		global $appointments;
		$this->dismiss();

		global $current_user;
		$r = false;
		$results = appointments_get_services();
		if ( !$results ) {
			echo '<div class="error"><p>' .
			     __('<b>[Appointments+]</b> You must define at least once service.', 'appointments') .
			     '</p></div>';
			$r = true;
		}
		else {
			foreach ( $results as $result ) {
				if ( $result->duration < $appointments->get_min_time() ) {
					echo '<div class="error"><p>' .
					     __('<b>[Appointments+]</b> One of your services has a duration smaller than time base. Please visit Services tab and after making your corrections save new settings.', 'appointments') .
					     '</p></div>';
					$r = true;
					break;
				}
				if ( $result->duration % $appointments->get_min_time() != 0 ) {
					echo '<div class="error"><p>' .
					     __('<b>[Appointments+]</b> One of your services is not divisible by the time base. Please visit Services tab and after making your corrections save new settings.', 'appointments') .
					     '</p></div>';
					$r = true;
					break;
				}
				if ( $result->duration > 1440 ) {
					echo '<div class="error"><p>' .
					     __('<b>[Appointments+]</b> One of your services has a duration greater than 24 hours. Appointments+ does not support services exceeding 1440 minutes (24 hours). ', 'appointments') .
					     '</p></div>';
					$r = true;
					break;
				}
				$dismissed = false;
				$dismiss_id = get_user_meta( $current_user->ID, 'app_dismiss', true );
				if ( $dismiss_id )
					$dismissed = true;
				if ( $appointments->get_workers() && !$appointments->get_workers_by_service( $result->ID ) && !$dismissed ) {
					echo '<div class="error"><p>' .
					     __('<b>[Appointments+]</b> One of your services does not have a service provider assigned. Delete services you are not using.', 'appointments') .
					     '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a title="'.__('Dismiss this notice for this session', 'appointments').'" href="' . $_SERVER['REQUEST_URI'] . '&app_dismiss=1"><small>'.__('Dismiss', 'appointments').'</small></a>'.
					     '</p></div>';
					$r = true;
					break;
				}
			}
		}
		if ( !$appointments->db_version || version_compare( $appointments->db_version, '1.2.2', '<' ) ) {
			echo '<div class="error"><p>' .
			     __('<b>[Appointments+]</b> Appointments+ database tables need to be updated. Please deactivate and reactivate the plugin (DO NOT DELETE the plugin). You will not lose any saved information.', 'appointments') .
			     '</p></div>';
			$r = true;
		}
		// Warn if Openid is not loaded
		$dismissed_g = false;
		$dismiss_id_g = get_user_meta( $current_user->ID, 'app_dismiss_google', true );
		if ( $dismiss_id_g )
			$dismissed_g = true;
		if ( @$appointments->options['accept_api_logins'] && !@$appointments->openid && !$dismissed_g ) {
			echo '<div class="error"><p>' .
			     __('<b>[Appointments+]</b> Either php curl is not installed or HTTPS wrappers are not enabled. Login with Google+ will not work.', 'appointments') .
			     '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a title="'.__('Dismiss this notice for this session', 'appointments').'" href="' . $_SERVER['REQUEST_URI'] . '&app_dismiss_google=1"><small>'.__('Dismiss', 'appointments').'</small></a>'.
			     '</p></div>';
			$r = true;
		}
		// Check for duplicate shortcodes for a visited page
		if ( isset( $_GET['post'] ) && $_GET['post'] && $appointments->has_duplicate_shortcode( $_GET['post'] ) ) {
			echo '<div class="error"><p>' .
			     __('<b>[Appointments+]</b> More than one instance of services, service providers, confirmation, Paypal or login shortcodes on the same page may cause problems.</p>', 'appointments' ).
			     '</div>';
		}

		// Check for missing confirmation shortcode
		$dismissed_c = false;
		$dismiss_id_c = get_user_meta( $current_user->ID, 'app_dismiss_confirmation_lacking', true );
		if ( $dismiss_id_c )
			$dismissed_c = true;
		if ( !$dismissed_c && isset( $_GET['post'] ) && $_GET['post'] && $appointments->confirmation_shortcode_missing( $_GET['post'] ) ) {
			echo '<div class="error"><p>' .
			     __('<b>[Appointments+]</b> Confirmation shortcode [app_confirmation] is always required to complete an appointment.', 'appointments') .
			     '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a title="'.__('Dismiss this notice for this session', 'appointments').'" href="' . $_SERVER['REQUEST_URI'] . '&app_dismiss_confirmation_lacking=1"><small>'.__('Dismiss', 'appointments').'</small></a>'.
			     '</p></div>';
			$r = true;
		}
		return $r;
	}

	/**
	 *	Creates the list for Appointments admin page
	 */
	function appointment_list() {
		App_Template::admin_appointments_list();

	}

	function transactions () {
		App_Template::admin_transactions_list();
	}

	function shortcodes_page () {
		global $appointments;
		?>
		<div class="wrap">
			<div class="icon32" style="margin:10px 0 0 0"><img src="<?php echo $appointments->plugin_url . '/images/general.png'; ?>" /></div>
			<h2><?php echo __('Appointments+ Shortcodes','appointments'); ?></h2>
			<div class="metabox-holder columns-2">
				<?php if (file_exists(APP_PLUGIN_DIR . '/includes/support/app-shortcodes.php')) include(APP_PLUGIN_DIR . '/includes/support/app-shortcodes.php'); ?>
			</div>
		</div>
		<?php
	}

	function faq_page () {
		global $appointments;
		?>
		<div class="wrap">
			<div class="icon32" style="margin:10px 0 0 0"><img src="<?php echo $appointments->plugin_url . '/images/general.png'; ?>" /></div>
			<h2><?php echo __('Appointments+ FAQ','appointments'); ?></h2>
			<?php if (file_exists(APP_PLUGIN_DIR . '/includes/support/app-faq.php')) include(APP_PLUGIN_DIR . '/includes/support/app-faq.php'); ?>
		</div>
		<?php
	}


	/**
	 *	Admin pages init stuff, save settings
	 *
	 */
	function admin_init() {
		global $appointments;
		if ( !session_id() )
			@session_start();

		$page = add_menu_page('Appointments', __('Appointments','appointments'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_APPOINTMENTS),  'appointments', array(&$this,'appointment_list'),'dashicons-clock');
		add_submenu_page('appointments', __('Transactions','appointments'), __('Transactions','appointments'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_TRANSACTIONS), "app_transactions", array(&$this,'transactions'));
		add_submenu_page('appointments', __('Settings','appointments'), __('Settings','appointments'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_SETTINGS), "app_settings", array(&$this,'settings'));
		add_submenu_page('appointments', __('Shortcodes','appointments'), __('Shortcodes','appointments'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_SHORTCODES), "app_shortcodes", array(&$this,'shortcodes_page'));
		add_submenu_page('appointments', __('FAQ','appointments'), __('FAQ','appointments'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_FAQ), "app_faq", array(&$this,'faq_page'));
		// Add datepicker to appointments page
		add_action( "admin_print_scripts-$page", array( &$this, 'admin_scripts' ) );

		do_action('app-admin-admin_pages_added', $page);

		if ( isset($_POST["action_app"]) && !wp_verify_nonce($_POST['app_nonce'],'update_app_settings') ) {
			add_action( 'admin_notices', array( &$this, 'warning' ) );
			return;
		}

		// Read Location, Service, Worker
		$appointments->get_lsw();
		global $wpdb;

		if ( isset($_POST["action_app"]) && 'save_general' == $_POST["action_app"] ) {
			$appointments->options["min_time"]					= $_POST["min_time"];
			$appointments->options["additional_min_time"]		= trim( $_POST["additional_min_time"] );
			$appointments->options["admin_min_time"]			= $_POST["admin_min_time"];
			$appointments->options["app_lower_limit"]			= trim( $_POST["app_lower_limit"] );
			$appointments->options["app_limit"]					= trim( $_POST["app_limit"] );
			$appointments->options["clear_time"]				= trim( $_POST["clear_time"] );
			$appointments->options["spam_time"]					= trim( $_POST["spam_time"] );
			$appointments->options["auto_confirm"]				= $_POST["auto_confirm"];
			$appointments->options["allow_worker_wh"]			= $_POST["allow_worker_wh"];
			$appointments->options["allow_worker_confirm"]		= $_POST["allow_worker_confirm"];
			$appointments->options["allow_overwork"]			= $_POST["allow_overwork"];
			$appointments->options["allow_overwork_break"]		= $_POST["allow_overwork_break"];
			$appointments->options["dummy_assigned_to"]			= !$appointments->is_dummy( @$_POST["dummy_assigned_to"] ) ? @$_POST["dummy_assigned_to"] : 0;

			$appointments->options["login_required"]			= $_POST["login_required"];
			$appointments->options["accept_api_logins"]			= isset( $_POST["accept_api_logins"] );
			$appointments->options["facebook-no_init"]			= isset( $_POST["facebook-no_init"] );
			$appointments->options['facebook-app_id']			= trim( $_POST['facebook-app_id'] );
			$appointments->options['twitter-app_id']			= trim( $_POST['twitter-app_id'] );
			$appointments->options['twitter-app_secret']		= trim( $_POST['twitter-app_secret'] );
			$appointments->options['google-client_id']			= trim( $_POST['google-client_id'] );

			$appointments->options["app_page_type"]				= $_POST["app_page_type"];
			$appointments->options["show_legend"]				= $_POST["show_legend"];
			$appointments->options["color_set"]					= $_POST["color_set"];
			foreach ( $appointments->get_classes() as $class=>$name ) {
				$appointments->options[$class."_color"]			= $_POST[$class."_color"];
			}
			$appointments->options["ask_name"]					= isset( $_POST["ask_name"] );
			$appointments->options["ask_email"]					= isset( $_POST["ask_email"] );
			$appointments->options["ask_phone"]					= isset( $_POST["ask_phone"] );
			$appointments->options["ask_phone"]					= isset( $_POST["ask_phone"] );
			$appointments->options["ask_address"]				= isset( $_POST["ask_address"] );
			$appointments->options["ask_city"]					= isset( $_POST["ask_city"] );
			$appointments->options["ask_note"]					= isset( $_POST["ask_note"] );
			$appointments->options["additional_css"]			= trim( stripslashes_deep($_POST["additional_css"]) );

			$appointments->options["payment_required"]			= $_POST["payment_required"];
			$appointments->options["percent_deposit"]			= trim( str_replace( '%', '', $_POST["percent_deposit"] ) );
			$appointments->options["fixed_deposit"]				= trim( str_replace( $appointments->options["currency"], '', $_POST["fixed_deposit"] ) );

			/*
			 * Membership plugin is replaced by Membership2. Old options are
			 * only saved when the depreacted Membership plugin is still active.
			 */
			if ( class_exists( 'M_Membership' ) ) {
				$appointments->options['members_no_payment']	= isset( $_POST['members_no_payment'] ); // not used??
				$appointments->options['members_discount']		= trim( str_replace( '%', '', $_POST['members_discount'] ) );
				$appointments->options['members']				= maybe_serialize( @$_POST["members"] );
			}

			$appointments->options['currency'] 					= $_POST['currency'];
			$appointments->options['mode'] 						= $_POST['mode'];
			$appointments->options['merchant_email'] 			= trim( $_POST['merchant_email'] );
			$appointments->options['return'] 					= $_POST['return'];
			$appointments->options['allow_free_autoconfirm'] 	= !empty($_POST['allow_free_autoconfirm']);

			$appointments->options["send_confirmation"]			= $_POST["send_confirmation"];
			$appointments->options["send_notification"]			= @$_POST["send_notification"];
			$appointments->options["confirmation_subject"]		= stripslashes_deep( $_POST["confirmation_subject"] );
			$appointments->options["confirmation_message"]		= stripslashes_deep( $_POST["confirmation_message"] );
			$appointments->options["send_reminder"]				= $_POST["send_reminder"];
			$appointments->options["reminder_time"]				= str_replace( " ", "", $_POST["reminder_time"] );
			$appointments->options["send_reminder_worker"]		= $_POST["send_reminder_worker"];
			$appointments->options["reminder_time_worker"]		= str_replace( " ", "", $_POST["reminder_time_worker"] );
			$appointments->options["reminder_subject"]			= stripslashes_deep( $_POST["reminder_subject"] );
			$appointments->options["reminder_message"]			= stripslashes_deep( $_POST["reminder_message"] );

			$appointments->options["send_removal_notification"] = $_POST["send_removal_notification"];
			$appointments->options["removal_notification_subject"] = stripslashes_deep( $_POST["removal_notification_subject"] );
			$appointments->options["removal_notification_message"] = stripslashes_deep( $_POST["removal_notification_message"] );

			$appointments->options["log_emails"]				= $_POST["log_emails"];

			$appointments->options['use_cache'] 				= $_POST['use_cache'];
			$appointments->options['disable_js_check_admin']	= isset( $_POST['disable_js_check_admin'] );
			$appointments->options['disable_js_check_frontend']	= isset( $_POST['disable_js_check_frontend'] );

			$appointments->options['use_mp']	 				= isset( $_POST['use_mp'] );
			$appointments->options["app_page_type_mp"]			= @$_POST["app_page_type_mp"];

			$appointments->options['allow_cancel'] 				= @$_POST['allow_cancel'];
			$appointments->options['cancel_page'] 				= @$_POST['cancel_page'];

			$appointments->options["records_per_page"]			= (int)trim( @$_POST["records_per_page"] );

			$appointments->options = apply_filters('app-options-before_save', $appointments->options);

			$saved = false;
			if ( update_option( 'appointments_options', $appointments->options ) ) {
				$saved = true;
				if ( 'yes' == $appointments->options['use_cache'] )
					add_action( 'admin_notices', array ( &$appointments, 'saved_cleared' ) );
				else
					add_action( 'admin_notices', array ( &$appointments, 'saved' ) );
			}

			// Flush cache
			if ( isset( $_POST["force_flush"] ) || $saved ) {
				$appointments->flush_cache();
				appointments_delete_timetables_cache();
				if ( isset( $_POST["force_flush"] ) )
					add_action( 'admin_notices', array ( &$appointments, 'cleared' ) );
			}

			if (isset($_POST['make_an_appointment']) || isset($_POST['make_an_appointment_product'])) {
				$this->_create_pages();
			}

			// Redirecting when saving options
			if ($saved) {
				wp_redirect(add_query_arg('saved', 1));
				die;
			}
		}

		$result = $updated = $inserted = false;
		// Save Working Hours
		if ( isset($_POST["action_app"]) && 'save_working_hours' == $_POST["action_app"] ) {
			$location = (int)$_POST['location'];
			foreach ( array( 'closed', 'open' ) as $stat ) {
				$query = $wpdb->prepare(
					"SELECT COUNT(*) FROM {$appointments->wh_table} WHERE location=%d AND worker=%d AND status=%s",
					$location, $appointments->worker, $stat
				);

				$count = $wpdb->get_var($query);

				if ( $count > 0 ) {
					$r = $wpdb->update( $appointments->wh_table,
						array( 'hours'=>serialize($_POST[$stat]), 'status'=>$stat ),
						array( 'location'=>$location, 'worker'=>$appointments->worker, 'status'=>$stat ),
						array( '%s', '%s' ),
						array( '%d', '%d', '%s' )
					);
					if ( $r )
						$result = true;
				}
				else {
					$r = $wpdb->insert( $appointments->wh_table,
						array( 'location'=>$location, 'worker'=>$appointments->worker, 'hours'=>serialize($_POST[$stat]), 'status'=>$stat ),
						array( '%d', '%d', '%s', '%s' )
					);
					if ( $r )
						$result = true;

				}
				if ( $result )
					add_action( 'admin_notices', array ( &$appointments, 'saved' ) );

				appointments_delete_work_breaks_cache( $location, $appointments->worker );
				appointments_delete_timetables_cache();
			}
		}
		// Save Exceptions
		if ( isset($_POST["action_app"]) && 'save_exceptions' == $_POST["action_app"] ) {
			$location = (int)$_POST['location'];
			foreach ( array( 'closed', 'open' ) as $stat ) {
				$count = $wpdb->get_var($wpdb->prepare(
					"SELECT COUNT(*) FROM {$appointments->exceptions_table} WHERE location=%d AND worker=%d AND status=%s",
					$location, $appointments->worker, $stat
				));

				if ( $count > 0 ) {
					$r = $wpdb->update( $appointments->exceptions_table,
						array(
							'days'		=> $this->_sort( $_POST[$stat]["exceptional_days"] ),
							'status'	=> $stat
						),
						array(
							'location'	=> $location,
							'worker'	=> $appointments->worker,
							'status'	=> $stat ),
						array( '%s', '%s' ),
						array( '%d', '%d', '%s' )
					);
					if ( $r )
						$result = true;
				}
				else {
					$r = $wpdb->insert( $appointments->exceptions_table,
						array( 'location'	=> $location,
						       'worker'	=> $appointments->worker,
						       'days'		=> $this->_sort( $_POST[$stat]["exceptional_days"] ),
						       'status'	=> $stat
						),
						array( '%d', '%d', '%s', '%s' )
					);
					if ( $r )
						$result = true;
				}
				if ( $result )
					add_action( 'admin_notices', array ( &$appointments, 'saved' ) );

				appointments_delete_exceptions_cache( $location, $appointments->worker );
			}
		}
		// Save Services
		if ( isset($_POST["action_app"]) && 'save_services' == $_POST["action_app"] && is_array( $_POST["services"] ) ) {
			do_action('app-services-before_save');
			foreach ( $_POST["services"] as $ID=>$service ) {
				if ( '' != trim( $service["name"] ) ) {
					// Update or insert?
					$_service = appointments_get_service( $ID );
					if ( $_service ) {
						$args = array(
							'name'		=> $service["name"],
							'capacity'	=> (int)$service["capacity"],
							'duration'	=> $service["duration"],
							'price'		=> $service["price"],
							'page'		=> $service["page"]
						);

						$result = appointments_update_service( $ID, $args );
					}
					else {
						$args = array(
							'ID'		=> $ID,
							'name'		=> $service["name"],
							'capacity'	=> (int)$service["capacity"],
							'duration'	=> $service["duration"],
							'price'		=> $service["price"],
							'page'		=> $service["page"]
						);
						$result = appointments_insert_service( $args );
					}

					do_action('app-services-service-updated', $ID);
				}
				else {
					// Entering an empty name means deleting of a service
					$r = appointments_delete_service( $ID );
					if ( $r )
						$result = true;
				}
			}
			if( $result )
				add_action( 'admin_notices', array ( &$appointments, 'saved' ) );

		}
		// Save Workers
		if ( isset($_POST["action_app"]) && 'save_workers' == $_POST["action_app"] && is_array( $_POST["workers"] ) ) {
			foreach ( $_POST["workers"] as $worker_id => $worker ) {
				$new_worker_id = absint( $worker["user"] );
 				$worker_id = absint( $worker_id );
				$inserted = false;
				$updated = false;
				$result = false;

				$worker_exists = appointments_get_worker( $worker_id );

				if ( $worker_exists ) {
					// Update
					if ( ( $new_worker_id != $worker_id ) && ! empty ( $worker["services_provided"] ) ) {
						// We are trying to chage the user ID
						$count = appointments_get_worker( $new_worker_id );

						// If the new ID already exist, do nothing
						if ( ! $count ) {
							// Otherwise, change the ID
							$args = array(
									'ID' => $new_worker_id,
									'price' => $worker["price"],
									'services_provided' => $worker["services_provided"],
									'dummy' => isset( $worker["dummy"] ),
									'page' => $worker['page']
							);
							$updated = appointments_update_worker( $worker_id, $args );
						}
					}
					elseif ( ( $new_worker_id == $worker_id ) && ! empty ( $worker["services_provided"] ) ) {
						// Do not change user ID but update
						$args = array(
								'price' => $worker["price"],
								'services_provided' => $worker["services_provided"],
								'dummy' => isset( $worker["dummy"] ),
								'page' => $worker['page']
						);
						$updated = appointments_update_worker( $worker_id, $args );
					}
					elseif ( empty( $worker["services_provided"] ) ) {
						$r = appointments_delete_worker( $worker_id );
						if ( $r )
							$result = true;
					}
				}
				elseif ( ! $worker_exists && ! empty( $worker["services_provided"] ) ) {
					// Insert
					$args = array(
						'ID'				=> $worker["user"],
						'price'				=> $worker["price"],
						'services_provided'	=> $worker["services_provided"],
						'page'				=> $worker["page"],
						'dummy'				=> isset ( $worker["dummy"] )
					);
					$inserted = appointments_insert_worker( $args );

					if ( $inserted )
						do_action( 'app-workers-worker-updated', $worker_id );
				}

			}
			if( $result || $updated || $inserted )
				add_action( 'admin_notices', array ( &$appointments, 'saved' ) );
		}

		// Delete removed app records
		if ( isset($_POST["delete_removed"]) && 'delete_removed' == $_POST["delete_removed"]
		     && isset( $_POST["app"] ) && is_array( $_POST["app"] ) ) {
			$result = 0;
			foreach ( $_POST["app"] as $app_id ) {
				$result = $result + appointments_delete_appointment( $app_id );
			}

			if ( $result ) {
				global $current_user;
				$userdata = get_userdata( $current_user->ID );
				add_action( 'admin_notices', array ( &$appointments, 'deleted' ) );
				do_action( 'app_deleted',  $_POST["app"] );
				$appointments->log( sprintf( __('Appointment(s) with id(s):%s deleted by user:%s', 'appointments' ),  implode( ', ', $_POST["app"] ), $userdata->user_login ) );
			}
		}

		// Bulk status change
		if ( isset( $_POST["app_status_change"] ) && $_POST["app_new_status"] && isset( $_POST["app"] ) && is_array( $_POST["app"] ) ) {

			$result = 0;
			$new_status = $_POST["app_new_status"];
			foreach ( $_POST["app"] as $app_id ) {
				$result = $result + (int)appointments_update_appointment_status( absint( $app_id ), $new_status  );
			}

			if ( $result ) {
				$userdata = get_userdata( get_current_user_id() );
				add_action( 'admin_notices', array ( &$appointments, 'updated' ) );
				do_action( 'app_bulk_status_change',  $_POST["app"] );

				$appointments->log( sprintf( __('Status of Appointment(s) with id(s):%s changed to %s by user:%s', 'appointments' ),  implode( ', ', $_POST["app"] ), $new_status, $userdata->user_login ) );

				if ( is_object( $appointments->gcal_api ) ) {
					// If deleted, remove these from GCal too
					if ( 'removed' == $new_status ) {
						foreach ( $_POST["app"] as $app_id ) {
							$appointments->gcal_api->delete( $app_id );
							$appointments->send_removal_notification($app_id);
						}
					}
					// If confirmed or paid, add these to GCal
					else if (is_object($appointments->gcal_api) && $appointments->gcal_api->is_syncable_status($new_status)) {
						foreach ( $_POST["app"] as $app_id ) {
							$appointments->gcal_api->update( $app_id );
							// Also send out an email
							if (!empty($appointments->options["send_confirmation"]) && 'yes' == $appointments->options["send_confirmation"]) {
								appointments_send_confirmation( $app_id );
							}
						}
					}
				}
			}
		}

		// Determine if we shall flush cache
		if ( ( isset( $_POST["action_app"] ) ) && ( $result || $updated || $inserted ) ||
		     ( isset( $_POST["delete_removed"] ) && 'delete_removed' == $_POST["delete_removed"] ) ||
		     ( isset( $_POST["app_status_change"] ) && $_POST["app_new_status"] ) )
			// As it means any setting is saved, lets clear cache
			$appointments->flush_cache();
	}

	private function _create_pages () {
		global $appointments;
		// Add an appointment page
		if ( isset( $_POST["make_an_appointment"] ) ) {
			$tpl = !empty($_POST['app_page_type']) ? $_POST['app_page_type'] : false;
			wp_insert_post(
					array(
							'post_title'	=> 'Make an Appointment',
							'post_status'	=> 'publish',
							'post_type'		=> 'page',
							'post_content'	=> App_Template::get_default_page_template($tpl)
					)
			);
		}

		// Add an appointment product page
		if ( isset( $_POST["make_an_appointment_product"] ) && $appointments->marketpress_active ) {
			$tpl = !empty($_POST['app_page_type_mp']) ? $_POST['app_page_type_mp'] : false;
			$post_id = wp_insert_post(
					array(
							'post_title'	=> 'Appointment',
							'post_status'	=> 'publish',
							'post_type'		=> 'product',
							'post_content'	=> App_Template::get_default_page_template($tpl)
					)
			);
			if ( $post_id ) {
				// Add a download link, so that app will be a digital product
				$file = get_post_meta($post_id, 'mp_file', true);
				if ( !$file ) add_post_meta( $post_id, 'mp_file', get_permalink( $post_id) );

				// MP requires at least 2 variations, so we add a dummy one
				add_post_meta( $post_id, 'mp_var_name', array( 0 ) );
				add_post_meta( $post_id, 'mp_sku', array( 0 ) );
				add_post_meta( $post_id, 'mp_price', array( 0 ) );
			}
		}
	}

	/**
	 *	Sorts a comma delimited string
	 *	@since 1.2
	 */
	function _sort( $input ) {
		if ( strpos( $input, ',') === false )
			return $input;
		$temp = explode( ',', $input );
		sort( $temp );
		return implode( ',', $temp );
	}

	/**
	 *	Packs an array into a string with : as glue
	 */
	function _implode( $input ) {
		if ( !is_array( $input ) || empty( $input ) )
			return false;
		return ':'. implode( ':', array_filter( $input ) ) . ':';
	}

	/**
	 * Admin settings HTML code
	 */
	function settings() {
		global $appointments;
		if (!App_Roles::current_user_can('manage_options', App_Roles::CTX_PAGE_SETTINGS)) {
			wp_die( __('You do not have sufficient permissions to access this page.','appointments') );
		}
		$appointments->get_lsw();
		global $wpdb;
		?>
		<div class="wrap">
			<div class="icon32" style="margin:10px 0 0 0"><img src="<?php echo $appointments->plugin_url . '/images/general.png'; ?>" /></div>
			<h2><?php echo __('Appointments+ Settings','appointments'); ?></h2>
			<h3 class="nav-tab-wrapper">
				<?php
				$tab = ( !empty($_GET['tab']) ) ? $_GET['tab'] : 'main';

				$tabs = array(
						'gcal'			=> __('Google Calendar', 'appointments'),
						'working_hours'	=> __('Working Hours', 'appointments'),
						'exceptions'	=> __('Exceptions', 'appointments'),
						'services'      => __('Services', 'appointments'),
						'workers' 	    => __('Service Providers', 'appointments'),
					//'shortcodes'    => __('Shortcodes', 'appointments'),
						'addons'		=> __('Add-ons', 'appointments'),
						'log'    		=> __('Logs', 'appointments'),
					//'faq'    		=> __('FAQ', 'appointments'),
				);

				$tabhtml = array();

				// If someone wants to remove or add a tab
				$tabs = apply_filters( 'appointments_tabs', $tabs );

				$class = ( 'main' == $tab ) ? ' nav-tab-active' : '';
				$tabhtml[] = '	<a href="' . admin_url( 'admin.php?page=app_settings' ) . '" class="nav-tab'.$class.'">' . __('General', 'appointments') . '</a>';

				foreach ( $tabs as $stub => $title ) {
					$class = ( $stub == $tab ) ? ' nav-tab-active' : '';
					$tabhtml[] = '	<a href="' . admin_url( 'admin.php?page=app_settings&amp;tab=' . $stub ) . '" class="nav-tab'.$class.'" id="app_tab_'.$stub.'">'.$title.'</a>';
				}

				echo implode( "\n", $tabhtml );
				?>
			</h3>
			<div class="clear"></div>
			<?php App_Template::admin_settings_tab($tab); ?>
		</div>
		<?php
	}

}
<?php


class Appointmets_AJAX {

	public $options = array();

	public function __construct() {
		add_action( 'wp_ajax_nopriv_app_paypal_ipn', array(&$this, 'handle_paypal_return')); // Send Paypal to IPN function

		add_action( 'wp_ajax_delete_log', array( &$this, 'delete_log' ) ); 				// Clear log
		add_action( 'wp_ajax_inline_edit', array( &$this, 'inline_edit' ) ); 			// Add/edit appointments
		add_action( 'wp_ajax_inline_edit_save', array( &$this, 'inline_edit_save' ) ); 	// Save edits
		add_action( 'wp_ajax_js_error', array( &$this, 'js_error' ) ); 					// Track js errors
		add_action( 'wp_ajax_app_export', array( &$this, 'export' ) ); 					// Export apps

		// Front end ajax hooks
		add_action( 'wp_ajax_pre_confirmation', array( &$this, 'pre_confirmation' ) ); 			// Get pre_confirmation results
		add_action( 'wp_ajax_nopriv_pre_confirmation', array( &$this, 'pre_confirmation' ) ); 	// Get pre_confirmation results
		add_action( 'wp_ajax_post_confirmation', array( &$this, 'post_confirmation' ) ); 		// Do after final confirmation
		add_action( 'wp_ajax_nopriv_post_confirmation', array( &$this, 'post_confirmation' ) ); // Do after final confirmation

		add_action( 'wp_ajax_cancel_app', array( &$this, 'cancel' ) ); 							// Cancel appointment from my appointments
		add_action( 'wp_ajax_nopriv_cancel_app', array( &$this, 'cancel' ) );
	}

	/**
	 * Track javascript errors
	 * @since 1.0.3
	 */
	function js_error() {

		// @TODO: Activate this again in future releases
//		if  ( false && isset( $_POST['url'] ) ) {
//			$this->error_url = $_POST['url'];
//			$appointments->log( __('Javascript error on : ', 'appointments') . $this->error_url );
//			die( json_encode( array( 'message'	=> '<div class="error"><p>' .
//			                                         sprintf( __('<b>[Appointments+]</b> You have at least one javascript error on %s.<br />Error message: %s<br />File: %s<br />Line: %s', 'appointments'), $this->error_url, @$_POST['errorMessage'], @$_POST['file'], @$_POST['lineNumber']) .
//			                                         '</p></div>')
//			)
//			);
//		}
		die();
	}

	function inline_edit_save() {
		global $appointments;

		$app_id = $_POST["app_id"];
		$email_sent = false;
		global $wpdb, $current_user;
		$app = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$appointments->app_table} WHERE ID=%d", $app_id));
		$data = array();

		if ($app != null) {
			$data['ID'] = $app_id;
		} else {
			$data['created'] = date("Y-m-d H:i:s", $appointments->local_time );
			$data['ID'] = 'NULL';
		}

		$data['user'] = $_POST['user'];
		$data['email'] = !empty($_POST['email']) && is_email($_POST['email']) ? $_POST['email'] : '';
		$data['name'] = $_POST['name'];
		$data['phone'] = $_POST['phone'];
		$data['address'] = $_POST['address'];
		$data['city'] = $_POST['city'];
		$data['service'] = $_POST['service'];
		$service = $appointments->get_service( $_POST['service'] );
		$data['worker'] = $_POST['worker'];
		$data['price'] = $_POST['price'];
		// Clear comma from date format. It creates problems for php5.2
		$data['start']	= date(
			'Y-m-d H:i:s',
			strtotime(
				str_replace(',', '', $appointments->to_us($_POST['date'])) .
				" " .
				date('H:i', strtotime($_POST['time']))
			)
		);
		$data['end'] = date(
			'Y-m-d H:i:s',
			strtotime(
				str_replace(',', '', $appointments->to_us($_POST['date'])) .
				" " .
				date('H:i', strtotime($_POST['time']))
			)
			+ ($service->duration * 60)
		);
		$data['note'] = $_POST['note'];
		$data['status'] = $_POST['status'];
		$resend = $_POST["resend"];

		$data = apply_filters('app-appointment-inline_edit-save_data', $data);

		$update_result = $insert_result = false;
		if ($app != null) {
			// Update
			$update_result = $wpdb->update( $appointments->app_table, $data, array('ID' => $app_id) );
			if ( $update_result ) {
				if ( ( 'pending' == $data['status'] || 'removed' == $data['status'] || 'completed' == $data['status'] ) && is_object( $appointments->gcal_api ) ) {
					$appointments->gcal_api->delete( $app_id );
				} else if (is_object($appointments->gcal_api) && $appointments->gcal_api->is_syncable_status($data['status'])) {
					$appointments->gcal_api->update( $app_id ); // This also checks for event insert
				}
				if ('removed' === $data['status']) $appointments->send_removal_notification($app_id);
			}
			if ($update_result && $resend) {
				if ('removed' == $data['status']) do_action( 'app_removed', $app_id );
				//else $this->send_confirmation( $app_id );
			}
		} else {
			// Insert
			$insert_result = $wpdb->insert( $appointments->app_table, $data );
			/*
// Moved
			if ( $insert_result && $resend && empty($email_sent) ) {
				$email_sent = $this->send_confirmation( $wpdb->insert_id );
			}
			if ( $insert_result && is_object($this->gcal_api) && $this->gcal_api->is_syncable_status($data['status'])) {
				$this->gcal_api->insert( $wpdb->insert_id );
			}
			*/
		}

		do_action('app-appointment-inline_edit-after_save', ($update_result ? $app_id : $wpdb->insert_id), $data);
		/*
		// Moved
				if ($resend && 'removed' != $data['status'] && empty($email_sent) ) {
					$email_sent = $this->send_confirmation( $app_id );
				}
		*/
		if ( ( $update_result || $insert_result ) && $data['user'] && defined('APP_USE_LEGACY_USERDATA_OVERWRITING') && APP_USE_LEGACY_USERDATA_OVERWRITING ) {
			if ( $data['name'] )
				update_user_meta( $data['user'], 'app_name',  $data['name'] );
			if (  $data['email'] )
				update_user_meta( $data['user'], 'app_email', $data['email'] );
			if ( $data['phone'] )
				update_user_meta( $data['user'], 'app_phone', $data['phone'] );
			if ( $data['address'] )
				update_user_meta( $data['user'], 'app_address', $data['address'] );
			if ( $data['city'] )
				update_user_meta( $data['user'], 'app_city', $data['city'] );

			do_action( 'app_save_user_meta', $data['user'], $data );
		}

		do_action('app-appointment-inline_edit-before_response', ($update_result ? $app_id : $wpdb->insert_id), $data);

		// Move mail sending here so the fields can expand
		if ( $insert_result && $resend && empty($email_sent) ) {
			$email_sent = $appointments->send_confirmation( $wpdb->insert_id );
		}
		if ( $insert_result && is_object($appointments->gcal_api) && $appointments->gcal_api->is_syncable_status($data['status'])) {
			$appointments->gcal_api->insert( $wpdb->insert_id );
		}
		if ($resend && !empty($app_id) && 'removed' != $data['status'] && empty($email_sent) ) {
			$email_sent = $appointments->send_confirmation( $app_id );
		}

		$result = array(
			'app_id' => 0,
			'message' => '',
		);
		if ( $update_result ) {
			// Log change of status
			if ( $data['status'] != $app->status ) {
				$appointments->log( sprintf( __('Status changed from %s to %s by %s for appointment ID:%d','appointments'), $app->status, $data["status"], $current_user->user_login, $app->ID ) );
			}
			$result = array(
				'app_id' => $app->ID,
				'message' => __('<span style="color:green;font-weight:bold">Changes saved.</span>', 'appointments'),
			);
		} else if ( $insert_result ) {
			$result = array(
				'app_id' => $wpdb->insert_id,
				'message' => __('<span style="color:green;font-weight:bold">Changes saved.</span>', 'appointments'),
			);
		} else {
			$message = $resend && !empty($data['status']) && 'removed' != $data['status']
				? sprintf('<span style="color:green;font-weight:bold">%s</span>', __('Confirmation message (re)sent', 'appointments'))
				: sprintf('<span style="color:red;font-weight:bold">%s</span>', __('Record could not be saved OR you did not make any changes!', 'appointments'))
			;
			$result = array(
				'app_id' => ($update_result ? $app_id : $wpdb->insert_id),
				'message' => $message,
			);
		}

		$result = apply_filters('app-appointment-inline_edit-result', $result, ($update_result ? $app_id : $wpdb->insert_id), $data);
		die(json_encode($result));
	}

	// Edit or create appointments
	function inline_edit() {
		global $appointments;
		$safe_date_format = $appointments->safe_date_format();
		// Make a locale check to update locale_error flag
		$date_check = $appointments->to_us( date_i18n( $safe_date_format, strtotime('today') ) );

		global $wpdb;
		$app_id = $_POST["app_id"];
		$end_datetime = '';
		if ( $app_id ) {
			$app = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$appointments->app_table} WHERE ID=%d", $app_id) );
			$start_date_timestamp = date("Y-m-d", strtotime($app->start));
			if ( $appointments->locale_error ) {
				$start_date = date( $safe_date_format, strtotime( $app->start ) );
			} else {
				$start_date = date_i18n( $safe_date_format, strtotime( $app->start ) );
			}

			$start_time = date_i18n( $appointments->time_format, strtotime( $app->start ) );
			$end_datetime = date_i18n( $appointments->datetime_format, strtotime( $app->end ) );
			// Is this a registered user?
			if ( $app->user ) {
				$name = get_user_meta( $app->user, 'app_name', true );
				if ( $name )
					$app->name = $app->name && !(defined('APP_USE_LEGACY_ADMIN_USERDATA_OVERRIDES') && APP_USE_LEGACY_ADMIN_USERDATA_OVERRIDES) ? $app->name : $name;

				$email = get_user_meta( $app->user, 'app_email', true );
				if ( $email )
					$app->email = $app->email && !(defined('APP_USE_LEGACY_ADMIN_USERDATA_OVERRIDES') && APP_USE_LEGACY_ADMIN_USERDATA_OVERRIDES) ? $app->email : $email;

				$phone = get_user_meta( $app->user, 'app_phone', true );
				if ( $phone )
					$app->phone = $app->phone && !(defined('APP_USE_LEGACY_ADMIN_USERDATA_OVERRIDES') && APP_USE_LEGACY_ADMIN_USERDATA_OVERRIDES) ? $app->phone : $phone;

				$address = get_user_meta( $app->user, 'app_address', true );
				if ( $address )
					$app->address = $app->address && !(defined('APP_USE_LEGACY_ADMIN_USERDATA_OVERRIDES') && APP_USE_LEGACY_ADMIN_USERDATA_OVERRIDES) ? $app->address : $address;

				$city = get_user_meta( $app->user, 'app_city', true );
				if ( $city )
					$app->city = $app->city && !(defined('APP_USE_LEGACY_ADMIN_USERDATA_OVERRIDES') && APP_USE_LEGACY_ADMIN_USERDATA_OVERRIDES) ? $app->city : $city;
			}
		} else {
			$app = new stdClass(); // This means insert a new app object
			/*
			//DO NOT DO THIS!!!!
			//This is just begging for a race condition issue >.<
						// Get maximum ID
						$app_max = $wpdb->get_var( "SELECT MAX(ID) FROM " . $this->app_table . " " );
						// Check if nothing has saved yet
						if ( !$app_max )
							$app_max = 0;
						$app->ID = $app_max + 1 ; // We want to create a new record
			*/
			$app->ID = 0;
			// Set other fields to default so that we don't get notice messages
			$app->user = $app->location = $app->worker = 0;
			$app->created = $app->end = $app->name = $app->email = $app->phone = $app->address = $app->city = $app->status = $app->sent = $app->sent_worker = $app->note = '';

			// Get first service and its price
			$app->service = $appointments->get_first_service_id();
			$_REQUEST['app_service_id'] = $app->service;
			$_REQUEST['app_provider_id'] = 0;
			$app->price = $appointments->get_price( );

			// Select time as next 1 hour
			$start_time = date_i18n( $appointments->time_format, intval(($appointments->local_time + 60*$appointments->get_min_time())/3600)*3600 );

			$start_date_timestamp = date("Y-m-d", $appointments->local_time + 60*$appointments->get_min_time());
			// Set start date as now + 60 minutes.
			if ( $appointments->locale_error ) {
				$start_date = date( $safe_date_format, $appointments->local_time + 60*$appointments->get_min_time() );
			}
			else {
				$start_date = date_i18n( $safe_date_format, $appointments->local_time + 60*$appointments->get_min_time() );
			}
		}

		$html = '';
		$html .= '<tr class="inline-edit-row inline-edit-row-post quick-edit-row-post">';

		$html .= isset($_POST['col_len']) && is_numeric($_POST['col_len'])
			? '<td colspan="' . (int)$_POST["col_len"] . '" class="colspanchange">'
			: '<td colspan="6" class="colspanchange">'
		;

		$html .= '<fieldset class="inline-edit-col-left" style="width:33%">';
		$html .= '<div class="inline-edit-col">';
		$html .= '<h4>'.__('CLIENT', 'appointments').'</h4>';
		/* user */
		$html .= '<label>';
		$html .= '<span class="title">'.__('User', 'appointments'). '</span>';
		$html .= wp_dropdown_users( array( 'show_option_all'=>__('Not registered user','appointments'), 'show'=>'user_login', 'echo'=>0, 'selected' => $app->user, 'name'=>'user' ) );
		$html .= '</label>';
		/* Client name */
		$html .= '<label>';
		$html .= '<span class="title">'.$appointments->get_field_name('name'). '</span>';
		$html .= '<span class="input-text-wrap">';
		$html .= '<input type="text" name="cname" class="ptitle" value="' . esc_attr(stripslashes($app->name)) . '" />';
		$html .= '</span>';
		$html .= '</label>';
		/* Client email */
		$html .= '<label>';
		$html .= '<span class="title">'.$appointments->get_field_name('email'). '</span>';
		$html .= '<span class="input-text-wrap">';
		$html .= '<input type="text" name="email" class="ptitle" value="' . esc_attr($app->email) . '" />';
		$html .= '</span>';
		$html .= '</label>';
		/* Client Phone */
		$html .= '<label>';
		$html .= '<span class="title">'.$appointments->get_field_name('phone'). '</span>';
		$html .= '<span class="input-text-wrap">';
		$html .= '<input type="text" name="phone" class="ptitle" value="' . esc_attr(stripslashes($app->phone)) . '" />';
		$html .= '</span>';
		$html .= '</label>';
		/* Client Address */
		$html .= '<label>';
		$html .= '<span class="title">'.$appointments->get_field_name('address'). '</span>';
		$html .= '<span class="input-text-wrap">';
		$html .= '<input type="text" name="address" class="ptitle" value="' . esc_attr(stripslashes($app->address)) . '" />';
		$html .= '</span>';
		$html .= '</label>';
		/* Client City */
		$html .= '<label>';
		$html .= '<span class="title">'.$appointments->get_field_name('city'). '</span>';
		$html .= '<span class="input-text-wrap">';
		$html .= '<input type="text" name="city" class="ptitle" value="' . esc_attr(stripslashes($app->city)) . '" />';
		$html .= '</span>';
		$html .= '</label>';
		$html .= apply_filters('app-appointments_list-edit-client', '', $app);
		$html .= '</div>';
		$html .= '</fieldset>';

		$html .= '<fieldset class="inline-edit-col-center" style="width:28%">';
		$html .= '<div class="inline-edit-col">';
		$html .= '<h4>'.__('SERVICE', 'appointments').'</h4>';
		/* Services */
		$services = $appointments->get_services();
		$html .= '<label>';
		$html .= '<span class="title">'.__('Name', 'appointments'). '</span>';
		$html .= '<select name="service">';
		if ( $services ) {
			foreach ( $services as $service ) {
				if ( $app->service == $service->ID )
					$sel = ' selected="selected"';
				else
					$sel = '';
				$html .= '<option value="' . esc_attr($service->ID) . '"'.$sel.'>'. stripslashes( $service->name ) . '</option>';
			}
		}
		$html .= '</select>';
		$html .= '</label>';
		/* Workers */
		$workers = $wpdb->get_results("SELECT * FROM " . $appointments->workers_table . " " );
		$html .= '<label>';
		$html .= '<span class="title">'.__('Provider', 'appointments'). '</span>';
		$html .= '<select name="worker">';
		// Always add an "Our staff" field
		$html .= '<option value="0">'. __('No specific provider', 'appointments') . '</option>';
		if ( $workers ) {
			foreach ( $workers as $worker ) {
				if ( $app->worker == $worker->ID ) {
					$sel = ' selected="selected"';
				}
				else
					$sel = '';
				$html .= '<option value="' . esc_attr($worker->ID) . '"'.$sel.'>'. $appointments->get_worker_name( $worker->ID, false ) . '</option>';
			}
		}
		$html .= '</select>';
		$html .= '</label>';
		/* Price */
		$html .= '<label>';
		$html .= '<span class="title">'.__('Price', 'appointments'). '</span>';
		$html .= '<span class="input-text-wrap">';
		$html .= '<input type="text" name="price" style="width:50%" class="ptitle" value="' . esc_attr($app->price) . '" />';
		$html .= '</span>';
		$html .= '</label>';
		$html .= '</label>';
		$html .= apply_filters('app-appointments_list-edit-services', '', $app);
		$html .= '</div>';
		$html .= '</fieldset>';

		$html .= '<fieldset class="inline-edit-col-right" style="width:38%">';
		$html .= '<div class="inline-edit-col">';
		$html .= '<h4>'.__('APPOINTMENT', 'appointments').'</h4>';
		/* Created - Don't show for a new app */
		if ( $app_id ) {
			$html .= '<label>';
			$html .= '<span class="title">'.__('Created', 'appointments'). '</span>';
			$html .= '<span class="input-text-wrap" style="height:26px;padding-top:4px;">';
			$html .= date_i18n( $appointments->datetime_format, strtotime($app->created) );
			$html .= '</span>';
			$html .= '</label>';
		}
		/* Start */
		$html .= '<label style="float:left;width:65%">';
		$html .= '<span class="title">'.__('Start', 'appointments'). '</span>';
		$html .= '<span class="input-text-wrap" >';
		$html .= '<input type="text" name="date" class="datepicker" size="12" value="' . esc_attr($start_date) . '" data-timestamp="' . esc_attr($start_date_timestamp) . '"  />';
		$html .= '</label>';
		$html .= '<label style="float:left;width:30%; padding-left:5px;">';

		// Check if an admin min time (time base) is set. @since 1.0.2
		if ( isset( $this->options["admin_min_time"] ) && $this->options["admin_min_time"] )
			$min_time = $this->options["admin_min_time"];
		else
			$min_time = $appointments->get_min_time();

		$min_secs = 60 * apply_filters( 'app_admin_min_time', $min_time );
		$html .= '<select name="time" >';
		for ( $t=0; $t<3600*24; $t=$t+$min_secs ) {
			$s = array();
			$dhours = $appointments->secs2hours( $t ); // Hours in 08:30 format

			$s[] = $dhours == $start_time
				? 'selected="selected"'
				: ''
			;
			$s[] = 'value="' . esc_attr($appointments->secs2hours($t, 'H:i')) . '"';

			$html .= '<option ' . join(' ', array_values(array_filter($s))) . '>';
			$html .= $dhours;
			$html .= '</option>';
		}
		$html .= '</select>';
		$html .= '</span>';
		$html .= '</label>';
		$html .= '<div style="clear:both; height:0"></div>';
		/* End - Don't show for a new app */
		if ( $app_id ) {
			$html .= '<label style="margin-top:8px">';
			$html .= '<span class="title">'.__('End', 'appointments'). '</span>';
			$html .= '<span class="input-text-wrap" style="height:26px;padding-top:4px;">';
			$html .= $end_datetime;
			$html .= '</span>';
			$html .= '</label>';
		}
		/* Note */
		$html .= '<label>';
		$html .= '<span class="title">'.$appointments->get_field_name('note'). '</span>';
		$html .= '<textarea cols="22" rows=1">';
		$html .= esc_textarea(stripslashes($app->note));
		$html .= '</textarea>';
		$html .= '</label>';
		/* Status */
		//$statuses = $this->get_statuses();
		$statuses = App_Template::get_status_names();
		$html .= '<label>';
		$html .= '<span class="title">'.__('Status', 'appointments'). '</span>';
		$html .= '<span class="input-text-wrap">';
		$html .= '<select name="status">';
		if ( $statuses ) {
			foreach ( $statuses as $status => $status_name ) {
				if ( $app->status == $status )
					$sel = ' selected="selected"';
				else
					$sel = '';
				$html .= '<option value="'.$status.'"'.$sel.'>'. $status_name . '</option>';
			}
		}
		$html .= '</select>';
		$html .= '</span>';
		$html .= '</label>';
		/* Confirmation email */
		// Default is "checked" for a new appointment
		if ( $app_id ) {
			$c = '';
			$text = __('(Re)send confirmation email', 'appointments');
		}
		else {
			$c = ' checked="checked"';
			$text = __('Send confirmation email', 'appointments');
		}

		$html .= '<label>';
		$html .= '<span class="title">'.__('Confirm','appointments').'</span>';
		$html .= '<span class="input-text-wrap">';
		$html .= '<input type="checkbox" name="resend" value="1" '.$c.' />&nbsp;' .$text;
		$html .= '</span>';
		$html .= '</label>';

		$html .= '</div>';
		$html .= '</fieldset>';
		/* General fields required for save and cancel */
		$html .= '<p class="submit inline-edit-save">';
		$html .= '<a href="javascript:void(0)" title="'._x('Cancel', 'Drop current action', 'appointments').'" class="button-secondary cancel alignleft">'._x('Cancel', 'Drop current action', 'appointments').'</a>';
		if ( 'reserved' == $app->status ) {
			$js = 'style="display:none"';
			$title = __('GCal reserved appointments cannot be edited here. Edit them in your Google calendar.', 'appointments');
		}
		else {
			$js = 'href="javascript:void(0)"';
			$title = __('Click to save or update', 'appointments');
		}
		$html .= '<a '.$js.' title="' . esc_attr($title) . '" class="button-primary save alignright">'.__('Save / Update','appointments').'</a>';
		$html .= '<img class="waiting" style="display:none;" src="'.admin_url('images/wpspin_light.gif').'" alt="">';
		$html .= '<input type="hidden" name="app_id" value="' . esc_attr($app->ID) . '">';
		$html .= '<span class="error" style="display:none"></span>';
		$html .= '<br class="clear">';
		$html .= '</p>';

		$html .= '</td>';
		$html .= '</tr>';

		die( json_encode( array( 'result'=>$html)));

	}

	function delete_log(){
		global $appointments;
		// check_ajax_referer( );
		if ( !unlink( $appointments->log_file ) )
			die( json_encode( array('error' => esc_js( __('Log file could not be deleted','appointments')))));
		die();
	}


	/**
	 * Handle cancellation of an appointment by the client
	 * @since 1.2.6
	 */
	function cancel() {
		global $appointments;

		if ( isset( $this->options['allow_cancel'] ) && 'yes' == $this->options['allow_cancel'] ) {

			/* Cancel by the link in email */
			// We don't want to break any other plugin's init, so these conditions are very strict
			if ( isset( $_GET['app_cancel'] ) && isset( $_GET['app_id'] ) && isset( $_GET['app_nonce'] ) ) {
				$app_id = $_GET['app_id'];
				$app = $appointments->get_app( $app_id );

				if( isset( $app->status ) )
					$stat = $app->status;
				else
					$stat = '';

				// Addons may want to add or omit some stats, but as default we don't want completed appointments to be cancelled
				$in_allowed_stat = apply_filters( 'app_cancel_allowed_status', ('pending' == $stat || 'confirmed' == $stat || 'paid' == $stat), $stat, $app_id );

				// Also the clicked link may belong to a formerly created and deleted appointment.
				// Another irrelevant app may have been created after cancel link has been sent. So we will check creation date
				if ( $in_allowed_stat && $_GET['app_nonce'] == md5( $_GET['app_id']. $appointments->salt . strtotime( $app->created ) ) ) {
					if ( $appointments->change_status( 'removed', $app_id ) ) {
						$appointments->log( sprintf( __('Client %s cancelled appointment with ID: %s','appointments'), $appointments->get_client_name( $app_id ), $app_id ) );
						$appointments->send_notification( $app_id, true );

						if (!empty($appointments->gcal_api) && is_object($appointments->gcal_api)) $appointments->gcal_api->delete($app_id); // Drop the cancelled appointment
						else if (!defined('APP_GCAL_DISABLE')) $appointments->log("Unable to issue a remote call to delete the remote appointment.");

						do_action('app-appointments-appointment_cancelled', $app_id);
						// If there is a header warning other plugins can do whatever they need
						if ( !headers_sent() ) {
							if ( isset( $appointments->options['cancel_page'] ) &&  $appointments->options['cancel_page'] ) {
								wp_redirect( get_permalink( $appointments->options['cancel_page'] ) );
								exit;
							}
							else {
								wp_redirect( home_url() );
								exit;
							}
						}
					}
					// Gracefully go to home page if appointment has already been cancelled, or do something here
					do_action( 'app_cancel_failed', $app_id );
				}
			}

			/* Cancel from my appointments table by ajax */
			if ( isset( $_POST['app_id'] ) && isset( $_POST['cancel_nonce'] ) ) {
				$app_id = $_POST['app_id'];

				// Check if user is the real owner of this appointment to prevent malicious attempts
				$owner = false;
				// First try to find from database
				if ( is_user_logged_in() ) {
					global $current_user;
					$app = $appointments->get_app( $app_id );
					if ( $app->user && $app->user == $current_user->ID )
						$owner = true;
				}
				// Then check cookie. Check is not so strict here, as he couldn't be seeing that cancel checkbox in the first place
				if ( !$owner && isset( $_COOKIE["wpmudev_appointments"] ) ) {
					$apps = unserialize( stripslashes( $_COOKIE["wpmudev_appointments"] ) );
					if ( is_array( $apps ) && in_array( $app_id, $apps ) )
						$owner = true;
				}
				// Addons may want to do something here
				$owner = apply_filters( 'app_cancellation_owner', $owner, $app_id );

				// He is the wrong guy, or he may have cleared his cookies while he is on the page
				if ( !$owner )
					die( json_encode( array('error'=>esc_js(__('There is an issue with this appointment. Please refresh the page and try again. If problem persists, please contact website admin.','appointments') ) ) ) );

				// Now we can safely continue for cancel
				if ( $appointments->change_status( 'removed', $app_id ) ) {
					$appointments->log( sprintf( __('Client %s cancelled appointment with ID: %s','appointments'), $appointments->get_client_name( $app_id ), $app_id ) );
					$appointments->send_notification( $app_id, true );

					if (!empty($appointments->gcal_api) && is_object($appointments->gcal_api)) $appointments->gcal_api->delete($app_id); // Drop the cancelled appointment
					else if (!defined('APP_GCAL_DISABLE')) $appointments->log("Unable to issue a remote call to delete the remote appointment.");

					do_action('app-appointments-appointment_cancelled', $app_id);
					die( json_encode( array('success'=>1)));
				}
				else
					die( json_encode( array('error'=>esc_js(__('Appointment could not be cancelled. Please refresh the page and try again.','appointments') ) ) ) );
			}
		}
		else if ( isset( $_POST['app_id'] ) && isset( $_POST['cancel_nonce'] ) )
			die( json_encode( array('error'=>esc_js(__('Cancellation of appointments is disabled. Please contact website admin.','appointments') ) ) ) );
	}

	/**
	 * Make checks on submitted fields and save appointment
	 * @return json object
	 */
	function post_confirmation() {
		global $appointments;
		if (!$appointments->check_spam()) {
			die(json_encode(array(
				"error" => apply_filters(
					'app_spam_message',
					__( 'You have already applied for an appointment. Please wait until you hear from us.', 'appointments')
				),
			)));
		}

		global $wpdb, $current_user;

		$values = explode( ":", $_POST["value"] );
		$location = $values[0];
		$service = $values[1];
		$worker = $values[2];
		$start = $values[3];
		$end = $values[4];
		$post_id = $values[5];

		if (is_user_logged_in()) {
			$user_id = $current_user->ID;
			$userdata = get_userdata( $current_user->ID );
			$user_email = $userdata->email;

			$user_name = $userdata->display_name;
			if (!$user_name) {
				$first_name = get_user_meta($worker, 'first_name', true);
				$last_name = get_user_meta($worker, 'last_name', true);
				$user_name = $first_name . " " . $last_name;
			}
			if ("" == trim($user_name)) $user_name = $userdata->user_login;
		} else {
			$user_id = 0;
			$user_email = '';
			$user_name = '';
		}

		// A little trick to pass correct lsw variables to the get_price, is_busy and get_capacity functions
		$_REQUEST["app_location_id"] = $location;
		$_REQUEST["app_service_id"] = $service;
		$_REQUEST["app_provider_id"] = $worker;
		$appointments->get_lsw();

		// Default status
		$status = 'pending';
		if ('yes' != $this->options["payment_required"] && isset($this->options["auto_confirm"]) && 'yes' == $this->options["auto_confirm"]) {
			$status = 'confirmed';
		}

		// We may have 2 prices now: 1) Service full price, 2) Amount that will be paid to Paypal
		$price = $appointments->get_price();
		$price = apply_filters('app_post_confirmation_price', $price, $service, $worker, $start, $end);
		$paypal_price = $appointments->get_price(true);
		$paypal_price = apply_filters('app_post_confirmation_paypal_price', $paypal_price, $service, $worker, $start, $end);

		// Break here - is the appointment free and, if so, shall we auto-confirm?
		if (
			!(float)$price && !(float)$paypal_price // Free appointment ...
			&&
			'pending' === $status && "yes" === $this->options["payment_required"] // ... in a paid environment ...
			&&
			(!empty($this->options["auto_confirm"]) && "yes" === $this->options["auto_confirm"]) // ... with auto-confirm activated
		) {
			$status = defined('APP_CONFIRMATION_ALLOW_FREE_AUTOCONFIRM') && APP_CONFIRMATION_ALLOW_FREE_AUTOCONFIRM
				? 'confirmed'
				: $status
			;
		}

		$name = !empty($_POST['app_name'])
			? sanitize_text_field($_POST["app_name"])
			: $user_name
		;
		$name_check = apply_filters( "app_name_check", true, $name );
		if (!$name_check) $appointments->json_die( 'name' );

		$email = !empty($_POST['app_email']) && is_email($_POST['app_email'])
			? $_POST['app_email']
			: $user_email
		;
		if ($this->options["ask_email"] && !is_email($email)) $appointments->json_die( 'email' );

		$phone = !empty($_POST['app_phone'])
			? sanitize_text_field($_POST["app_phone"])
			: ''
		;
		$phone_check = apply_filters("app_phone_check", true, $phone);
		if (!$phone_check) $appointments->json_die('phone');

		$address = !empty($_POST['app_address'])
			? sanitize_text_field($_POST["app_address"])
			: ''
		;
		$address_check = apply_filters("app_address_check", true, $address);
		if (!$address_check) $appointments->json_die('address');

		$city = !empty($_POST['app_city'])
			? sanitize_text_field($_POST["app_city"])
			: ''
		;
		$city_check = apply_filters("app_city_check", true, $city);
		if (!$city_check) $appointments->json_die( 'city' );

		$note = !empty($_POST['app_note'])
			? sanitize_text_field($_POST["app_note"])
			: ''
		;

		$gcal = !empty($_POST['app_gcal'])
			? $_POST['app_gcal']
			: ''
		;

		do_action('app-additional_fields-validate');

		// It may be required to add additional data here
		$note = apply_filters('app_note_field', $note);

		$service_result = $appointments->get_service($service);

		$duration = false;
		if ($service_result !== null) $duration = $service_result->duration;
		if (!$duration) $duration = $appointments->get_min_time(); // In minutes

		$duration = apply_filters( 'app_post_confirmation_duration', $duration, $service, $worker, $user_id );

		if ($appointments->is_busy($start,  $start + ($duration * 60), $appointments->get_capacity())) {
			die(json_encode(array(
				"error" => apply_filters(
					'app_booked_message',
					__('We are sorry, but this time slot is no longer available. Please refresh the page and try another time slot. Thank you.', 'appointments')
				),
			)));
		}

		$status = apply_filters('app_post_confirmation_status', $status, $price, $service, $worker, $user_id);

		$result = $wpdb->insert(
			$appointments->app_table,
			array(
				'created'	=>	date ("Y-m-d H:i:s", $appointments->local_time ),
				'user'		=>	$user_id,
				'name'		=>	$name,
				'email'		=>	$email,
				'phone'		=>	$phone,
				'address'	=>	$address,
				'city'		=>	$city,
				'location'	=>	$location,
				'service'	=>	$service,
				'worker'	=> 	$worker,
				'price'		=>	$price,
				'status'	=>	$status,
				'start'		=>	date ("Y-m-d H:i:s", $start),
				'end'		=>	date ("Y-m-d H:i:s", $start + ($duration * 60 ) ),
				'note'		=>	$note
			)
		);

		if (!$result) {
			die(json_encode(array(
				"error" => __( 'Appointment could not be saved. Please contact website admin.', 'appointments'),
			)));
		}

		// A new appointment is accepted, so clear cache
		$insert_id = $wpdb->insert_id; // Save insert ID
		$appointments->flush_cache();
		$appointments->save_cookie( $insert_id, $name, $email, $phone, $address, $city, $gcal );
		do_action( 'app_new_appointment', $insert_id );

		// Send confirmation for pending, payment not required cases, if selected so
		if (
			'yes' != $this->options["payment_required"] &&
			isset($this->options["send_notification"]) &&
			'yes' == $this->options["send_notification"] &&
			'pending' == $status
		) {
			$appointments->send_notification( $insert_id );
		}

		// Send confirmation if we forced it
		if ('confirmed' == $status && isset($this->options["send_confirmation"]) && 'yes' == $this->options["send_confirmation"]) {
			$appointments->send_confirmation( $insert_id );
		}

		// Add to GCal API
		if (is_object($appointments->gcal_api) && $appointments->gcal_api->is_syncable_status($status)) {
			$appointments->gcal_api->insert( $insert_id );
		}

		// GCal button
		if (isset($this->options["gcal"]) && 'yes' == $this->options["gcal"] && $gcal) {
			$gcal_url = $appointments->gcal( $service, $start, $start + ($duration * 60 ), false, $address, $city );
		} else {
			$gcal_url = '';
		}

		$additional = array(
			'mp' => 0,
			'variation' => null,
		);
		$additional = apply_filters('app-appointment-appointment_created', $additional, $insert_id, $post_id, $service, $worker, $start, $end);
		$mp = isset($additional['mp']) ? $additional['mp'] : 0;
		$variation = isset($additional['variation']) ? $additional['variation'] : 0;

		$gcal_same_window = !empty($this->options["gcal_same_window"]) ? 1 : 0;

		if (isset( $this->options["payment_required"] ) && 'yes' == $this->options["payment_required"]) {
			die(json_encode(array(
				"cell" => $_POST["value"],
				"app_id" => $insert_id,
				"refresh" => 0,
				"price" => $paypal_price,
				"service_name" => stripslashes( $service_result->name ),
				'gcal_url' => $gcal_url,
				'gcal_same_window' => $gcal_same_window,
				'mp' => $mp,
				'variation' => $variation
			)));
		} else {
			die(json_encode(array(
				"cell" => $_POST["value"],
				"app_id" => $insert_id,
				"refresh" => 1,
				'gcal_url' => $gcal_url,
				'gcal_same_window' => $gcal_same_window,
			)));
		}
	}

	/**
	 *	IPN handling for Paypal
	 */
	function handle_paypal_return() {

		global $appointments;

		// PayPal IPN handling code
		$this->options = get_option( 'appointments_options' );

		if ((isset($_POST['payment_status']) || isset($_POST['txn_type'])) && isset($_POST['custom'])) {

			if ($this->options['mode'] == 'live') {
				$domain = 'https://www.paypal.com';
			} else {
				$domain = 'https://www.sandbox.paypal.com';
			}

			$req = 'cmd=_notify-validate';
			foreach ($_POST as $k => $v) {
				if (get_magic_quotes_gpc()) $v = stripslashes($v);
				$req .= '&' . $k . '=' . $v;
			}

			$header = 'POST /cgi-bin/webscr HTTP/1.0' . "\r\n"
			          . 'Content-Type: application/x-www-form-urlencoded' . "\r\n"
			          . 'Content-Length: ' . strlen($req) . "\r\n"
			          . "\r\n";

			@set_time_limit(60);
			if ($conn = @fsockopen($domain, 80, $errno, $errstr, 30)) {
				fputs($conn, $header . $req);
				socket_set_timeout($conn, 30);

				$response = '';
				$close_connection = false;
				while (true) {
					if (feof($conn) || $close_connection) {
						fclose($conn);
						break;
					}

					$st = @fgets($conn, 4096);
					if ($st === false) {
						$close_connection = true;
						continue;
					}

					$response .= $st;
				}

				$error = '';
				$lines = explode("\n", str_replace("\r\n", "\n", $response));
				// looking for: HTTP/1.1 200 OK
				if (count($lines) == 0) $error = 'Response Error: Header not found';
				else if (substr($lines[0], -7) != ' 200 OK') $error = 'Response Error: Unexpected HTTP response';
				else {
					// remove HTTP header
					while (count($lines) > 0 && trim($lines[0]) != '') array_shift($lines);

					// first line will be empty, second line will have the result
					if (count($lines) < 2) $error = 'Response Error: No content found in transaction response';
					else if (strtoupper(trim($lines[1])) != 'VERIFIED') $error = 'Response Error: Unexpected transaction response';
				}

				if ($error != '') {
					$appointments->log( $error );
					exit;
				}
			}

			// We are using server time. Not Paypal time.
			$timestamp = $appointments->local_time;

			// process PayPal response
			switch ($_POST['payment_status']) {
				case 'Partially-Refunded':
					break;

				case 'In-Progress':
					break;

				case 'Completed':
				case 'Processed':
					// case: successful payment
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];

					$appointments->record_transaction($_POST['custom'], $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], '');
					if ( $appointments->change_status( 'paid', $_POST['custom'] ) )
						$appointments->send_confirmation( $_POST['custom'] );
					else {
						// Something wrong. Warn admin
						$message = sprintf( __('Paypal confirmation arrived, but status could not be changed for some reason. Please check appointment with ID %s', 'appointments'), $_POST['custom'] );

						wp_mail( $appointments->get_admin_email( ), __('Appointment status could not be changed','appointments'), $message, $appointments->message_headers() );
					}
					break;

				case 'Reversed':
					// case: charge back
					$note = __('Last transaction has been reversed. Reason: Payment has been reversed (charge back)', 'appointments');
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];

					$appointments->record_transaction($_POST['custom'], $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);
					break;

				case 'Refunded':
					// case: refund
					$note = __('Last transaction has been reversed. Reason: Payment has been refunded', 'appointments');
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];

					$appointments->record_transaction($_POST['custom'], $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);
					break;

				case 'Denied':
					// case: denied
					$note = __('Last transaction has been reversed. Reason: Payment Denied', 'appointments');
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];

					$appointments->record_transaction($_POST['custom'], $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);

					break;

				case 'Pending':
					// case: payment is pending
					$pending_str = array(
						'address' => __('Customer did not include a confirmed shipping address', 'appointments'),
						'authorization' => __('Funds not captured yet', 'appointments'),
						'echeck' => __('eCheck that has not cleared yet', 'appointments'),
						'intl' => __('Payment waiting for aproval by service provider', 'appointments'),
						'multi-currency' => __('Payment waiting for service provider to handle multi-currency process', 'appointments'),
						'unilateral' => __('Customer did not register or confirm his/her email yet', 'appointments'),
						'upgrade' => __('Waiting for service provider to upgrade the PayPal account', 'appointments'),
						'verify' => __('Waiting for service provider to verify his/her PayPal account', 'appointments'),
						'*' => ''
					);
					$reason = @$_POST['pending_reason'];
					$note = __('Last transaction is pending. Reason: ', 'appointments') . (isset($pending_str[$reason]) ? $pending_str[$reason] : $pending_str['*']);
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];

					// Save transaction.
					$appointments->record_transaction($_POST['custom'], $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);

					break;

				default:
					// case: various error cases
			}
		} else {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.
			// This is IPN response, so echoing will not help. Let's log it.
			$appointments->log( 'Error: Missing POST variables. Identification is not possible.' );
			exit;
		}
		exit;
	}
	/**
	 * Check and return necessary fields to the front end
	 * @return json object
	 */
	function pre_confirmation () {
		global $appointments;

		$values = explode( ":", $_POST["value"] );
		$location = $values[0];
		$service = $values[1];
		$worker = $values[2];
		$start = $values[3];
		$end = $values[4];

		// A little trick to pass correct lsw variables to the get_price, is_busy and get_capacity functions
		$_REQUEST["app_location_id"] = $location;
		$_REQUEST["app_service_id"] = $service;
		$_REQUEST["app_provider_id"] = $worker;
		$appointments->get_lsw();

		// Alright, so before we go further, let's check if we can
		if (!is_user_logged_in() && (!empty($this->options['login_required']) && 'yes' == $this->options['login_required'])) {
			die(json_encode(array(
				'error' => __('You need to login to make an appointment.', 'appointments'),
			)));
		}

		$price = $appointments->get_price();

		// It is possible to apply special discounts
		$price = apply_filters('app_display_amount', $price, $service, $worker);
		$price = apply_filters('app_pre_confirmation_price', $price, $service, $worker, $start, $end);

		$display_currency = !empty($this->options["currency"])
			? App_Template::get_currency_symbol($this->options["currency"])
			: App_Template::get_currency_symbol('USD')
		;

		if ($appointments->is_busy($start,  $end, $appointments->get_capacity())) {
			die(json_encode(array(
				"error" => apply_filters(
					'app_booked_message',
					__( 'We are sorry, but this time slot is no longer available. Please refresh the page and try another time slot. Thank you.', 'appointments')
				)
			)));
		}

		$service_obj = $appointments->get_service($service);
		$service = '<label><span>' . __('Service name: ', 'appointments') .  '</span>'. apply_filters('app_confirmation_service', stripslashes($service_obj->name), $service_obj->name) . '</label>';
		$start = '<label><span>' . __('Date and time: ', 'appointments') . '</span>'. apply_filters('app_confirmation_start', date_i18n($appointments->datetime_format, $start), $start) . '</label>';
		$end = '<label><span>' . __('Lasts (approx): ', 'appointments') . '</span>'. apply_filters('app_confirmation_lasts', $service_obj->duration . " " . __('minutes', 'appointments'), $service_obj->duration) . '</label>';

		$price = $price > 0
			? '<label><span>' . __('Price: ', 'appointments') .  '</span>'. apply_filters('app_confirmation_price', $price . " " . $display_currency, $price) . '</label>'
			: 0
		;

		$worker = !empty($worker)
			? '<label><span>' . __('Service provider: ', 'appointments' ) . '</span>'. apply_filters('app_confirmation_worker', stripslashes($appointments->get_worker_name($worker)), $worker) . '</label>'
			: ''
		;

		$ask_name = !empty($this->options['ask_name'])
			? 'ask'
			: ''
		;

		$ask_email = !empty($this->options['ask_email'])
			? 'ask'
			: ''
		;

		$ask_phone = !empty($this->options['ask_phone'])
			? 'ask'
			: ''
		;

		$ask_address = !empty($this->options['ask_address'])
			? 'ask'
			: ''
		;

		$ask_city = !empty($this->options['ask_city'])
			? 'ask'
			: ''
		;

		$ask_note = !empty($this->options['ask_note'])
			? 'ask'
			: ''
		;

		$ask_gcal = isset( $this->options["gcal"] ) && 'yes' == $this->options["gcal"]
			? 'ask'
			: ''
		;

		$reply_array = array(
			'service'	=> $service,
			'worker'	=> $worker,
			'start'		=> $start,
			'end'		=> $end,
			'price'		=> $price,
			'name'		=> $ask_name,
			'email'		=> $ask_email,
			'phone'		=> $ask_phone,
			'address'	=> $ask_address,
			'city'		=> $ask_city,
			'note'		=> $ask_note,
			'gcal'		=> $ask_gcal
		);

		$reply_array = apply_filters('app_pre_confirmation_reply', $reply_array);

		die(json_encode($reply_array));
	}



}
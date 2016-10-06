<?php


class Appointments_AJAX {

	public $options = array();
	public $_google_user_cache = null;

	public function __construct() {
		global $appointments;
		add_action( 'wp_ajax_nopriv_app_paypal_ipn', array(&$this, 'handle_paypal_return')); // Send Paypal to IPN function

		add_action( 'wp_ajax_inline_edit', array( &$this, 'inline_edit' ) ); 			// Add/edit appointments
		add_action( 'wp_ajax_inline_edit_save', array( &$this, 'inline_edit_save' ) ); 	// Save edits
		add_action( 'wp_ajax_js_error', array( &$this, 'js_error' ) ); 					// Track js errors
		add_action( 'wp_ajax_app_export', array( &$this, 'export' ) ); 					// Export apps

		// Front end ajax hooks
		add_action( 'wp_ajax_pre_confirmation', array( &$this, 'pre_confirmation' ) ); 			// Get pre_confirmation results
		add_action( 'wp_ajax_nopriv_pre_confirmation', array( &$this, 'pre_confirmation' ) ); 	// Get pre_confirmation results
		add_action( 'wp_ajax_post_confirmation', array( &$this, 'post_confirmation' ) ); 		// Do after final confirmation
		add_action( 'wp_ajax_nopriv_post_confirmation', array( &$this, 'post_confirmation' ) ); // Do after final confirmation

		add_action( 'wp_ajax_cancel_app', array( $appointments, 'cancel' ) ); 							// Cancel appointment from my appointments
		add_action( 'wp_ajax_nopriv_cancel_app', array( $appointments, 'cancel' ) );

		add_action( 'wp_ajax_services_load_thumbnail', array( $this, 'load_service_thumbnail' ) );
		add_action( 'wp_ajax_nopriv_services_load_thumbnail', array( $this, 'load_service_thumbnail' ) );
	}

	public function load_service_thumbnail() {
		wp_send_json_success( array( 'hello' ) );
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
		global $appointments, $wpdb, $current_user;

		$app_id = absint( $_POST["app_id"] );
		$app = appointments_get_appointment( $app_id );
		$data = array();

		$data['user'] = $_POST['user'];
		$data['email'] = !empty($_POST['email']) && is_email($_POST['email']) ? $_POST['email'] : '';
		$data['name'] = $_POST['name'];
		$data['phone'] = $_POST['phone'];
		$data['address'] = $_POST['address'];
		$data['city'] = $_POST['city'];
		$data['service'] = $_POST['service'];
		$data['worker'] = $_POST['worker'];
		$data['price'] = $_POST['price'];
		$data['note'] = $_POST['note'];
		$data['status'] = $_POST['status'];
		$data['date'] = $_POST['date'];
		$data['time'] = $_POST['time'];
		$resend = $_POST["resend"];

		$data = apply_filters('app-appointment-inline_edit-save_data', $data);

		$error = apply_filters( 'appointments_inline_edit_error', false, $data, $_REQUEST );
		if ( is_wp_error( $error ) ) {
			$result = array(
				'app_id' => $app_id,
				'message' => '<strong style="color:red;">' . _x( 'Error', 'Error while editing an appointment', 'appointments' ) . ': ' . $error->get_error_message() . '</strong>'
			);
			wp_send_json( $result );
		}
		elseif ( true === $error ) {
			// Unknown error
			$result = array(
				'app_id' => $app_id,
				'message' => '<strong style="color:red;">' . _x( 'Error', 'Error while editing an appointment', 'appointments' ) . ': ' . __( 'Record could not be saved OR you did not make any changes!', 'appointments' ) . '</strong>'
			);
			wp_send_json( $result );
		}

		do_action( 'appointments_inline_edit', $app_id, $data );


		$update_result = $insert_result = false;
		if ( $app ) {
			// Update
			$update_result = appointments_update_appointment( $app_id, $data );
			if ( $resend && 'removed' != $data['status'] ) {
				appointments_send_confirmation( $app_id );
			}

		} else {
			// Insert
			if ( ! $resend ) {
				add_filter( 'appointments_send_confirmation', '__return_false', 50 );
			}
			$app_id = appointments_insert_appointment( $data );
			$insert_result = true;
		}

		do_action('app-appointment-inline_edit-after_save', $app_id, $data);
		do_action('app-appointment-inline_edit-before_response', $app_id, $data);

		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			$insert_result = false;
			$update_result = false;
		}


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
				'app_id' => $app->ID,
				'message' => __('<span style="color:green;font-weight:bold">Changes saved.</span>', 'appointments'),
			);
		} else {
			$message = $resend && !empty($data['status']) && 'removed' != $data['status']
				? sprintf('<span style="color:green;font-weight:bold">%s</span>', __('Confirmation message (re)sent', 'appointments'))
				: sprintf('<span style="color:red;font-weight:bold">%s</span>', __('Record could not be saved OR you did not make any changes!', 'appointments'))
			;
			$result = array(
				'app_id' => $app_id,
				'message' => $message,
			);
		}

		$result = apply_filters('app-appointment-inline_edit-result', $result, $app_id, $data);
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
			$app = appointments_get_appointment( $app_id );
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
			$app->ID = 0;
			// Set other fields to default so that we don't get notice messages
			$app->user = $app->location = $app->worker = 0;
			$app->created = $app->end = $app->name = $app->email = $app->phone = $app->address = $app->city = $app->status = $app->sent = $app->sent_worker = $app->note = '';

			// Get first service and its price
			$app->service = appointments_get_services_min_id();
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

		$columns = isset( $_POST['columns'] ) ? absint( $_POST['columns'] ) : absint( $_POST['col_len'] );
		$html .= isset($_POST['col_len']) && is_numeric($_POST['col_len'])
			? '<td colspan="' . $columns . '" class="colspanchange">'
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
		$services = appointments_get_services();
		$html .= '<label>';
		$html .= '<span class="title">'.__('Name', 'appointments'). '</span>';
		$html .= '<select name="service">';
		if ( $services ) {
			foreach ( $services as $service ) {
				$html .= '<option value="' . esc_attr($service->ID) . '"'.selected( $app->service, $service->ID, false ).'>'. stripslashes( $service->name ) . '</option>';
			}
		}
		$html .= '</select>';
		$html .= '</label>';
		/* Workers */
		$workers = appointments_get_workers();
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
				$html .= '<option value="' . esc_attr($worker->ID) . '"'.$sel.'>'. appointments_get_worker_name( $worker->ID, false ) . '</option>';
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
		if ( isset( $appointments->options["admin_min_time"] ) && $appointments->options["admin_min_time"] )
			$min_time = $appointments->options["admin_min_time"];
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
		$html .= '<textarea name="note" cols="22" rows=1">';
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


	/**
	 * Make checks on submitted fields and save appointment
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
		if ('yes' != $appointments->options["payment_required"] && isset($appointments->options["auto_confirm"]) && 'yes' == $appointments->options["auto_confirm"]) {
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
			'pending' === $status && "yes" === $appointments->options["payment_required"] // ... in a paid environment ...
			&&
			(!empty($appointments->options["auto_confirm"]) && "yes" === $appointments->options["auto_confirm"]) // ... with auto-confirm activated
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

		$email = $user_email;
		if ( ! empty( $_POST['app_email'] ) ) {
			$_email = sanitize_email( $_POST['app_email'] );
			if ( is_email( $_email ) ) {
				$email = $_email;
			}
		}

		if ($appointments->options["ask_email"] && !is_email($email)) $appointments->json_die( 'email' );

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

		$service_result = appointments_get_service( $service );

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

		$args = array(
			'user'     => $user_id,
			'name'     => $name,
			'email'    => $email,
			'phone'    => $phone,
			'address'  => $address,
			'city'     => $city,
			'location' => $location,
			'service'  => $service,
			'worker'   => $worker,
			'price'    => $price,
			'status'   => $status,
			'date'    => $start,
			'note'     => $note,
			'duration' => $duration
		);

		$error = apply_filters( 'appointments_post_confirmation_error', false, $args, $_REQUEST );
		if ( is_wp_error( $error ) ) {
			wp_send_json( array( 'error' => $error->get_error_message() ) );
		}
		elseif ( true === $error ) {
			// Unknown error
			wp_send_json( array( 'error' => __( 'Appointment could not be saved. Please contact website admin.', 'appointments') ) );
		}

		$insert_id = appointments_insert_appointment( $args );

		appointments_clear_appointment_cache();

		if (!$insert_id) {
			die(json_encode(array(
				"error" => __( 'Appointment could not be saved. Please contact website admin.', 'appointments'),
			)));
		}

		// A new appointment is accepted, so clear cache
		$appointments->flush_cache();
		$appointments->save_cookie( $insert_id, $name, $email, $phone, $address, $city, $gcal );

		// Send confirmation for pending, payment not required cases, if selected so
		if (
			'yes' != $appointments->options["payment_required"] &&
			isset($appointments->options["send_notification"]) &&
			'yes' == $appointments->options["send_notification"] &&
			'pending' == $status
		) {
			appointments_send_notification( $insert_id );
		}



		// GCal button
		if (isset($appointments->options["gcal"]) && 'yes' == $appointments->options["gcal"] && $gcal) {
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

		$gcal_same_window = !empty($appointments->options["gcal_same_window"]) ? 1 : 0;

		if (isset( $appointments->options["payment_required"] ) && 'yes' == $appointments->options["payment_required"]) {
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
			$result = array(
				"cell" => $_POST["value"],
				"app_id" => $insert_id,
				"refresh" => 1,
				'gcal_url' => $gcal_url,
				'gcal_same_window' => $gcal_same_window,
			);
			wp_send_json( $result );
		}
	}

	/**
	 *	IPN handling for Paypal
	 */
	function handle_paypal_return() {

		global $appointments;

		// PayPal IPN handling code
		$appointments->options = get_option( 'appointments_options' );

		if ((isset($_POST['payment_status']) || isset($_POST['txn_type'])) && isset($_POST['custom'])) {

			if ($appointments->options['mode'] == 'live') {
				$domain = 'https://www.paypal.com';
			} else {
				$domain = 'https://www.sandbox.paypal.com';
			}

			$req = 'cmd=_notify-validate';
			foreach ($_POST as $k => $v) {
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


					$args = array(
						'paypal_ID' => $_POST['txn_id'],
						'stamp' => $timestamp,
						'total_amount' => $amount,
						'currency' => $currency,
						'status' => $_POST['payment_status'],
						'note' => '',
					);

					$transaction = appointments_get_transaction( $_POST['custom'] );
					if ( ! $transaction ) {
						appointments_update_transaction( $_POST['custom'], $args );
					}
					else {
						appointments_insert_transaction( $args );
					}

					if ( ! appointments_update_appointment_status( $_POST['custom'], 'paid' ) ) {
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

					$args = array(
						'paypal_ID' => $_POST['txn_id'],
						'stamp' => $timestamp,
						'total_amount' => $amount,
						'currency' => $currency,
						'status' => $_POST['payment_status'],
						'note' => $note,
					);

					$transaction = appointments_get_transaction( $_POST['custom'] );
					if ( ! $transaction ) {
						appointments_update_transaction( $_POST['custom'], $args );
					}
					else {
						appointments_insert_transaction( $args );
					}
					break;

				case 'Refunded':
					// case: refund
					$note = __('Last transaction has been reversed. Reason: Payment has been refunded', 'appointments');
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];

					$args = array(
						'paypal_ID' => $_POST['txn_id'],
						'stamp' => $timestamp,
						'total_amount' => $amount,
						'currency' => $currency,
						'status' => $_POST['payment_status'],
						'note' => $note,
					);

					$transaction = appointments_get_transaction( $_POST['custom'] );
					if ( ! $transaction ) {
						appointments_update_transaction( $_POST['custom'], $args );
					}
					else {
						appointments_insert_transaction( $args );
					}
					break;

				case 'Denied':
					// case: denied
					$note = __('Last transaction has been reversed. Reason: Payment Denied', 'appointments');
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];

					$args = array(
						'paypal_ID' => $_POST['txn_id'],
						'stamp' => $timestamp,
						'total_amount' => $amount,
						'currency' => $currency,
						'status' => $_POST['payment_status'],
						'note' => $note,
					);

					$transaction = appointments_get_transaction( $_POST['custom'] );
					if ( ! $transaction ) {
						appointments_update_transaction( $_POST['custom'], $args );
					}
					else {
						appointments_insert_transaction( $args );
					}

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

					$args = array(
						'paypal_ID' => $_POST['txn_id'],
						'stamp' => $timestamp,
						'total_amount' => $amount,
						'currency' => $currency,
						'status' => $_POST['payment_status'],
						'note' => $note,
					);

					$transaction = appointments_get_transaction( $_POST['custom'] );
					if ( ! $transaction ) {
						appointments_update_transaction( $_POST['custom'], $args );
					}
					else {
						appointments_insert_transaction( $args );
					}

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
		if (!is_user_logged_in() && (!empty($appointments->options['login_required']) && 'yes' == $appointments->options['login_required'])) {
			die(json_encode(array(
				'error' => __('You need to login to make an appointment.', 'appointments'),
			)));
		}

		$price = $appointments->get_price();

		// It is possible to apply special discounts
		$price = apply_filters('app_display_amount', $price, $service, $worker);
		$price = apply_filters('app_pre_confirmation_price', $price, $service, $worker, $start, $end);

		$display_currency = !empty($appointments->options["currency"])
			? App_Template::get_currency_symbol($appointments->options["currency"])
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

		$service_obj = appointments_get_service($service);
		$service = '<label><span>' . __('Service name: ', 'appointments') .  '</span>'. apply_filters('app_confirmation_service', stripslashes($service_obj->name), $service_obj->name) . '</label>';
		$start = '<label><span>' . __('Date and time: ', 'appointments') . '</span>'. apply_filters('app_confirmation_start', date_i18n($appointments->datetime_format, $start), $start) . '</label>';
		$end = '<label><span>' . __('Lasts (approx): ', 'appointments') . '</span>'. apply_filters('app_confirmation_lasts', $service_obj->duration . " " . __('minutes', 'appointments'), $service_obj->duration) . '</label>';

		$price = $price > 0
			? '<label><span>' . __('Price: ', 'appointments') .  '</span>'. apply_filters('app_confirmation_price', $price . " " . $display_currency, $price) . '</label>'
			: 0
		;

		$worker = !empty($worker)
			? '<label><span>' . __('Service provider: ', 'appointments' ) . '</span>'. apply_filters('app_confirmation_worker', stripslashes(appointments_get_worker_name($worker)), $worker) . '</label>'
			: ''
		;

		$ask_name = !empty($appointments->options['ask_name'])
			? 'ask'
			: ''
		;

		$ask_email = !empty($appointments->options['ask_email'])
			? 'ask'
			: ''
		;

		$ask_phone = !empty($appointments->options['ask_phone'])
			? 'ask'
			: ''
		;

		$ask_address = !empty($appointments->options['ask_address'])
			? 'ask'
			: ''
		;

		$ask_city = !empty($appointments->options['ask_city'])
			? 'ask'
			: ''
		;

		$ask_note = !empty($appointments->options['ask_note'])
			? 'ask'
			: ''
		;

		$ask_gcal = isset( $appointments->options["gcal"] ) && 'yes' == $appointments->options["gcal"]
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


	/**
	 * Save a CSV file of all appointments
	 * @since 1.0.9
	 */
	function export(){
		global $appointments;

		$type = ! empty( $_POST['export_type'] ) ? $_POST['export_type'] : 'all';
		$apps = array();
		if ( 'selected' == $type && ! empty( $_POST['app'] ) ) {
			// selected appointments
			if ( $_POST['app'] ) {
				$apps = appointments_get_appointments( array( 'app_id' => array_map( 'absint', $_POST['app'] ) ) );
			}
		} else if ( 'type' == $type ) {
			$status = ! empty( $_POST['status'] ) ? $_POST['status'] : false;
			if ( 'active' === $status ) {
				$apps = appointments_get_appointments( array( 'status' => array( 'confirmed', 'paid' ) ) );
			} else if ( $status ) {
				$apps = appointments_get_appointments( array( 'status' => $status ) );
			}
		} else if ( 'all' == $type ) {
			$apps = appointments_get_appointments();
		}

		if ( empty( $apps ) || ! is_array( $apps ) ) {
			die( __( 'Nothing to download!', 'appointments' ) );
		}

		$file = fopen('php://temp/maxmemory:'. (12*1024*1024), 'r+');

		// Add field names to the file
		$columns = array_map( 'strtolower', apply_filters( 'app-export-columns', $appointments->db->get_col_info() ) );
		fputcsv( $file,  $columns );

		foreach ( $apps as $app ) {
			$raw = $app;
			array_walk( $app, array( &$this, 'export_helper' ) );
			$app = apply_filters( 'app-export-appointment', $app, $raw );
			if ( ! empty( $app ) ) {
				fputcsv( $file, (array)$app );
			}
		}

		$filename = "appointments_".date('F')."_".date('d')."_".date('Y').".csv";

		//serve the file
		rewind($file);
		ob_end_clean(); //kills any buffers set by other plugins
		header('Content-Description: File Transfer');
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		$output = stream_get_contents($file);
		//$output = $output . "\xEF\xBB\xBF"; // UTF-8 BOM
		header('Content-Length: ' . strlen($output));
		fclose($file);
		die($output);
	}

	/**
	 * Helper function for export
	 * @since 1.0.9
	 */
	function export_helper( &$value, $key ) {
		global $appointments;
		if ( 'created' == $key || 'start' == $key || 'end' == $key )
			$value = mysql2date( $appointments->datetime_format, $value );
		else if ( 'user' == $key && $value ) {
			$userdata = get_userdata( $value );
			if ( $userdata )
				$value = $userdata->user_login;
		}
		else if ( 'service' == $key )
			$value = $appointments->get_service_name( $value );
		else if ( 'worker' == $key )
			$value = appointments_get_worker_name( $value );
	}



}
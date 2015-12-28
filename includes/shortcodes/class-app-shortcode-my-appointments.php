<?php

/**
 * My appointments list.
 */
class App_Shortcode_MyAppointments extends App_Shortcode {
	public function __construct () {
		$this->_defaults = array(
			'provider' => array(
				'value' => 0,
				'help' => __('Enter 1 if this appointment list belongs to a service provider. Default: "0" (client)', 'appointments'),
				'example' => '1',
			),
			'provider_id' => array(
				'value' => 0,
				'help' => __('Enter the user ID of the provider whose list will be displayed. If ommitted, current service provider will be displayed. Default: "0" (current service provider)', 'appointments'),
				'example' => '12',
			),
			'title' => array(
				'value' => __('<h3>My Appointments</h3>', 'appointments'),
				'help' => __('Title text.', 'appointments'),
				'example' => __('My Appointments', 'appointments'),
			),
			'status' => array(
				'value' => 'paid,confirmed',
				'help' => __('Which status(es) will be included. Possible values: paid, confirmed, completed, pending, removed, reserved or combinations of them separated with comma.', 'appointments'),
				'allowed_values' => array('paid', 'confirmed', 'pending', 'completed', 'removed', 'reserved'),
				'example' => 'paid,confirmed',
			),
			'gcal' => array(
				'value' => 1,
				'help' => __('Enter 0 to disable Google Calendar button by which clients can add appointments to their Google Calendar after booking the appointment. Default: "1" (enabled - provided that "Add Google Calendar Button" setting is set as Yes)', 'appointments'),
				'example' => '0',
			),
			'order_by' => array(
				'value' => 'ID',
				'help' => __('Sort order of the appointments. Possible values: ID, start. Optionally DESC (descending) can be used, e.g. "start DESC" will reverse the order. Default: "ID". Note: This is the sort order as page loads. Table can be dynamically sorted by any field from front end (Some date formats may not be sorted correctly).', 'appointments'),
				'example' => 'ID',
			),
			'allow_cancel' => array(
				'value' => 0,
				'help' => __('Enter 1 if you want to allow cancellation of appointments by the client using this table. "Allow client cancel own appointments" setting must also be set as Yes. Default: "0" (Cancellation is not allowed).', 'appointments'),
				'example' => '1',
			),
			'strict' => array(
				'value' => 0,
				'help' => __('Ensure strict matching when searching for appointments to display. The shortcode will, by default, use the widest possible match.', 'appointments'),
				'example' => '1',
			),

			'_allow_confirm' => array('value' => 0),
			'_tablesorter' => array('value' => 1),

		);
	}

	public function get_usage_info () {
		return __('Inserts a table where client or service provider can see his upcoming appointments.', 'appointments');
	}

	public function process_shortcode ($args=array(), $content='') {
		extract(wp_parse_args($args, $this->_defaults_to_args()));

		global $wpdb, $current_user, $bp, $appointments;

		$statuses = explode( ',', $status );

		if ( !is_array( $statuses ) || empty( $statuses ) )
			return '';

		if ( !trim( $order_by ) )
			$order_by = 'ID';

		$stat = '';
		foreach ( $statuses as $s ) {
			// Allow only defined stats
			if ( array_key_exists( trim( $s ), App_Template::get_status_names() ) )
				$stat .= " status='".trim( $s )."' OR ";
		}
		$stat = rtrim( $stat, "OR " );

		// If this is a client shortcode
		if ( !$provider ) {
			if ( isset( $_COOKIE["wpmudev_appointments"] ) )
				$apps = unserialize( stripslashes( $_COOKIE["wpmudev_appointments"] ) );
			else
				$apps = array();

			if ( !is_array( $apps) )
				return '';

			$provider_or_client = __('Provider', 'appointments' );

			$q = '';
			if ($strict) {
				// Strict matching
				if (is_user_logged_in()) {
					$q = "user={$current_user->ID}"; // If the user is logged in, show just those apps
				} else {
					// Otherwise, deal with the cookie-cached ones
					$apps = array_values(array_filter(array_map('intval', $apps)));
					if (!empty($apps)) $q = "ID IN(" . join(',', $apps) . ")";
				}
			} else {
				// Non-strict matching
				foreach ( $apps as $app_id ) {
					if ( is_numeric( $app_id ) )
						$q .= " ID=".$app_id." OR ";
				}
				$q = rtrim( $q, "OR " );

				// But he may as well has appointments added manually (requires being registered user)
				if ( is_user_logged_in() ) {
					$q .= " OR user=".$current_user->ID;
					$q = ltrim( $q, " OR" );
				}
			}
			if ( $q && $stat ) {
				$results = $wpdb->get_results(
					"SELECT * FROM " . $appointments->app_table .
					" WHERE (".$q.") AND (".$stat.") ORDER BY " . $appointments->sanitize_order_by( $order_by )
				);
			} else $results = false;
		}
		else {
			$provider_or_client = __('Client', 'appointments' );
			// If no id is given, get current user
			if ( !$provider_id )
				$provider_id = $current_user->ID;
			// Special case: If this is a single provider website, show staff appointments in his schedule too
			$workers = $appointments->get_workers();
			if ( App_Roles::current_user_can('manage_options', App_Roles::CTX_STAFF) && ( ( $workers && count( $workers ) == 1 ) || !$workers ) )
				$provider_id .= ' OR worker=0';
			$results = $wpdb->get_results("SELECT * FROM " . $appointments->app_table . " WHERE (worker=".$provider_id.") AND (".$stat.") ORDER BY ".$order_by." " );
		}

		// Can worker confirm pending appointments?
		if ( $_allow_confirm && appointments_is_worker( $current_user->ID ) && isset( $appointments->options['allow_worker_confirm'] ) && 'yes' == $appointments->options['allow_worker_confirm'] )
			$allow_confirm = true;
		else
			$allow_confirm = false;

		// Can client cancel appointments?
		if ( $allow_cancel && !$provider && isset( $appointments->options['allow_cancel'] ) && 'yes' == $appointments->options['allow_cancel'] )
			$a_cancel = true;
		else
			$a_cancel = false;

		$ret  = '';
		$ret .= '<div class="appointments-my-appointments">';

		// Make this a form for BP if confirmation is allowed, but not on admin side user profile page
		if ($this->_can_display_editable($allow_confirm)) {
			$ret .= '<form method="post">';
		}

		$ret .= $title;
		$ret  = apply_filters( 'app_my_appointments_before_table', $ret );
		$ret .= '<table class="my-appointments tablesorter"><thead>';
		$ret .= apply_filters( 'app_my_appointments_column_name',
			'<th class="my-appointments-service">'. __('Service', 'appointments' )
			. '</th><th class="my-appointments-worker">' . $provider_or_client
			. '</th><th class="my-appointments-date">' . __('Date/time', 'appointments' )
			. '</th><th class="my-appointments-status">' . __('Status', 'appointments' ) . '</th>' );
		$colspan = 4;

		if ( $allow_confirm ) {
			$ret .= '<th class="my-appointments-confirm">'. __('Confirm', 'appointments' ) . '</th>';
			$colspan++;
		}
		if ( $a_cancel ) {
			$ret .= '<th class="my-appointments-cancel">'. _x('Cancel', 'Discard existing info', 'appointments') . '</th>';
			$colspan++;
		}
		if ( $gcal && 'yes' == $appointments->options['gcal'] ) {
			$ret .= '<th class="my-appointments-gcal">&nbsp;</th>';
			$colspan++;
		}

		$ret .= '</thead><tbody>';

		if ( $results ) {
			foreach ( $results as $r ) {
				$ret .= '<tr><td>';
				$ret .= $appointments->get_service_name( $r->service ) . '</td>';
				$ret .= apply_filters('app-shortcode-my_appointments-after_service', '', $r);
				$ret .= '<td>';

				if ( !$provider )
					$ret .= $appointments->get_worker_name( $r->worker ) . '</td>';
				else
					$ret .= $appointments->get_client_name( $r->ID ) . '</td>';
				$ret .= apply_filters('app-shortcode-my_appointments-after_worker', '', $r);

				$ret .= '<td>';
				$ret .= date_i18n( $appointments->datetime_format, strtotime( $r->start ) ) . '</td>';
				$ret .= apply_filters('app-shortcode-my_appointments-after_date', '', $r);

				$ret .= '<td>';
				$ret .= App_Template::get_status_name($r->status);
				$ret .= '</td>';
				$ret .= apply_filters('app-shortcode-my_appointments-after_status', '', $r);

				// If allowed so, a worker can confirm an appointment himself
				if ( $allow_confirm ) {
					if ( 'pending' == $r->status )
						$is_readonly = '';
					else
						$is_readonly = ' readonly="readonly"';

					$ret .= '<td><input class="app-my-appointments-confirm" type="checkbox" name="app_confirm['.$r->ID.']" '.$is_readonly.' /></td>';
				}

				// If allowed so, a client can cancel an appointment
				if ( $a_cancel ) {
					// We don't want completed appointments to be cancelled
					$stat = $r->status;
					$in_allowed_stat = apply_filters( 'app_cancel_allowed_status', ('pending' == $stat || 'confirmed' == $stat || 'paid' == $stat), $stat, $r->ID );
					if ( $in_allowed_stat )
						$is_readonly = '';
					else
						$is_readonly = ' readonly="readonly"';

					$ret .= '<td><input class="app-my-appointments-cancel" type="checkbox" name="app_cancel['.$r->ID.']" '.$is_readonly.' /></td>';
				}

				if ( $gcal && 'yes' == $appointments->options['gcal'] ) {
					if ( isset( $appointments->options["gcal_same_window"] ) && $appointments->options["gcal_same_window"] )
						$target = '_self';
					else
						$target = '_blank';
					$ret .= '<td><a title="'.__('Click to submit this appointment to your Google Calendar account','appointments')
					        .'" href="'.$appointments->gcal( $r->service, strtotime( $r->start, $appointments->local_time), strtotime( $r->end, $appointments->local_time), true, $r->address, $r->city )
					        .'" target="'.$target.'">'.$appointments->gcal_image.'</a></td>';
				}

				$ret .= apply_filters( 'app_my_appointments_add_cell', '', $r );

				$ret .= '</tr>';

			}
		} else {
			$ret .= '<tr><td colspan="'.$colspan.'">'. __('No appointments','appointments'). '</td></tr>';
		}

		$ret .= '</tbody></table>';
		$ret  = apply_filters( 'app_my_appointments_after_table', $ret, $results );


		if ($this->_can_display_editable($allow_confirm)) {
			$ret .='<div class="submit">' .
			       '<input type="submit" name="app_bp_settings_submit" value="' . esc_attr(__('Submit Confirm', 'appointments')) . '" class="auto">' .
			       '<input type="hidden" name="app_bp_settings_user" value="' . esc_attr($bp->displayed_user->id) . '">' .
			       wp_nonce_field('app_bp_settings_submit', 'app_bp_settings_submit', true, false ) .
			       '</div>';
			$ret .= '</form>';
		}

		$ret .= '</div>';

		$sorter = 'usLongDate';
		$dateformat = 'us';
		// Search for formats where day is at the beginning
		if ( stripos( str_replace( array('/','-'), '', $appointments->date_format ), 'dmY') !== false ) {
			$sorter = 'shortDate';
			$dateformat = 'uk';
		}

		// Sort table from front end
		if ( $_tablesorter && file_exists( $appointments->plugin_dir . '/js/jquery.tablesorter.min.js' ) )
			$appointments->add2footer( '
				$(".my-appointments").tablesorter({
					dateFormat: "'.$dateformat.'",
					headers: {
						2: {
							sorter:"'.$sorter.'"
						}
					}
				});
				$("th.my-appointments-gcal,th.my-appointments-confirm,th.my-appointments-cancel").removeClass("header");

				$(".app-my-appointments-cancel").change( function() {
					if ( $(this).is(":checked") ) {
						var cancel_box = $(this);
						if ( !confirm("'. esc_js( __("Are you sure you want to cancel the selected appointment?","appointments") ) .'") ) {
							cancel_box.attr("checked", false);
							return false;
						}
						else{
							var cancel_id = $(this).attr("name").replace("app_cancel[","").replace("]","");
							if (cancel_id) {
								var cancel_data = {action: "cancel_app", app_id: cancel_id, cancel_nonce: "'. wp_create_nonce() .'"};
								$.post(_appointments_data.ajax_url, cancel_data, function(response) {
									if (response && response.error ) {
										cancel_box.attr("disabled",true);
										alert(response.error);
									}
									else if (response && response.success) {
										alert("'.esc_js( __("Selected appointment cancelled.","appointments") ).'");
										cancel_box.closest("tr").css("opacity","0.3");
										cancel_box.attr("disabled",true);
									}
									else {
										cancel_box.attr("disabled",true);
										alert("'.esc_js( __("A connection error occurred.","appointments") ).'");
									}
								}, "json");
							}
						}
					}

				});'
			);

		return $ret;
	}

	/**
	 * Checks whether it's sane to display the editable appointments list for current user on a BP profile
	 *
	 * @param bool $allow_confirm Shortcode argument.
	 * @return bool
	 */
	private function _can_display_editable ($allow_confirm=false) {
		if (is_admin()) return false;
		if (!$allow_confirm) return false;

		if (!function_exists('bp_loggedin_user_id') || !function_exists('bp_displayed_user_id')) return false;

		if (!is_user_logged_in()) return false; // Logged out users aren't being shown editable stuff, ever.

		$bp_ready = class_exists('App_BuddyPress') && App_BuddyPress::is_ready();
		$allow_current_user = bp_displayed_user_id() === bp_loggedin_user_id();

		return $bp_ready && $allow_current_user;
	}
}
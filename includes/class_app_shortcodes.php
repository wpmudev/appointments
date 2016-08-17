<?php
/**
 * Contains the default App_Shortcode descendent implementations.
 */

/**
 * Monthly schedule calendar.
 */
class App_Shortcode_MonthlySchedule extends App_Shortcode {

	public function __construct () {
		$this->_defaults = array(
			'title' => array(
				'value' => __('<h3>Our schedule for START</h3>', 'appointments'),
				'help' => __('Text that will be displayed as the schedule title. Placeholders START, WORKER and SERVICE will be automatically replaced by their real values.', 'appointments'),
				'example' => __('Our schedule for START', 'appointments'),
			),
			'logged' => array(
				'value' => __('Click a free day to apply for an appointment.', 'appointments'),
				'help' => __('Text that will be displayed after the title only to the clients who are logged in or you don\'t require a login.', 'appointments'),
				'example' => __('Click a free day to apply for an appointment.', 'appointments'),
			),
			'notlogged' => array(
				'value' => __('You need to login to make an appointment. Please click here to register/login: LOGIN_PAGE', 'appointments'),
				'help' => __('Text that will be displayed after the title only to the clients who are not logged in and you require a login. <code>LOGIN_PAGE</code> will be replaced with your website\'s login page, while <code>REGISTRATION_PAGE</code> will be replaced with your website\'s registration page.', 'appointments'),
				'example' => __('You need to login to make an appointment. Please click here to register/login: LOGIN_PAGE', 'appointments'),
			),
			'service' => array(
				'value' => 0,
				'help' => __('Enter service ID only if you want to force the table display the service with entered ID. Default: "0" (Service is selected by dropdown). Note: Multiple selections are not allowed.', 'appointments'),
				'example' => 12,
			),
			'worker' => array(
				'value' => 0,
				'help' => __('Enter service provider ID only if you want to force the table display the service provider with entered ID. Default: "0" (Service provider is selected by dropdown). Note: Multiple selections are not allowed.', 'appointments'),
				'example' => 15,
			),
			'long' => array(
				'value' => 0,
				'help' => __('If entered 1, long week days are displayed on the schedule table row, e.g. "Saturday" instead of "Sa".', 'appointments'),
				'example' => 1,
			),
			'class' => array(
				'value' => '',
				'help' => __('A css class name for the schedule table. Default is empty.', 'appointments'),
				'example' => 'my-class',
			),
			'add' => array(
				'value' => 0,
				'help' => __('Number of months to add to the schedule to use for preceding months\' schedules. Enter 1 for next month, 2 for the other month, so on. Default: "0" (Current month)', 'appointments'),
				'example' => 1,
			),
			'widget' => array(
				'value' => 0,
			),
			'date' => array(
				'value' => false,
				'help' => __('Normally calendar starts from the current month. If you want to force it to start from a certain date, enter that date here. Most date formats are supported, but YYYY-MM-DD is recommended. Notes: 1) This value will also affect other subsequent calendars on the same page. 2) It is sufficient to enter a date inside the month. Default: "0" (Current month)', 'appointments'),
				'example' => '2014-02-01',
			),
			'require_provider' => array(
				'value' => 0,
				'help' => __('Setting this argument to "1" means a timetable will not be rendered unless a service provider has been previously selected.', 'appointments'),
				'example' => 1,
			),
			'required_message' => array(
				'value' => __('Please, select a service provider.', 'appointments'),
				'help' => __('The message that will be shown if service providers are required.', 'appointments'),
				'example' => __('Please, select a service provider.', 'appointments'),
			),
			'require_service' => array(
				'value' => 0,
				'help' => __('Setting this argument to "1" means a timetable will not be rendered unless a service has been previously selected.', 'appointments'),
				'example' => 1,
			),
			'required_service_message' => array(
				'value' => __('Please, select a service.', 'appointments'),
				'help' => __('The message that will be shown if services are required.', 'appointments'),
				'example' => __('Please, select a service.', 'appointments'),
			),
		);
	}

	public function process_shortcode ($args=array(), $content='') {
		global $current_user, $appointments;

		extract(wp_parse_args($args, $this->_defaults_to_args()));


		// Force service
		if ( $service ) {
			// Check if such a service exists
			if ( !appointments_get_service( $service ) )
				return;
			$_REQUEST["app_service_id"] = $service;
		}

		$appointments->get_lsw(); // This should come after Force service


		$workers_by_service = appointments_get_workers_by_service( $appointments->service );
		$single_worker = false;
		if ( 1 === count( $workers_by_service ) ) {
			$single_worker = $workers_by_service[0]->ID;
		}

		// Force worker or pick up the single worker
		if ( $worker ) {
			// Check if such a worker exists
			if (! appointments_is_worker($worker)) return;
			$_REQUEST["app_provider_id"] = $worker;
		}
		else if ( $single_worker ) {
			// Select the only provider if that is the case
			$_REQUEST["app_provider_id"] = $single_worker;
			$worker = $single_worker;
		}

		// Force a date
		if ( $date && !isset( $_GET["wcalendar"] ) ) {
			$time = $appointments->first_of_month( strtotime( $date, $appointments->local_time ), $add );
			$_GET["wcalendar"] = $time;
		}
		else {
			if (!empty($_GET['wcalendar_human'])) $_GET['wcalendar'] = strtotime($_GET['wcalendar_human']);
			if ( isset( $_GET["wcalendar"] ) && (int)$_GET['wcalendar'] )
				$time = $appointments->first_of_month( (int)$_GET["wcalendar"], $add  );
			else
				$time = $appointments->first_of_month( $appointments->local_time, $add  );
		}

		$year = date("Y", $time);
		$month = date("m",  $time);

		if (!empty($title)) {
			$replacements = array(
				date_i18n("F Y",  strtotime("{$year}-{$month}-01")), // START
				appointments_get_worker_name(
					(!empty($_REQUEST['app_provider_id']) ? $_REQUEST['app_provider_id'] : null)
				),
				$appointments->get_service_name(
					(!empty($_REQUEST['app_service_id']) ? $_REQUEST['app_service_id'] : null)
				),
			);
			$title = str_replace(
				array("START", "WORKER", "SERVICE"),
				$replacements,
			$title);
		} else {
			$title = '';
		}

		$has_worker = !empty($appointments->worker) || !empty($worker);

        $has_service = !empty($require_service) ? $_REQUEST["app_service_id"] : false;
        
        

		$c  = '';
        $c .= '<div class="appointments-wrapper">';

		if (!$has_worker && !empty($require_provider)) {
			$c .= !empty($required_message)
				? $required_message
				: __('Please, select a service provider.', 'appointments')
			;
 		} elseif (!$has_service && !empty($require_service)) {
			$c .= !empty($required_service_message)
				? $required_service_message
				: __('Please, select a service.', 'appointments')
			;
 		} else {
	        $c .= apply_filters('app-shortcodes-monthly_schedule-title', $title, $args);

			if ( is_user_logged_in() || 'yes' != $appointments->options["login_required"] ) {
				$c .= $logged ? "<div class='appointments-instructions'>{$logged}</div>" : '';
			} else {
				$codec = new App_Macro_GeneralCodec;
				if ( !@$appointments->options["accept_api_logins"] ) {
					//$c .= str_replace( 'LOGIN_PAGE', '<a class="appointments-login_show_login" href="'.site_url( 'wp-login.php').'">'. __('Login','appointments'). '</a>', $notlogged );
					$c .= $codec->expand($notlogged, App_Macro_GeneralCodec::FILTER_BODY);
				} else {
					$c .= '<div class="appointments-login">';
					//$c .= str_replace( 'LOGIN_PAGE', '<a class="appointments-login_show_login" href="javascript:void(0)">'. __('Login','appointments'). '</a>', $notlogged );
					$c .= $codec->expand($notlogged, App_Macro_GeneralCodec::FILTER_BODY);
					$c .= '<div class="appointments-login_inner">';
					$c .= '</div>';
					$c .= '</div>';
				}
			}

			$c .= '<div class="appointments-list">';
	 			$c .= $appointments->get_monthly_calendar($time, $class, $long, $widget);
			$c .= '</div>';

		}
		$c .= '</div>'; // .appointments-wrapper
		$script = '';

		$appointments->add2footer( $script );

		return $c;
	}

	public function get_usage_info () {
		return __('Creates a monthly calendar plus time tables whose free time slots are clickable to apply for an appointment.', 'appointments');
	}
}


/**
 * Pagination shortcode.
 */
class App_Shortcode_Pagination extends App_Shortcode {

	public function __construct () {
		$this->_defaults = array(
			'step' => array(
				'value' => 1,
				'help' => __('Number of weeks or months that selected time will increase or decrease with each next or previous link click. You may consider entering 4 if you have 4 schedule tables on the page.', 'appointments'),
				'example' => '1',
			),
			'month' => array(
				'value' => 0,
				'help' => __('If entered 1, step parameter will mean month, otherwise week. In short, enter 1 for monthly schedule.', 'appointments'),
				'example' => '1',
			),
			'date' => array(
				'value' => 0,
				'help' => __('This is only required if this shortcode resides above any schedule shortcodes. Otherwise it will follow date settings of the schedule shortcodes. Default: "0" (Current week or month)', 'appointments'),
				'example' => '0',
			),
			'anchors' => array(
				'value' => 1,
				'help' => __('Setting this argument to <code>0</code> will prevent pagination links from adding schedule hash anchors. Default: "1"', 'appointments'),
				'example' => '1',
			),
		);
	}

	public function get_usage_info () {
		return __('Inserts pagination codes (previous, next week or month links) and Legend area.', 'appointments');
	}

	public function process_shortcode ($args=array(), $content='') {
		global $appointments;
		extract(wp_parse_args($args, $this->_defaults_to_args()));

		// Force a date
		if ( $date && !isset( $_GET["wcalendar"] ) ) {
			$time = strtotime( $date, $appointments->local_time );
			$_GET["wcalendar"] = $time;
		}
		else {
			if ( isset( $_GET["wcalendar"] ) && (int)$_GET['wcalendar'] )
				$time = (int)$_GET["wcalendar"] ;
			else
				$time = $appointments->local_time;
		}

		$c = '';
		$script = '';
		// Legends
		if ( isset( $appointments->options['show_legend'] ) && 'yes' == $appointments->options['show_legend'] ) {
			$c .= '<div class="appointments-legend">';
			$c .= '<table class="appointments-legend-table">';
			$n = 0;
			$c .= '<tr>';
			foreach ( $appointments->get_classes() as $class=>$name ) {
				$c .= '<td class="class-name">' .$name . '</td>';
				$c .= '<td class="'.$class.'">&nbsp;</td>';
				$n++;
				if ( 3 == $n )
				$c .= '</tr><tr>';
			}
			$c .= '</tr>';
			$c .= '</table>';
			$c .= '</div>';
			// Do not let clicking box inside legend area
			$script .= '$("table.appointments-legend-table td.free").click(false);';
		}

		// Pagination
		$c .= '<div class="appointments-pagination">';
		if ( !$month ) {
			$prev = $time - ($step*7*86400);
			$next = $time + ($step*7*86400);
			$prev_min = $appointments->local_time - $step*7*86400;
			$next_max = $appointments->local_time + ($appointments->get_app_limit() + 7*$step ) *86400;
			$month_week_next = __('Next Week', 'appointments');
			$month_week_previous = __('Previous Week', 'appointments');
		}
		else {
			$prev = $appointments->first_of_month( $time, -1 * $step );
			$next = $appointments->first_of_month( $time, $step );
			$prev_min = $appointments->first_of_month( $appointments->local_time, -1 * $step );
			$next_max = $appointments->first_of_month( $appointments->local_time, $step ) + $appointments->get_app_limit() * 86400;
			$month_week_next = __('Next Month', 'appointments');
			$month_week_previous = __('Previous Month', 'appointments');
		}

		$hash = !empty($anchors) && (int)$anchors
			? '#app_schedule'
			: ''
		;

		if ( $prev > $prev_min ) {
			$c .= '<div class="previous">';
			$c .= '<a href="'. add_query_arg( "wcalendar", $prev ) . $hash . '">&laquo; '. $month_week_previous . '</a>';
			$c .= '</div>';
		}
		if ( $next < $next_max ) {
			$c .= '<div class="next">';
			$c .= '<a href="'. add_query_arg( "wcalendar", $next ) . $hash . '">'. $month_week_next . ' &raquo;</a>';
			$c .= '</div>';
		}
		$c .= '<div style="clear:both"></div>';
		$c .= '</div>';

		$appointments->add2footer( $script );

		return $c;
	}
}

/**
 * All appointments list.
 */
class App_Shortcode_AllAppointments extends App_Shortcode {
	public function __construct () {
		$this->_defaults = array(
			'title' => array(
				'value' => __('<h3>All Appointments</h3>', 'appointments'),
				'help' => __('Title text.', 'appointments'),
				'example' => __('All Appointments', 'appointments'),
			),
			'status' => array(
				'value' => 'paid,confirmed',
				'help' => __('Which status(es) will be included. Possible values: paid, confirmed, completed, pending, removed, reserved or combinations of them separated with comma.', 'appointments'),
				'allowed_values' => array('paid', 'confirmed', 'pending', 'completed', 'removed', 'reserved'),
				'example' => 'paid,confirmed',
			),
			'order_by' => array(
				'value' => 'start',
				'help' => __('Sort order of the appointments. Possible values: ID, start. Optionally DESC (descending) can be used, e.g. "start DESC" will reverse the order. Default: "start". Note: This is the sort order as page loads. Table can be dynamically sorted by any field from front end (Some date formats may not be sorted correctly).', 'appointments'),
				'example' => 'start',
			),
			'_tablesorter' => array(
				'value' => 1,
			),
		);
	}

	public function get_usage_info () {
		return __('Inserts a table that displays all upcoming appointments.', 'appointments');
	}

	public function process_shortcode ($args=array(), $content='') {
		global $appointments;
		extract(wp_parse_args($args, $this->_defaults_to_args()));

		$statuses = explode( ',', $status );

		if ( ! is_array( $statuses ) || empty( $statuses ) ) {
			return '';
		}

		if ( ! trim( $order_by ) ) {
			$order_by = 'start';
		}


		$query_args = array(
			'status' => $statuses,
			'orderby' => $order_by
		);
		$results = appointments_get_appointments( $query_args );

		$ret  = '';
		$ret .= '<div class="appointments-all-appointments">';
		$ret .= $title;
		$ret  = apply_filters( 'app_all_appointments_before_table', $ret );
		$ret .= '<table class="all-appointments tablesorter"><thead>';
		$ret .= apply_filters( 'app_all_appointments_column_name',
			'<th class="all-appointments-service">'. __('Service', 'appointments' )
			. '</th><th class="all-appointments-provider">' . __('Provider', 'appointments' )
			. '</th><th class="all-appointments-client">' . __('Client', 'appointments' )
			. '</th><th class="all-appointments-date">' . __('Date/time', 'appointments' )
			. '</th><th class="all-appointments-status">' . __('Status', 'appointments' ) . '</th>'
			);
		$colspan = 5;

		$ret .= '</thead><tbody>';

		if ( $results ) {
			foreach ( $results as $r ) {
				$ret .= '<tr><td>';
				$ret .= $appointments->get_service_name( $r->service ) . '</td>';
				$ret .= apply_filters('app-shortcode-all_appointments-after_service', '', $r);

				$ret .= '<td>';
				$ret .= appointments_get_worker_name( $r->worker ) . '</td>';
				$ret .= apply_filters('app-shortcode-all_appointments-after_provider', '', $r);

				$ret .= '<td>';
				$ret .= $appointments->get_client_name( $r->ID ) . '</td>';
				$ret .= apply_filters('app-shortcode-all_appointments-after_client', '', $r);

				$ret .= '<td>';
				$ret .= date_i18n( $appointments->datetime_format, strtotime( $r->start ) ) . '</td>';
				$ret .= apply_filters('app-shortcode-all_appointments-after_date', '', $r);

				$ret .= '<td>';
				$ret .= App_Template::get_status_name($r->status);
				$ret .= '</td>';
				$ret .= apply_filters('app-shortcode-all_appointments-after_status', '', $r);

				$ret .= apply_filters( 'app_all_appointments_add_cell', '', $r );
				$ret .= '</tr>';
			}
		}
		else
			$ret .= '<tr><td colspan="'.$colspan.'">'. __('No appointments','appointments'). '</td></tr>';

		$ret .= '</tbody></table>';
		$ret  = apply_filters( 'app_all_appointments_after_table', $ret, $results );

		$ret .= '</div>';

		$sorter = 'usLongDate';
		$dateformat = 'us';
		// Search for formats where day is at the beginning
		if ( stripos( str_replace( array('/','-'), '', $appointments->date_format ), 'dmY') !== false ) {
			$sorter = 'shortDate';
			$dateformat = 'uk';
		}

		// Sort table from front end
		if ( $_tablesorter && file_exists( appointments_plugin_dir() . 'js/jquery.tablesorter.min.js' ) )
			$appointments->add2footer( '
				$(".all-appointments").tablesorter({
					dateFormat: "'.$dateformat.'",
					headers: {
						2: {
							sorter:"'.$sorter.'"
						}
					}
				});
				$("th.all-appointments-gcal,th.all-appointments-confirm,th.all-appointments-cancel").removeClass("header");'
			);

		return $ret;
	}
}



/**
 * Front-end login.
 */
class App_Shortcode_Login extends App_Shortcode {
	public function __construct () {
		$this->_defaults = array(
			'login_text' => array(
				'value' => __('Please click here to login:', 'appointments'),
				'help' => __('Text above the login buttons, proceeded by a login link. Default: "Please click here to login:"', 'appointments'),
				'example' => __('Please click here to login:', 'appointments'),
			),
			'redirect_text' => array(
				'value' => __('Login required to make an appointment. Now you will be redirected to login page.', 'appointments'),
				'help' => __('Javascript text if front end login is not set and user is redirected to login page', 'appointments'),
				'example' => __('Login required to make an appointment. Now you will be redirected to login page.', 'appointments'),
			),
		);
	}

	public function get_usage_info () {
		return __('Inserts front end login buttons for Facebook, Twitter and WordPress.', 'appointments');
	}

	public function process_shortcode ($args=array(), $content='') {
		extract(wp_parse_args($args, $this->_defaults_to_args()));

		global $appointments;

		$ret  = '';
		$ret .= '<div class="appointments-login">';
		if ( !is_user_logged_in() && $appointments->options["login_required"] == 'yes' ){
			$ret .= $login_text. " ";
			$ret .= '<a href="javascript:void(0)" class="appointments-login_show_login" >'. __('Login', 'appointments') . '</a>';
		}
		$ret .= '<div class="appointments-login_inner">';
		$ret .= '</div>';
		$ret .= '</div>';

		$script  = '';
		$script .= "$('.appointments-login_show_login').click(function(){";
		if ( !@$appointments->options["accept_api_logins"] ) {
			$script .= 'var app_redirect=confirm("'.esc_js($redirect_text).'");';
			$script .= ' if(app_redirect){';
			$script .= 'window.location.href= "'.wp_login_url( ).'";';
			$script .= '}';
		}
		else {
			$script .= '$(".appointments-login_link-cancel").focus();';
		}
		$script .= "});";

		$appointments->add2footer( $script );

		return $ret;
	}
}

/**
 * Adds PayPal payment forms.
 */
class App_Shortcode_Paypal extends App_Shortcode {
	public function __construct () {
		$this->_defaults = array(
			'item_name' => array(
				'value' => __('Payment for SERVICE', 'appointments'),
				'help' => __('Item name that will be seen on Paypal. Default: "Payment for SERVICE" if deposit is not asked, "Deposit for SERVICE" if deposit is asked', 'appointments'),
				'example' => __('Payment for SERVICE', 'appointments'),
			),
			'button_text' => array(
				'value' => __('Please confirm PRICE CURRENCY payment for SERVICE', 'appointments'),
				'help' => __('Text that will be displayed on Paypal button. Default: "Please confirm PRICE CURRENCY payment for SERVICE"', 'appointments'),
				'example' => __('Please confirm PRICE CURRENCY payment for SERVICE', 'appointments'),
			),
		);
	}

	public function get_usage_info () {
		return '' .
			__('Inserts PayPal Pay button and form.', 'appointments') .
			'<br />' .
			__('For the shortcode parameters, you can use SERVICE, PRICE, CURRENCY placeholders which will be replaced by their real values.', 'appointments') .
		'';
	}

	public function process_shortcode ($args=array(), $content='') {
		extract(wp_parse_args($args, $this->_defaults_to_args()));

		global $post, $current_user, $appointments;

		if ( 'Payment for SERVICE' == $item_name && ( ( isset( $appointments->options["percent_deposit"] ) && $appointments->options["percent_deposit"] )
			|| ( isset( $appointments->options["fixed_deposit"] ) && $appointments->options["fixed_deposit"] ) ) )
			$item_name = __('Deposit for SERVICE', 'appointments');

		$item_name = apply_filters( 'app_paypal_item_name', $item_name );

		// Let's be on the safe side and select the default currency
		if(empty($appointments->options['currency']))
			$appointments->options['currency'] = 'USD';

		if ( !isset( $appointments->options["return"] ) || !$return = get_permalink( $appointments->options["return"] ) )
			$return = get_permalink( $post->ID );
		// Never let an undefined page, just in case
		if ( !$return )
			$return = home_url();

		$return = apply_filters( 'app_paypal_return', $return );

		$cancel_return = apply_filters( 'app_paypal_cancel_return', get_option('home') );

		$form = '';
		$form .= '<div class="appointments-paypal">';

		if ($appointments->options['mode'] == 'live') {
			$form .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">';
		} else {
			$form .= '<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">';
		}
		$form .= '<input type="hidden" name="business" value="' . esc_attr($appointments->options['merchant_email']) . '" />';
		$form .= '<input type="hidden" name="cmd" value="_xclick">';
		$form .= '<input type="hidden" class="app_item_name" name="item_name" value="' . $item_name . '" />';
		$form .= '<input type="hidden" name="no_shipping" value="1" />';
		$form .= '<input type="hidden" name="currency_code" value="' . $appointments->options['currency'] .'" />';
		$form .= '<input type="hidden" name="return" value="' . $return . '" />';
		$form .= '<input type="hidden" name="cancel_return" value="' . $cancel_return . '" />';
		$form .= '<input type="hidden" name="notify_url" value="' . admin_url('admin-ajax.php?action=app_paypal_ipn') . '" />';
		$form .= '<input type="hidden" name="src" value="0" />';
		$form .= '<input class="app_custom" type="hidden" name="custom" value="" />';
		$form .= '<input class="app_amount" type="hidden" name="amount" value="" />';
		$form .= '<input class="app_submit_btn';
		// Add a class if user not logged in. May be required for addons.
		if ( !is_user_logged_in() )
			$form .= ' app_not_loggedin';

		$display_currency = App_Template::get_currency_symbol($appointments->options["currency"]);
		$form .= '" type="submit" name="submit_btn" value="'. str_replace( array("CURRENCY"), array($display_currency), $button_text).'" />';

		// They say Paypal uses this for tracking. I would prefer to remove it if it is not mandatory.
		$form .= '<img style="display:none" alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" />';

		$form = apply_filters( 'app_paypal_additional_fields', $form, $appointments->location, $appointments->service, $appointments->worker );

		$form .= '</form>';

		$form .= '</div>';

		return $form;
	}
}




/**
 * Non-default formatting reorder callback.
 * Bound to filter, will actually reorder default WP content formatting.
 */
function app_core_late_map_global_formatting_reorder ($content) {
	if (!preg_match('/\[app_/', $content)) return $content;

	remove_filter('the_content', 'wpautop');
	add_filter('the_content', 'wpautop', 20);
	add_filter('the_content', 'shortcode_unautop', 21);

	return $content;
}

function app_core_shortcodes_register ($shortcodes) {

	// Unless manually enabled...
	if (defined('APP_REORDER_DEFAULT_FORMATTING') && APP_REORDER_DEFAULT_FORMATTING) {
		// ... or disabled by some code ...
		if (has_action('the_content', 'wpautop')) {
			if (defined('APP_DEFAULT_FORMATTING_GLOBAL_REORDER') && APP_DEFAULT_FORMATTING_GLOBAL_REORDER) { // If global define is in place, just do this
				// ... move the default formatting functions higher up the chain
				remove_filter('the_content', 'wpautop');
				add_filter('the_content', 'wpautop', 20);
				add_filter('the_content', 'shortcode_unautop', 21);
			} else add_filter('the_content', 'app_core_late_map_global_formatting_reorder', 0); // With no global formatting, do "the_content" filtering bits only. Note the "0"
		}
	}

	include_once( 'shortcodes/class-app-shortcode-confirmation.php' );
	include_once( 'shortcodes/class-app-shortcode-my-appointments.php' );
	include_once( 'shortcodes/class-app-shortcode-services.php' );
	include_once( 'shortcodes/class-app-shortcode-monthly-worker-calendar.php' );
	include_once( 'shortcodes/class-app-shortcode-service-providers.php' );
	include_once( 'shortcodes/class-app-shortcode-schedule.php' );

	$shortcodes['app_worker_montly_calendar'] = 'App_Shortcode_WorkerMontlyCalendar'; // Typo :(
	$shortcodes['app_worker_monthly_calendar'] = 'App_Shortcode_WorkerMonthlyCalendar';
	$shortcodes['app_schedule'] = 'App_Shortcode_WeeklySchedule';
	$shortcodes['app_monthly_schedule'] = 'App_Shortcode_MonthlySchedule';
	$shortcodes['app_pagination'] = 'App_Shortcode_Pagination';
	$shortcodes['app_all_appointments'] = 'App_Shortcode_AllAppointments';
	$shortcodes['app_my_appointments'] = 'App_Shortcode_MyAppointments';
	$shortcodes['app_services'] = 'App_Shortcode_Services';
	$shortcodes['app_service_providers'] = 'App_Shortcode_ServiceProviders';
	$shortcodes['app_login'] = 'App_Shortcode_Login';
	$shortcodes['app_paypal'] = 'App_Shortcode_Paypal';
	$shortcodes['app_confirmation'] = 'App_Shortcode_Confirmation';
	return $shortcodes;
}
add_filter('app-shortcodes-register', 'app_core_shortcodes_register', 1);


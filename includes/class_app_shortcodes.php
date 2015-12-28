<?php
/**
 * Contains the default App_Shortcode descendent implementations.
 */


/**
 * Monthly worker calendar overview.
 */
class App_Shortcode_WorkerMonthlyCalendar extends App_Shortcode {

	public function __construct () {
		$this->_defaults = array(
			'status' => array(
				'value' => 'paid,confirmed',
				'help' => __('Show Appointments with this status (comma-separated list)', 'appointments'),
				'allowed_values' => array('paid', 'confirmed', 'pending', 'completed'),
				'example' => 'paid,confirmed',
			),
			'worker_id' => array(
				'value' => false,
				'help' => __('Show Appointments calendar for service provider with this user ID', 'appointments'),
				'example' => '32',
			),
			'start_at' => array(
				'value' => false,
				'help' => __('Show Appointments calendar for this month. Defaults to current month.', 'appointments'),
				'example' => '2013-07-01',
			)
		);
	}

	public function process_shortcode ($args=array(), $content='') {
		$status = false;
		$args = wp_parse_args($args, $this->_defaults_to_args());

		if (!empty($args['worker_id'])) {
			$args['worker_id'] = $this->_arg_to_int($args['worker_id']);
		} else if (is_user_logged_in()) {
			$worker = wp_get_current_user();
			$args['worker_id'] = $worker->ID;
		} else {
			return $content; // We don't know what to show
		}
		if (!$args['worker_id']) return $content;

		$status = $this->_arg_to_string_list($args['status']);

		$args['start_at'] = !empty($args['start_at'])
			? strtotime($args['start_at'])
			: false
		;
		if (!$args['start_at'] && !empty($_GET['wcalendar']) && is_numeric($_GET['wcalendar'])) {
			$args['start_at'] = (int)$_GET['wcalendar'];
		} else if (!$args['start_at']) {
			$args['start_at'] = current_time('timestamp');
		}

		$appointments = $this->_get_worker_appointments($args['worker_id'], $status, $args['start_at']);
		if (empty($appointments)) return $content;

		return $this->_create_appointments_table($appointments, $args);

		return $content;
	}

	public function get_usage_info () {
		return __('Renders a calendar with appointments assigned to a service provider.', 'appointments');
	}

	private function _get_worker_appointments ($worker_id, $status, $start_at) {
		global $appointments, $wpdb;
		$worker_sql = $status_sql = '';

		$services = appointments_get_worker_services($worker_id);
		$service_ids = !empty($services)
			? array_filter(array_map('intval', wp_list_pluck($services, 'ID')))
			: false
		;
		$worker_sql = !empty($service_ids)
			? $wpdb->prepare('(worker=%d OR service IN(' . join(',', $service_ids) . '))', $worker_id)
			: $wpdb->prepare('worker=%d', $worker_id)
		;

		$status = is_array($status) ? array_map( 'esc_sql', $status) : false;
		$status_sql = $status ? "AND status IN('" . join("','", $status) . "')" : '';

		$first = strtotime(date('Y-m-01', $start_at));
		$last = ($first + (date('t', $first) * 86400 )) - 1;

		$sql = $wpdb->prepare(
			"SELECT * FROM {$appointments->app_table} WHERE {$worker_sql} {$status_sql} AND UNIX_TIMESTAMP(start)>%d AND UNIX_TIMESTAMP(end)<%d ORDER BY start",
			$first, $last
		);
		return $wpdb->get_results($sql);
	}

	private function _create_appointments_table ($scheduled, $args) {
		global $appointments;

		$week_start = $appointments->start_of_week;
		$days = (int)date('t', $args['start_at']);
		$first_dow = (int)date('w', strtotime(date('Y-m-01', $args['start_at'])));
		$last_dow = (int)date('w', strtotime(date('Y-m-' . $days, $args['start_at'])));

		$today = date('Y-m-d', current_time('timestamp'));

		$out = '<div class="app-worker_monthly_calendar-wrapper"><table class="app-worker_monthly_calendar">';

		$out .= $appointments->_get_table_meta_row_monthly('thead', true);

		$out .= '<tbody><tr>';
		if ($first_dow > $week_start) {
			$out .= '<td class="no-left-border" colspan="' . ($first_dow - $week_start) . '">&nbsp;</td>';
		} else if ($first_dow < $week_start) {
			$out .= '<td class="no-left-border" colspan="' . (7 + $first_dow - $week_start) . '">&nbsp;</td>';
		}

		for ($i=1; $i<=$days; $i++) {
			$date = date('Y-m-' . sprintf("%02d", $i), $args['start_at']);
			$current_timestamp = strtotime($date);
			$dow = (int)date('w', strtotime($date));
			$morning = strtotime("{$date} 00:00");

			if ($week_start == $dow) $out .= '</tr><tr>';

			$daily_schedule = '';
			foreach ($scheduled as $app) {
				$app_start = mysql2date('U', $app->start);
				$app_end = mysql2date('U', $app->end);

				if ($app_start < $current_timestamp || $app_end > ($current_timestamp+86400)) continue;
				if (!empty($app->worker) && $app->worker != $args['worker_id']) continue; // Assigned, but not to me

				$app_class = array_filter(array(
					($app->worker == $args['worker_id'] ? 'app-is_mine' : 'app-is_service'),
					"app-status-{$app->status}",
				));

				$duration_unit = __('%dmin', 'appointments');
				$duration = ($app_end - $app_start) / 60.0;
				if ($duration > 59) {
					$duration /= 60.0;
					$duration_unit = (int)$duration < $duration
						? __('%.1fhr', 'appointments')
						: __('%dhr', 'appointments')
					;
				}

				$daily_schedule .= '<div class="app-scheduled_appointment ' . join(' ', $app_class) . '">' .
					date_i18n($appointments->time_format, $app_start) . ' <span class="app-end_time">- ' . date_i18n($appointments->time_format, $app_end) . '</span>' .
					'<div class="app-scheduled_appointment-info" style="display:none">' .
						'<ul>' .
							'<li><b>' . __('Start', 'appointments') . '</b>' .
								' ' . date_i18n($appointments->datetime_format, $app_start) . '</li>' .
							'<li><b>' . __('Duration', 'appointments') . '</b>' .
								' ' . sprintf($duration_unit, $duration) . '</li>' .
							'<li><b>' . __('Status', 'appointments') . '</b>' .
								' ' . App_Template::get_status_name($app->status) . '</dd>' .
							'<li><b>' . __('Client', 'appointments') . '</b>' .
								' ' . $appointments->get_client_name($app->ID) . '</li>' .
						'</ul>' .
					'</div>' .
				'</div>';
			}

			$out .= '<td class="' . ($today == $date ? 'app-today' : '') . '" title="'.date_i18n($appointments->date_format, $morning).'">' .
				"<p>{$i}</p>" .
				$daily_schedule .
			'</td>';
		}

		$ret = '';
		if (0 == (6 - $last_dow + $week_start)) {
			$ret .= '</tr>';
		} else if ($last_dow > $week_start) {
			$ret .= '<td class="no-right-border" colspan="' . (6 - $last_dow + $week_start) . '">&nbsp;</td></tr>';
		} else if ($last_dow + 1 == $week_start) {
			$ret .= '</tr>';
		} else {
			$ret .= '<td class="no-right-border" colspan="' . (6 + $last_dow - $week_start) . '">&nbsp;</td></tr>';
		}

		$out .= '</tbody></table> <div class="app-worker_monthly_calendar-out"></div> </div>';

		$appointments->add2footer(
'
(function () {
$(".app-scheduled_appointment")
	.find(".app-scheduled_appointment-info").hide().end()
	.on("click", function () {
		var $me = $(this),
			$out = $me.parents(".app-worker_monthly_calendar-wrapper").find(".app-worker_monthly_calendar-out"),
			$info = $me.find(".app-scheduled_appointment-info")
		;
		$out.empty().hide().append($info.html()).slideDown("slow");
		return false;
	})
;
})();
'
		);

		return $out;
	}
}
// Special-case shortcode for typo handling
class App_Shortcode_WorkerMontlyCalendar extends App_Shortcode_WorkerMonthlyCalendar {
	public function register ($key) {
		$this->_key = $key;
		add_shortcode($key, array($this, "process_shortcode"));
	}
}


/**
 * Weekly schedule calendar shortcode.
 */
class App_Shortcode_WeeklySchedule extends App_Shortcode {
	public function __construct () {
		$this->_defaults = array(
			'title' => array(
				'value' => __('<h3>Our schedule from START to END</h3>', 'appointments'),
				'help' => __('Text that will be displayed as the schedule title. Placeholders START and END will be automatically replaced by their real values.', 'appointments'),
				'example' => __('Our schedule from START to END', 'appointments'),
			),
			'logged' => array(
				'value' => __('Click on a free time slot to apply for an appointment.', 'appointments'),
				'help' => __('Text that will be displayed after the title only to the clients who are logged in or you don\'t require a login.', 'appointments'),
				'example' => __('Click on a free time slot to apply for an appointment.', 'appointments'),
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
				'help' => __('Number of weeks to add to the schedule to use for preceding weeks\' schedules. Enter 1 for next week, 2 for the other week, so on. Default: "0" (Current week)', 'appointments'),
				'example' => 1,
			),
			'_noscript' => array(
				'value' => false,
			),
			'date' => array(
				'value' => false,
				'help' => __('Normally calendar starts from the current week. If you want to force it to start from a certain date, enter that date here. Most date formats are supported, but YYYY-MM-DD is recommended. Notes: 1) This value will also affect other subsequent calendars on the same page. 2) Date value will not change starting day of week. It is sufficient to enter a date inside the week. Default: "0" (Current week)', 'appointments'),
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

		);
	}

	public function process_shortcode ($args=array(), $content='') {
		global $appointments;

		extract(wp_parse_args($args, $this->_defaults_to_args()));

		// Force service
		if ( $service ) {
			// Check if such a service exists
			if ( !$appointments->get_service( $service ) )
				return;
			$_REQUEST["app_service_id"] = $service;
		}

		$appointments->get_lsw(); // This should come after Force service

		if ( $worker ) {
			// Check if such a worker exists
			if ( ! appointments_is_worker( $worker ) )
				return;
			$_REQUEST["app_provider_id"] = $worker;
		}
		else if ( $single_worker = $appointments->is_single_worker( $appointments->service ) ) {
			// Select the only provider if that is the case
			$_REQUEST["app_provider_id"] = $single_worker;
			$worker = $single_worker;
		}

		// Force a date
		if ( $date && !isset( $_GET["wcalendar"] ) ) {
			$time = strtotime( $date, $appointments->local_time ) + ($add * 7 * 86400) ;
			$_GET["wcalendar"] = $time;
		}
		else {
			if ( isset( $_GET["wcalendar"] ) && (int)$_GET['wcalendar'] )
				$time = (int)$_GET["wcalendar"] + ($add * 7 * 86400) ;
			else
				$time = $appointments->local_time + ($add * 7 * 86400);
		}

		$start_of_calendar = $appointments->sunday( $time ) + $appointments->start_of_week * 86400;

		if ( '' != $title )
			$title = str_replace(
					array( "START", "END" ),
					array(
						date_i18n($appointments->date_format, $start_of_calendar ),
						date_i18n($appointments->date_format, $start_of_calendar + 6*86400 )
						),
					$title
			);
		else
			$title = '';

		$has_worker = !empty($appointments->worker) || !empty($worker);

		$c  = '';
        $c .= '<div class="appointments-wrapper">';

        if (!$has_worker && !empty($require_provider)) {
			$c .= !empty($required_message)
				? $required_message
				: __('Please, select a service provider.', 'appointments')
			;
 		} else {
	        $c .= $title;

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
	 		$c .= $appointments->get_weekly_calendar($time, $class, $long);

			$c .= '</div>';
		}
		$c .= '</div>'; // .appointments-wrapper

		$script = '';

		if (!$_noscript) $appointments->add2footer( $script );

		return $c;
	}

	public function get_usage_info () {
		return __('Creates a weekly table whose cells are clickable to apply for an appointment.', 'appointments');
	}
}

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
		);
	}

	public function process_shortcode ($args=array(), $content='') {
		global $current_user, $appointments;

		extract(wp_parse_args($args, $this->_defaults_to_args()));


		// Force service
		if ( $service ) {
			// Check if such a service exists
			if ( !$appointments->get_service( $service ) )
				return;
			$_REQUEST["app_service_id"] = $service;
		}

		$appointments->get_lsw(); // This should come after Force service

		// Force worker or pick up the single worker
		if ( $worker ) {
			// Check if such a worker exists
			if (! appointments_is_worker($worker)) return;
			$_REQUEST["app_provider_id"] = $worker;
		}
		else if ( $single_worker = $appointments->is_single_worker( $appointments->service ) ) {
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
				$appointments->get_worker_name(
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

		$c  = '';
        $c .= '<div class="appointments-wrapper">';

		if (!$has_worker && !empty($require_provider)) {
			$c .= !empty($required_message)
				? $required_message
				: __('Please, select a service provider.', 'appointments')
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
		global $wpdb, $appointments;
		extract(wp_parse_args($args, $this->_defaults_to_args()));

		$statuses = explode( ',', $status );

		if ( !is_array( $statuses ) || empty( $statuses ) )
			return;

		if ( !trim( $order_by ) )
			$order_by = 'start';

		$stat = '';
		foreach ( $statuses as $s ) {
			// Allow only defined stats
			if ( array_key_exists( trim( $s ), App_Template::get_status_names() ) )
				$stat .= " status='". trim( $s ) ."' OR ";
		}
		$stat = rtrim( $stat, "OR " );

		$results = $wpdb->get_results( "SELECT * FROM " . $appointments->app_table . " WHERE (".$stat.") ORDER BY ".$appointments->sanitize_order_by( $order_by )." " );

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
				$ret .= $appointments->get_worker_name( $r->worker ) . '</td>';
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
		if ( $_tablesorter && file_exists( $appointments->plugin_dir . '/js/jquery.tablesorter.min.js' ) )
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
 * Services dropdown list shortcode.
 */
class App_Shortcode_Services extends App_Shortcode {
	public function __construct () {
		$this->_defaults = array(
			'select' => array(
				'value' => __('Please select a service:', 'appointments'),
				'help' => __('Text above the select menu. Default: "Please select a service"', 'appointments'),
				'example' => __('Please select a service:', 'appointments'),
			),
			'show' => array(
				'value' => __('Show available times', 'appointments'),
				'help' => __('Button text to show the results for the selected. Default: "Show available times"', 'appointments'),
				'example' => __('Show available times', 'appointments'),
			),
			'description' => array(
				'value' => 'excerpt',
				'help' => __('WSelects which part of the description page will be displayed under the dropdown menu when a service is selected . Selectable values are "none", "excerpt", "content". Default: "excerpt"', 'appointments'),
				'allowed_values' => array('none', 'excerpt', 'content',),
				'example' => 'content',
			),
			'thumb_size' => array(
				'value' => '96,96',
				'help' => __('Inserts the post thumbnail if page has a featured image. Selectable values are "none", "thumbnail", "medium", "full" or a 2 numbers separated by comma representing width and height in pixels, e.g. 32,32. Default: "96,96"', 'appointments'),
				'example' => 'thumbnail',
			),
			'thumb_class' => array(
				'value' => 'alignleft',
				'help' => __('css class that will be applied to the thumbnail. Default: "alignleft"', 'appointments'),
				'example' => 'my-class',
			),
			'autorefresh' => array(
				'value' => 0,
				'help' => __('If set as 1, Show button will not be displayed and page will be automatically refreshed as client changes selection. Note: Client cannot browse through the selections and thus check descriptions on the fly (without the page is refreshed). Default: "0" (disabled)', 'appointments'),
				'example' => '1',
			),
			'order_by' => array(
				'value' => 'ID',
				'help' => __('Sort order of the services. Possible values: ID, name, duration, price. Optionally DESC (descending) can be used, e.g. "name DESC" will reverse the order. Default: "ID"', 'appointments'),
				'example' => 'ID',
			),
			'worker' => array(
				'value' => 0,
				'help' => __('In some cases, you may want to display services which are given only by a certain provider. In that case enter provider ID here. Default: "0" (all defined services). Note: Multiple selections are not allowed.', 'appointments'),
				'example' => '12',
			),
			'_noscript' => array('value' => 0),

		);
	}

	public function get_usage_info () {
		return __('Creates a dropdown menu of available services.', 'appointments');
	}

	public function process_shortcode ($args=array(), $content='') {
		extract(wp_parse_args($args, $this->_defaults_to_args()));

		global $wpdb, $appointments;
		$appointments->get_lsw();

		if (!trim($order_by)) $order_by = 'ID';

		if ($worker) {
			$services = appointments_get_worker_services( $worker );
			// Find first service by this worker
			$fsby = $services[0]->ID;
			if ( $fsby && !@$_REQUEST['app_service_id'] ) {
				$_REQUEST['app_service_id'] = $fsby; // Set this as first service
				$appointments->get_lsw(); // Update
			}
			// Re-sort worker services
			if (!empty($services) && !empty($order_by) && 'ID' !== $order_by) $services = $this->_reorder_services($services, $order_by);
		} else {
			$services = $appointments->get_services( $order_by );
		}

		$services = apply_filters( 'app_services', $services );

		// If there are no workers do nothing
		if (!$services || empty($services)) return;

		$script ='';
		$s = '';
		$e = '';

		$s .= '<div class="app_services">';
		$s .= '<div class="app_services_dropdown">';
		$s .= '<div class="app_services_dropdown_title">';
		$s .= $select;
		$s .= '</div>';
		$s .= '<div class="app_services_dropdown_select">';
		$s .= '<select name="app_select_services" class="app_select_services">';
		if ($services) {
			foreach ($services as $service) {
				$service_description = '';
				// Check if this is the first service, so it would be displayed by default
				if ($service->ID == $appointments->service) {
					$d = '';
					$sel = ' selected="selected"';
				} else {
					$d = ' style="display:none"';
					$sel = '';
				}
				// Add options
				$s .= '<option value="'.$service->ID.'"'.$sel.'>'. stripslashes( $service->name ) . '</option>';
				// Include excerpts
				$e .= '<div '.$d.' class="app_service_excerpt" id="app_service_excerpt_'.$service->ID.'" >';
				// Let addons modify service page
				$page = apply_filters('app_service_page', $service->page, $service->ID);
				switch ($description) {
					case 'none':
						break;
					case 'excerpt':
						$service_description .= $appointments->get_excerpt($page, $thumb_size, $thumb_class, $service->ID); 
						break;
					case 'content':
						$service_description .= $appointments->get_content($page, $thumb_size, $thumb_class, $service->ID); 
						break;
					default:
						$service_description .= $appointments->get_excerpt($page, $thumb_size, $thumb_class, $service->ID); 
						break;
				}
				$e .= apply_filters('app-services-service_description', $service_description, $service, $description) . '</div>';
			}
		}
		$s .= '</select>';
		$s .= '<input type="button" class="app_services_button" value="'.$show.'">';
		$s .= '</div>';
		$s .= '</div>';

		$s .= '<div class="app_service_excerpts">';
		$s .= $e;
		$s .= '</div>';
		$s .= '</div>';

		$wcalendar = isset($_GET['wcalendar']) && (int)$_GET['wcalendar']
			? (int)$_GET['wcalendar']
			: false
		;

		// First remove these parameters and add them again to make wcalendar appear before js variable
		$href = add_query_arg( array( "wcalendar"=>false, "app_provider_id" => false, "app_service_id" => false ) );
		$href = apply_filters( 'app_service_href', add_query_arg( array( "wcalendar"=>$wcalendar, "app_service_id" => "__selected_service__" ), $href ) );

		if ( $autorefresh ) {
			$script .= "$('.app_services_button').hide();";
		}

		$script .= "$('.app_select_services').change(function(){";
		$script .= "var selected_service=$('.app_select_services option:selected').val();";
		$script .= "if (typeof selected_service=='undefined' || selected_service===null){";
		$script .= "selected_service=" . (int)$appointments->get_first_service_id() . ";";
		$script .= "}";
		$script .= "$('.app_service_excerpt').hide();";
		$script .= "$('#app_service_excerpt_'+selected_service).show();";
		if ( $autorefresh ) {
			$script .= "window.location.href='" . $this->_js_esc_url($href) . "'.replace(/__selected_service__/, selected_service);";
		}
		$script .= "});";

		$script .= "$('.app_services_button').click(function(){";
		$script .= "var selected_service=$('.app_select_services option:selected').val();";
		$script .= "window.location.href='" . $this->_js_esc_url($href) . "'.replace(/__selected_service__/, selected_service);";
		$script .= "});";

		if (!$_noscript) $appointments->add2footer($script);

		return $s;
	}

	/**
	 * Escape the URL, but convert back search query entities (i.e. ampersands)
	 *
	 * @param string $raw Raw URL to parse
	 *
	 * @return string Usable URL
	 */
	private function _js_esc_url ($raw='') {
		$url = esc_url($raw);
		$parts = explode('?', $url);
		
		if (empty($parts[1])) return $url;
		if (false === strpos($parts[1], '#038;') && false === strpos($parts[1], '&amp;')) return $url;

		$parts[1] = preg_replace('/&(#038|amp);/', '&', $parts[1]);

		return join('?', $parts);
	}

	/**
	 * Sort the services when we can't do so via SQL
	 */
	private function _reorder_services ($services, $order) {
		if (empty($services)) return $services;
		list($by,$direction) = explode(' ', trim($order), 2);

		$by = trim($by) ? trim($by) : 'ID';
		$by = in_array($by, array('ID', 'name', 'capacity', 'duration', 'price', 'page'))
			? $by
			: 'ID'
		;

		$direction = trim($direction) ? strtoupper(trim($direction)) : 'ASC';
		$direction = in_array($direction, array('ASC', 'DESC'))
			? $direction
			: 'ASC'
		;

		$comparator = 'ASC' === $direction
			? create_function('$a, $b', "return strnatcasecmp(\$a->{$by}, \$b->{$by});")
			: create_function('$a, $b', "return strnatcasecmp(\$b->{$by}, \$a->{$by});")
		;
		usort($services, $comparator);

		return $services;
	}
}

/**
 * Service providers shortcode list/dropdown.
 */
class App_Shortcode_ServiceProviders extends App_Shortcode {
	public function __construct () {
		$this->_defaults = array(
			'select' => array(
				'value' => __('Please choose a service provider:', 'appointments'),
				'help' => __('Text above the select menu. Default: "Please select a service"', 'appointments'),
				'example' => __('Please choose a service provider:', 'appointments'),
			),
			'empty_option' => array(
				'value' => __('No preference', 'appointments'),
				'help' => __('Empty option label for the selection', 'appointments'),
				'example' => __('Please, select', 'appointments'),
			),
			'show' => array(
				'value' => __('Show available times', 'appointments'),
				'help' => __('Button text to show the results for the selected. Default: "Show available times"', 'appointments'),
				'example' => __('Show available times', 'appointments'),
			),
			'description' => array(
				'value' => 'excerpt',
				'help' => __('Selects which part of the bio page will be displayed under the dropdown menu when a service provider is selected . Selectable values are "none", "excerpt", "content". Default: "excerpt"', 'appointments'),
				'allowed_values' => array('none', 'excerpt', 'content',),
				'example' => 'content',
			),
			'thumb_size' => array(
				'value' => '96,96',
				'help' => __('Inserts the post thumbnail if page has a featured image. Selectable values are "none", "thumbnail", "medium", "full" or a 2 numbers separated by comma representing width and height in pixels, e.g. 32,32. Default: "96,96"', 'appointments'),
				'example' => 'thumbnail',
			),
			'thumb_class' => array(
				'value' => 'alignleft',
				'help' => __('css class that will be applied to the thumbnail. Default: "alignleft"', 'appointments'),
				'example' => 'my-class',
			),
			'autorefresh' => array(
				'value' => 0,
				'help' => __('If set as 1, Show button will not be displayed and page will be automatically refreshed as client changes selection. Note: Client cannot browse through the selections and thus check descriptions on the fly (without the page is refreshed). Default: "0" (disabled)', 'appointments'),
				'example' => '1',
			),
			'order_by' => array(
				'value' => 'ID',
				'help' => __('Sort order of the service providers. Possible values: ID, name. Optionally DESC (descending) can be used, e.g. "name DESC" will reverse the order. Default: "ID"', 'appointments'),
				'example' => 'ID',
			),
			'service' => array(
				'value' => 0,
				'help' => __('In some cases, you may want to force to display providers who give only a certain service. In that case enter service ID here. Default: "0" (list is determined by services dropdown). Note: Multiple selections are not allowed.', 'appointments'),
				'example' => '12',
			),
			'require_service' => array(
				'value' => 0,
				'help' => __('Do not show service provider selection at all until the service has been previously selected.', 'appointments'),
				'example' => '1',
			),
			'_noscript' => array('value' => 0),

		);
	}

	public function get_usage_info () {
		return __('Creates a dropdown menu of available service providers.', 'appointments');
	}

	public function process_shortcode ($args=array(), $content='') {
		extract(wp_parse_args($args, $this->_defaults_to_args()));

		if (!empty($require_service) && empty($service) && empty($_REQUEST['app_service_id'])) return $content;

		global $wpdb, $appointments;
		$appointments->get_lsw();

		if ( !trim( $order_by ) )
			$order_by = 'ID';


		if ( !$service ) {
			if ( 0 == $appointments->service )
				$workers = $appointments->get_workers( $order_by );
			else
				$workers = $appointments->get_workers_by_service( $appointments->service, $order_by ); // Select only providers that can give this service
		}
		else
			$workers = $appointments->get_workers_by_service( $service, $order_by );

		$workers = apply_filters( 'app_workers', $workers );

		// If there are no workers do nothing
		if ( !$workers || empty( $workers) )
			return;

		$script ='';
		$s = $e = '';

		$s .= '<div class="app_workers">';
		$s .= '<div class="app_workers_dropdown">';
		$s .= '<div class="app_workers_dropdown_title">';
		$s .= $select;
		$s .= '</div>';
		$s .= '<div class="app_workers_dropdown_select">';
		$s .= '<select name="app_select_workers" class="app_select_workers">';
		// Do not show "Anyone" if there is only ONE provider
		if ( 1 != count( $workers ) )
			$s .= '<option value="0">'. $empty_option . '</option>';

		foreach ( $workers as $worker ) {
			$worker_description = '';
			if ( $appointments->worker == $worker->ID || 1 == count( $workers ) ) {
				$d = '';
				$sel = ' selected="selected"';
			}
			else {
				$d = ' style="display:none"';
				$sel = '';
			}
			$s .= '<option value="'.$worker->ID.'"'.$sel.'>'. $appointments->get_worker_name( $worker->ID )  . '</option>';
			// Include excerpts
			$e .= '<div '.$d.' class="app_worker_excerpt" id="app_worker_excerpt_'.$worker->ID.'" >';
			// Let addons modify worker bio page
			$page = apply_filters( 'app_worker_page', $worker->page, $worker->ID );
			switch ( $description ) {
				case 'none'		:		break;
				case 'excerpt'	:		$worker_description .= $appointments->get_excerpt( $page, $thumb_size, $thumb_class, $worker->ID ); break;
				case 'content'	:		$worker_description .= $appointments->get_content( $page, $thumb_size, $thumb_class, $worker->ID ); break;
				default			:		$worker_description .= $appointments->get_excerpt( $page, $thumb_size, $thumb_class, $worker->ID ); break;
			}
			$e .= apply_filters('app-workers-worker_description', $worker_description, $worker, $description) . '</div>';
		}

		$s .= '</select>';
		$s .= '<input type="button" class="app_workers_button" value="'.$show.'">';
		$s .= '</div>';
		$s .= '</div>';
		$s .= '<div class="app_worker_excerpts">';
		$s .= $e;
		$s .= '</div>';

		$s .= '</div>';
		if ( isset( $_GET['wcalendar'] ) && (int)$_GET['wcalendar'] )
			$wcalendar = (int)$_GET['wcalendar'];
		else
			$wcalendar = false;
		// First remove these parameters and add them again to make wcalendar appear before js variable
		$href = add_query_arg( array( "wcalendar"=>false, "app_provider_id" =>false ) );
		$href = apply_filters( 'app_worker_href', add_query_arg( array( "wcalendar"=>$wcalendar, "app_provider_id" => "__selected_worker__" ), $href ) );

		if ( $autorefresh ) {
			$script .= "$('.app_workers_button').hide();";
		}
		$script .= "$('.app_select_workers').change(function(){";
		$script .= "var selected_worker=$('.app_select_workers option:selected').val();";
		$script .= "if (typeof selected_worker=='undefined' || selected_worker==null){";
		$script .= "selected_worker=0;";
		$script .= "}";
		$script .= "$('.app_worker_excerpt').hide();";
		$script .= "$('#app_worker_excerpt_'+selected_worker).show();";
		if ( $autorefresh ) {
			$script .= "var redirection_url='" . $href . "'.replace(/__selected_worker__/, selected_worker) + (!!parseInt(selected_worker, 10) ? '#app_worker_excerpt_'+selected_worker : '');";
			$script .= "window.location.href=redirection_url;";
		}
		$script .= "});";

		$script .= "$('.app_workers_button').click(function(){";
		$script .= "var selected_worker=$('.app_select_workers option:selected').val();";
		$script .= "var redirection_url='" . $href . "'.replace(/__selected_worker__/, selected_worker) + (!!parseInt(selected_worker, 10) ? '#app_worker_excerpt_'+selected_worker : '');";
		$script .= "window.location.href=redirection_url;";
		$script .= "});";

		if (!$_noscript) $appointments->add2footer( $script );

		return $s;
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


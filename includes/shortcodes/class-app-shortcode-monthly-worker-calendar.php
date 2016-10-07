<?php
/**
 * Monthly worker calendar overview.
 */
class App_Shortcode_WorkerMonthlyCalendar extends App_Shortcode {

	public function __construct () {
		$this->name = __( 'Worker Monthly Calendar', 'appointments' );
	}

	public function get_defaults() {
		$_workers = appointments_get_workers();
		$workers = array(
			array( 'text' => __( 'Any provider', 'appointments' ), 'value' => '' )
		);
		foreach ( $_workers as $worker ) {
			/** @var Appointments_Worker $worker */
			$workers[] = array( 'text' => $worker->get_name(), 'value' => $worker->ID );
		}

		return array(
			'status' => array(
				'name' => _x( 'Status', 'Worker Monthly Calendar Shortcode status field', 'appointments' ),
				'value' => 'paid,confirmed',
				'help' => __( 'Which status(es) will be included. Possible values: paid, confirmed, completed, pending, removed, reserved or combinations of them separated with comma.', 'appointments' ),
				'type' => 'text'
			),
			'worker_id' => array(
				'name' => __( 'Provider', 'appointments' ),
				'value' => '',
				'help' => __('Show Appointments calendar for service provider with this user ID', 'appointments'),
				'type' => 'select',
				'options' => $workers,
			),
			'start_at' => array(
				'name' => __( 'Start At', 'appointments' ),
				'value' => '',
				'help' => sprintf( __('Show Appointments calendar for this month. Defaults to current month. Example: %s', 'appointments'), date( 'Y-m-01', current_time( 'timestamp' ) ) ),
				'type' => 'text',
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
		$args = array(
			'worker' => $worker_id,
			'start' => date( 'Y-m-d H:i:s', $start_at )
		);

		$status = is_array( $status ) ? $status : false;
		if ( $status ) {
			$args['status'] = $status;
		}

		return appointments_get_month_appointments( $args );
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
		$this->name = '';
		$this->_key = $key;
		add_shortcode($key, array($this, "process_shortcode"));
	}
}
<?php
/*
Plugin Name: Show Scheduled Users
Description: Shows scheduled user names for unavailable appointment schedule segments.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Schedule
Author: WPMU DEV
*/

class App_Schedule_ShowUsers {

	const POST_TYPE = 'page';
	private $_data;

	private function __construct () {}

	public static function serve () {
		$me = new App_Schedule_ShowUsers;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('plugins_loaded', array($this, 'initialize'));

		add_filter('app-schedule_cell-title', array($this, 'process_cell_title'), 10, 5);
	}

	public function initialize () {
		global $appointments;
		$this->_data = $appointments->options;
	}

	public function process_cell_title ($title, $is_busy, $start, $end, $schedule_key) {
		if (!$is_busy) return $title;
		$customers = $this->_get_appointments_by_interval($start, $end, $schedule_key);
		if (empty($customers)) return $title;
		$names = join("\n", array_unique(wp_list_pluck($customers, 'name')));
		return $names;
	}

	private function _get_appointments_by_interval ($start, $end, $schedule_key) {
		$apps = wp_cache_get('app-show_users-' . $schedule_key);
		if (!$apps) $apps = $this->_get_appointments_for_scheduled_interval($schedule_key);
		if (!$apps) return false;

		$ret = array();
		$period = new App_Period($start, $end);
		foreach ($apps as $app) {
			//if (mysql2date('U',$app->start) >= $start && mysql2date('U',$app->end) <= $end) $ret[] = $app;
			if ($period->contains($app->start, $app->end)) $ret[] = $app;
		}
		return $ret;
	}

	private function _get_appointments_for_scheduled_interval ($schedule_key) {
		$data = explode('x', $schedule_key);
		if (count($data) != 2) {
			$interval_start = current_time('timestamp');
			$interval_end = strtotime('next month', $interval_start);
		} else {
			$interval_start = $data[0];
			$interval_end = $data[1];
		}

		global $appointments, $wpdb;
		$table = $appointments->app_table;
		$where = "AND (status='pending' OR status='paid' OR status='confirmed' OR status='reserved')";

		if ($appointments->service) {
			$where .= " AND service={$appointments->service}";
		}
		if ($appointments->worker) {
			$where .= " AND worker={$appointments->worker}";
		}

		$sql = "SELECT name,user,start,end FROM {$table} WHERE UNIX_TIMESTAMP(start)>'{$interval_start}' AND UNIX_TIMESTAMP(end)<'{$interval_end}' {$where}";
		$res = $wpdb->get_results($sql);
		wp_cache_set('app-show_users-' . $schedule_key, $res);
		return $res;
	}

}
App_Schedule_ShowUsers::serve();
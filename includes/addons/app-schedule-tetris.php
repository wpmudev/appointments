<?php
/*
Plugin Name: Tetris Mode
Description: Adjust schedule time slots dynamically to avoid free gaps after booked appointments and breaks.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Schedule
Author: WPMU DEV
*/

class App_Schedule_Tetris {

	private function __construct () {}

	public static function serve () {
		$me = new App_Schedule_Tetris;
		$me->_add_hooks();
	}

	private function _add_hooks () {
        add_filter('app_next_time_step', array($this,'filter_next_step'), 99, 3);
	}

    function filter_next_step($next_step, $current_time, $step){
        global $appointments;
        $week = date("W", $current_time);

        // If a specific worker is selected, we will look at his schedule first.
        if (0 != $appointments->worker) {
            $apps = $appointments->get_reserve_apps_by_worker($appointments->location, $appointments->worker, $week);
            if ($apps) {
                foreach ($apps as $app) {
                    $app_start = is_numeric($app->start) ? $app->start : strtotime($app->start);
                    $app_end = is_numeric($app->end) ? $app->end : strtotime($app->end);
                    if ( ($current_time > $app_start && $current_time < $app_end) ){
                        $next_step = $app_end;
                        break;
                    } else if ( ($next_step > $app_end) && ( ($next_step - $app_end) <  $step - 1)){
                        $next_step = $app_end;
                        break;
                    }
                }
            }
        }

        //Check for breaks collisions.
        if ( $appointments->is_break( $next_step, $next_step + 60 ) ){
            $break_object = $this->get_break_data($next_step, $next_step + 60);
            $break_end = $break_object['end'];
            $next_step = $break_end;
        } else if ( !$appointments->is_service_possible( $current_time, $current_time + $step, $appointments->get_capacity() ) ){
            $check_step = 60 * 5;
            for($i = $current_time + $check_step; $i < $current_time + $step - $check_step; $i += $check_step ){
                $ccend = $i + 60;
                if ( $appointments->is_break( $i, $ccend ) ){
                    $break_object = $this->get_break_data($i, $ccend);
                    $break_end = $break_object['end'];
                    $next_step = $break_end;
                    break;
                }
            }
        }

        return $next_step;
    }

    function get_break_data( $cellstart, $cce, $w=0 ) {
        global $appointments;

        // A worker can be forced.
        if ( !$w )
            $w = $appointments->worker;

        // Try to get cached preprocessed hours first.
        $days = wp_cache_get('app-break_times-for-' . $w);
        if (!$days) {
            // Preprocess and cache workinghours.
            $result_days = $appointments->get_work_break($appointments->location, $w, 'closed');
            if ($result_days && is_object($result_days) && !empty($result_days->hours)) $days = maybe_unserialize($result_days->hours);
            if ($days) wp_cache_set('app-break_times-for-' . $w, $days);
        }
        if (!is_array($days) || empty($days)) return false;
        // What is the name of this day?.
        $this_days_name = date("l", $cellstart );
        // This days midnight.
        $this_day = date("d F Y", $cellstart );


        foreach( $days as $day_name=>$day ) {
            $break_data = array();
            if ( $day_name == $this_days_name && isset( $day["active"] ) && 'yes' == $day["active"] ) {
                $end = $appointments->to_military( $day["end"] );

                if ( '00:00' == $end )
                    $end = '24:00';

                $break_start = strtotime( $this_day. " ". $appointments->to_military( $day["start"] ), $appointments->local_time );
                $break_end = $appointments->str2time( $this_day, $end );
                $break_duration = $break_end - $break_start;
                $break_data['start'] = $break_start;
                $break_data['end'] = $break_end;
                $break_data['duration'] = $break_duration;
                if ( $cellstart >= $break_start && $cellstart < $break_end ) {
                    return $break_data;
                }

            } else if ($day_name == $this_days_name && isset($day["active"]) && is_array($day["active"])) {
                foreach ($day["active"] as $idx => $active) {
                    $end = $appointments->to_military( $day["end"][$idx] );

                    if ('00:00' == $end){
                        $end = '24:00';
                    }

                    $break_start = strtotime( $this_day. " ". $appointments->to_military( $day["start"][$idx] ), $appointments->local_time );
                    $break_end = $appointments->str2time( $this_day, $end );
                    $break_duration = $break_end - $break_start;
                    $break_data['start'] = $break_start;
                    $break_data['end'] = $break_end;
                    $break_data['duration'] = $break_duration;
                    if ( $cellstart >= $break_start && $cellstart < $break_end ) {
                        return $break_data;
                    }

                }
            }
        }
        return false;
    }

}
App_Schedule_Tetris::serve();
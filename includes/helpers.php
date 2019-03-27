<?php


function appointments_session_start() {

}

function appointments_session_id() {

}

/**
 * Return an Appointments table name
 *
 * @param string $table Table slug
 *
 * @return mixed
 */
function appointments_get_table( $table ) {
	global $wpdb;

	$tables = array(
		'services' => $wpdb->prefix . 'app_services',
		'workers' => $wpdb->prefix . 'app_workers',
		'wh' => $wpdb->prefix . 'app_working_hours',
		'exceptions' => $wpdb->prefix . 'app_exceptions',
		'appointments' => $wpdb->prefix . 'app_appointments',
		'appmeta' => $wpdb->prefix . 'app_appointmentmeta',
		'transactions' => $wpdb->prefix . 'app_transactions',
	);

	return isset( $tables[ $table ] ) ? $tables[ $table ] : false;
}

function appointments_get_db_version() {
	return get_option( 'app_db_version' );
}

function appointments_delete_timetables_cache() {
	$appointments = appointments();
	$appointments->timetables = array();
	delete_transient( 'app_timetables' );
}

/**
 * @since 2.2.1 Added `hide_today` argument.
 * @since 2.3.2 Added `worker_id` argument.
 */
function appointments_get_timetable( $day_start, $capacity, $schedule_key = false, $hide_today = false, $worker_id = 0 ) {
	global $appointments;
	return $appointments->get_timetable( $day_start, $capacity, $schedule_key, $hide_today, $worker_id );
}

function appointments_get_capacity() {
	global $appointments;
	return $appointments->get_capacity();
}

function appointments_clear_cache() {
	wp_cache_flush();
	appointments_delete_timetables_cache();
}

/**
 * Return Filename
 *
 * @param string $name Filename
 *
 * @param boolean $from_front If called from front end and want to use in hook
 *
 * @return mixed
 */

function appointments_get_view_path( $name, $from_front = false ) {

	$file = appointments_plugin_dir() . 'admin/views/' . $name . '.php';
	$file = apply_filters( 'appointments_admin_view_path', $file, $from_front );
	if ( is_file( $file ) ) {
		return $file;
	}

	return false;

}

/**
 * Returns the date and time format.
 *
 * If $type = 'full' it will return date + time format
 *
 * @param string $type full|time|date
 *
 * @return string
 */
function appointments_get_date_format( $type = 'full' ) {
	$date_format = get_option( 'date_format' );
	$date_format = empty( $date_format ) ? 'Y-m-d' : $date_format;
	$time_format = get_option( 'time_format' );
	$time_format = empty( $time_format ) ? 'H:i' : $time_format;
	if ( 'date' === $type ) {
		return $date_format;
	} elseif ( 'time' === $type ) {
		return $time_format;
	}

	return $date_format . ' ' . $time_format;
}


/**
 * Return the number of the day when the week start for this site
 * Sun = 7
 * Mon = 1
 */
function appointments_week_start() {
	$s = get_option( 'start_of_week' );
	return false !== $s ? $s : 1;
}

/**
 * Return the correspondence between the weekday number and its string representation
 */
function appointments_number_to_weekday( $weekday ) {
	switch ( $weekday ) {
		case 1: { return 'Monday'; break; }
		case 2: { return 'Tuesday'; break; }
		case 3: { return 'Wednesday'; break; }
		case 4: { return 'Thursday'; break; }
		case 5: { return 'Friday'; break; }
		case 6: { return 'Saturday'; break; }
		default: { return 'Sunday'; break; }
	}
}


/**
 * return a list of days => time slots for the weekly calendar
 *
 * @param bool $now
 * @return array
 */
function appointments_get_weekly_schedule_slots( $now = false, $service_id = 0, $worker_id = 0, $location_id = 0 ) {
	$appointments = appointments();

	if ( ! $now ) {
		$now = current_time( 'timestamp' );
	}

	// Get the start/end hours
	$hour_start = 8;
	$hour_end = 18;
	if ( $min_max = $appointments->min_max_wh() ) {
		$hour_start = $min_max['min'];
		$hour_end = $min_max['max'];
	}

	if ( $hour_start > $hour_end ) {
		$hour_start = $min_max['max'];
		$hour_end = $min_max['min'];
	}

	$hour_start = apply_filters( 'app_schedule_starting_hour', $hour_start, $now, 'week' );
	$hour_end = apply_filters( 'app_schedule_ending_hour', $hour_end, $now, 'week' );

	$step = $appointments->get_min_time() * MINUTE_IN_SECONDS; // Timestamp increase interval to one cell below
	if ( ! apply_filters( 'appointments_use_legacy_duration_end_time', false ) ) {
		$service = appointments_get_service( $service_id );
		if ( $service ) {
			$step = $service->duration * MINUTE_IN_SECONDS;
		}
	}

	// Get the last day that was a start of week. We'll start from there
	$week_start = appointments_week_start();
	$week_start_string = appointments_number_to_weekday( $week_start );

	$now_weekday = absint( date( 'N', $now ) );

	// By default we'll start the schedule by today
	$day_start = date( 'Y-m-d', $now );
	if ( $week_start != $now_weekday ) {
		// Today is not the same day that the week start
		// Get the latest start weekday
		$day_start = date( 'Y-m-d', strtotime( 'Last ' . $week_start_string, $now ) );
	}

	// Timestamps to start and end each day

	$day_start_timestamp = strtotime( $day_start . ' ' . zeroise( $hour_start, 2 ) . ':00:00' );
	$day_end_timestamp = strtotime( $day_start . ' ' . zeroise( $hour_end, 2 ) . ':00:00' );

	$the_week = array();
	for ( $day = $day_start_timestamp; $day < ( $day_start_timestamp + ( 24 * 3600 * 7 ) ); $day = $day + ( 24 * 3600 ) ) {
		// Increase one day in every loop until we completed 7 days
		$the_week[] = date( 'Y-m-d', $day );
	}

	// These are the time slots for every day in the week
	$time_slots = $start_hours = array();

	if ( $worker_id && appointments_is_worker( $worker_id ) ) {
		$start_hours = appointments_get_worker_weekly_start_hours( $service_id, $worker_id, $location_id );
	} else {
		$workers = array();
		if ( $service_id ) {
			$workers = appointments_get_workers_by_service( $service_id );
		} else {
			$workers = appointments_get_all_workers();
		}
		if ( empty( $workers ) ) {
			for ( $time = $day_start_timestamp; $time < $day_end_timestamp; $time = $time + $step ) {
				$time_slots[] = array(
					'from' => date( 'H:i', $time ),
					'to' => date( 'H:i', $time + $step ),
				);
			}
		} else {
			foreach ( $workers as $worker ) {
				$start_hours = array_merge( $time_slots , appointments_get_worker_weekly_start_hours( $service_id, $worker_id, $location_id ) );
			}
		}
	}

	if ( ! empty( $start_hours ) ) {
		sort( $start_hours );
		foreach ( $start_hours as $start_time ) {
			$start_dt = strtotime( $start_time );
			$end_time = date( 'H:i', strtotime( '+' . $step . ' seconds', $start_dt ) );
			if ( apply_filters( 'appointments_get_weekly_schedule_slots/skip_after_midnight', true ) ) {
				if ( $end_time < $start_time ) {
					continue;
				}
			}
			$time_slots[] = array(
				'from' => $start_time,
				'to' => $end_time,
			);
		}
	}
	$slots = array(
		'the_week' => $the_week,
		'time_slots' => $time_slots,
	);
	/**
	 * Allows to filter weekly schedule slots
	 */
	$slots = apply_filters( 'appointments_get_weekly_schedule_slots', $slots, $now, $service_id, $worker_id, $location_id );
	return $slots;
}

/**
 * Return the weekly starting hours of a worker, set in Settings/Working Hours
 *
 * @param int $service_id
 * @param int $worker_id
 * @param int $location_id
 * @param boolean $force Force to get hours.
 *
 * @return array
 */
function appointments_get_worker_weekly_start_hours( $service_id = 0, $worker_id = 0, $location_id = 0, $force = false, $use_admin_min_time = false ) {

	if ( ! $force ) {
		if ( ! $worker_id || ! appointments_is_worker( $worker_id ) ) {
			return array();
		}
	}

	$appointments = appointments();
	$step = $duration = $appointments->get_min_time() * MINUTE_IN_SECONDS;
	$worker = appointments_get_worker( $worker_id );
	$worker_working_hours = appointments_get_worker_working_hours( 'open', $worker_id, $location_id );

	if ( ! appointments_use_legacy_duration_calculus() ) {
		$service = appointments_get_service( $service_id );
		if ( $service ) {
			$duration = $service->duration * MINUTE_IN_SECONDS;
		}
	}
	// Allow direct step increment manipulation,
	// mainly for service duration based calculus start/stop times
	$duration = apply_filters( 'app-timetable-step_increment', $duration );

	if ( 'admin_min_time' === $use_admin_min_time ) {
		$options = appointments_get_options();
		if ( isset( $options['admin_min_time'] ) ) {
			$value = intval( $options['admin_min_time'] );
			if ( 0 < $value ) {
				$duration = MINUTE_IN_SECONDS * $value;
			}
		}
	}

	if ( ! empty( $worker_working_hours ) && isset( $worker_working_hours->hours ) && ! empty( $worker_working_hours->hours ) ) {
		$slot_starts = array();
		//The starting hours set in Working Hours settings page
		foreach ( $worker_working_hours->hours as $dayname => $open_hours ) {
			if ( $open_hours['active'] != 'yes' ) {
				continue;
			}
			for ( $start_time = $open_hours['start']; $start_time < $open_hours['end']; $start_time = date( 'H:i', strtotime( '+' . $duration . ' seconds', strtotime( $start_time ) ) ) ) {
				$end_slot = date( 'H:i', strtotime( '+' . $duration . ' seconds', strtotime( $start_time ) ) );
				if ( $end_slot > $open_hours['end'] ) {
					break;
				}
				$ccs = date( 'H:i', apply_filters( 'app_ccs', strtotime( $start_time ) ) );
				if ( ! in_array( $ccs, $slot_starts ) ) {
					$slot_starts[] = $ccs;
				}
			}
		}
		return $slot_starts;
	}
}

/**
 * Return the max and min working hours in this site
 * or for a given worker/location
 *
 * @param bool $worker
 * @param bool $location
 *
 * @return bool|array
 */
function appointments_get_working_hours_range( $worker = false, $location = false ) {
	$result = appointments_get_worker_working_hours( 'open', $worker, $location );
	if ( ! $result ) {
		return false;
	}

	$days = array_filter( $result->hours );

	if ( ! is_array( $days ) ) {
		return false;
	}

	$hours = array();
	foreach ( $days as $day ) {
		// Get the hour
		$day_start = explode( ':', $day['start'] );
		$hour_start = absint( $day_start[0] );

		$day_end = explode( ':', $day['end'] );
		$hour_end = absint( $day_end[0] );
		$minutes_end = absint( $day_end[1] );

		if ( 0 === $hour_end ) {
			$hour_end = 24;
		}

		if ( 0 !== $minutes_end ) {
			// Add an extra hour
			$hour_end = min( 24, ++$hour_end );
		}

		$hours[] = $hour_start;
		$hours[] = $hour_end;
	}

	return array( 'min' => min( $hours ), 'max' => max( $hours ) );
}


function appointments_use_legacy_duration_calculus() {
	if ( defined( 'APP_USE_LEGACY_DURATION_CALCULUS' ) && APP_USE_LEGACY_DURATION_CALCULUS ) {
		// Defined by user, use this value
		return ( APP_USE_LEGACY_DURATION_CALCULUS === 'legacy' );
	}
	return apply_filters( 'appointments_use_legacy_duration_calculus', false );
}

function appointments_use_legacy_break_times_padding_calculus() {
	if ( defined( 'APP_BREAK_TIMES_PADDING_CALCULUS' ) && APP_BREAK_TIMES_PADDING_CALCULUS ) {
		// Defined by user, use this value
		return ( APP_BREAK_TIMES_PADDING_CALCULUS != 'legacy' );
	}
	return apply_filters( 'appointments_use_legacy_break_times_padding_calculus', false );
}

function appointments_use_legacy_boundaries_calculus() {
	if ( defined( 'APP_USE_LEGACY_BOUNDARIES_CALCULUS' ) && APP_USE_LEGACY_BOUNDARIES_CALCULUS ) {
		// Defined by user, use this value
		return ( APP_USE_LEGACY_BOUNDARIES_CALCULUS === 'legacy' );
	}
	return apply_filters( 'appointments_use_legacy_boundaries_padding_calculus', false );
}

/**
 * Check if a slot is not available
 *
 * @param $start
 * @param $end
 * @param array $args
 *
 * @return bool
 */
function apppointments_is_range_busy( $start, $end, $args = array() ) {
	$appointments = appointments();
	$defaults = array(
	    'worker_id'   => 0,
	    'service_id'  => 0,
	    'location_id' => 0,
		'capacity'    => 0,
		'status_exclude' => array( 'removed', 'completed' ),
	);
	$args = wp_parse_args( $args, $defaults );
	$apps = array();
	$args['week'] = $week = date( 'W', $start );
	$period = new App_Period( $start, $end );
	// If a specific worker is selected, we will look at his schedule first.
	if ( 0 == $args['worker_id'] ) {
		$apps = appointments_get_appointments( $args );
		if ( 0 === $args['capacity'] ) {
			$workers = appointments_get_workers_by_service( $args['service_id'] );
			$args['capacity'] = count( $workers );
		}
	} else {
		if ( ! appointments_is_working( $start, $end, $args['worker_id'], $args['location_id'] ) ) {
			return true;
		}
		$apps = $appointments->get_reserve_apps_by_worker( $args['location_id'], $args['worker_id'], $week );
		$args['capacity'] = 1;
	}
	$is_busy = false;
	if ( 0 !== $args['capacity'] && $apps ) {
		$counter = 0;
		foreach ( $apps as $app ) {
			//if ( $start >= strtotime( $app->start ) && $end <= strtotime( $app->end ) ) return true;
			$app_properties = apply_filters( 'app-properties-for-calendar', array( 'start' => $app->start, 'end' => $app->end ), $app, $args );
			if ( $args['service_id'] === $app->service && $period->contains( $app_properties['start'], $app_properties['end'], true ) ) {
				$counter++;
			}
		}
		if ( $counter >= $args['capacity'] ) {
			$is_busy = true;
		}
	}
	// If we're here, no worker is set or (s)he's not busy by default. Let's go for quick filter trip.
	$is_busy = apply_filters( 'app-is_busy', $is_busy, $period );
	if ( $is_busy ) {
		return true;
	}
	// If we are here, no preference is selected (provider_id=0) or selected provider is not busy. There are 2 cases here:
	// 1) There are several providers: Look for reserve apps for the workers giving this service.
	// 2) No provider defined: Look for reserve apps for worker=0, because he will carry out all services
	if ( appointments_get_all_workers() ) {
		$workers = appointments_get_workers_by_service( $args['service_id'] );
		$apps    = array();
		if ( $workers ) {
			foreach ( $workers as $worker ) {
				/** @var Appointments_Worker $worker * */
				if ( appointments_is_working( $start, $end, $worker->ID, $args['location_id'] ) ) {
					$app_worker = $appointments->get_reserve_apps_by_worker( $args['location_id'], $worker->ID, $week );
					if ( $app_worker && is_array( $app_worker ) ) {
						$apps = array_merge( $apps, $app_worker );
					}
					// Also include appointments by general staff for services that can be given by this worker
					$services_provided = $worker->services_provided;
					if ( $services_provided && is_array( $services_provided ) && ! empty( $services_provided ) ) {
						foreach ( $services_provided as $service_ID ) {
							$_args           = array(
								'location' => $args['location_id'],
								'service'  => $service_ID,
								'week'     => $week,
							);
							$apps_service_0 = appointments_get_appointments_filtered_by_services( $_args );
							if ( $apps_service_0 && is_array( $apps_service_0 ) ) {
								$apps = array_merge( $apps, $apps_service_0 );
							}
						}
					}
				}
			}
			// Remove duplicates
			$apps = $appointments->array_unique_object_by_ID( $apps );
		}
	} else {
		$apps = $appointments->get_reserve_apps_by_worker( $args['location_id'], 0, $week );
	}
	$n = 0;
	foreach ( $apps as $app ) {
		// @FIX: this will allow for "only one service and only one provider per time slot"
		if ( $args['location_id'] && $args['service_id'] && ( $app->service != $args['service_id'] ) ) {
			continue;
			// This is for the following scenario:
			// 1) any number of providers per service
			// 2) any number of services
			// 3) only one service and only one provider per time slot:
			// 	- selecting one provider+service makes this provider and selected service unavailable in a time slot
			// 	- other providers are unaffected, other services are available
		}
		// End @FIX
		//if ( $start >= strtotime( $app->start ) && $end <= strtotime( $app->end ) ) $n++;
		if ( $period->contains( $app->start, $app->end ) ) {
			$n ++;
		}
	}
	$num = $appointments->available_workers( $start, $end );
	if ( $n >= $num ) {
		return true;
	}
	// Nothing found, so this time slot is not busy
	return false;
}

/**
 *
 * @param bool $timestamp Any timestamp inside the month we want to display
 * @param array $args List of arguments
 *
 * @return mixed|string|void
 */
function appointments_monthly_calendar( $timestamp = false, $args = array() ) {
	$appointments = appointments();
	$options = appointments_get_options();
	$defaults = array(
		'service_id' => 0,
		'worker_id' => 0,
		'location_id' => 0,
		'class' => '',
		'long' => false,
		'echo' => true,
		'widget' => false,
		'hide_today_times' => false,
	);
	$args = wp_parse_args( $args, $defaults );
	$current_time = current_time( 'timestamp' );
	$date = $timestamp ? $timestamp : $current_time;
	$year  = date( 'Y', $date );
	$month = date( 'm', $date );
	$time  = strtotime( "{$year}-{$month}-01" );

	$days  = (int) date( 't', $time );
	$first = (int) date( 'w', strtotime( date( 'Y-m-01', $time ) ) );
	$last  = (int) date( 'w', strtotime( date( 'Y-m-' . $days, $time ) ) );

	$schedule_key = sprintf( '%sx%s', strtotime( date( 'Y-m-01', $time ) ), strtotime( date( 'Y-m-' . $days, $time ) ) );

	$start_of_week = appointments_week_start();

	$tbl_class = $args['class'];
	$tbl_class = $tbl_class ? "class='{$tbl_class}'" : '';

	$working_days = $appointments->get_working_days( $args['worker_id'], $args['location_id'] ); // Get an array of working days
	$capacity = appointments_get_service_capacity( $args['service_id'] );

	$time_table = '';

	ob_start();
	?>
	<div class="app_monthly_schedule_wrapper">
		<a id="app_schedule">&nbsp;</a>

		<?php do_action( 'appointments_monthly_schedule_before_table', '' ); ?>

		<table width='100%' <?php echo $tbl_class; ?>>
			<?php echo _appointments_get_table_meta_row_monthly( 'thead', $args['long'] ); ?>
			<tbody>
			<?php do_action( 'appointments_monthly_schedule_before_first_row', '' ); ?>

			<tr>
				<?php if ( $first > $start_of_week ) :  ?>
					<td class="no-left-border" colspan="<?php echo ( $first - $start_of_week ); ?>">&nbsp;</td>
				<?php elseif ( $first < $start_of_week ) :  ?>
					<td class="no-left-border" colspan="<?php echo ( 7 + $first - $start_of_week ); ?>">&nbsp;</td>
				<?php endif; ?>

				<?php for ( $i = 1; $i <= $days; $i ++ ) :  ?>
					<?php
					$date = date( 'Y-m-' . sprintf( '%02d', $i ), $time );
					$dow  = (int) date( 'w', strtotime( $date ) );
					$ccs  = strtotime( "{$date} 00:00" );
					$cce  = strtotime( "{$date} 23:59" );
					?>
					<?php if ( $start_of_week == $dow ) :  ?>
						</tr>
						<tr>
					<?php endif; ?>

					<?php
						// First mark passed days
					if ( $current_time > $cce ) {
						$class_name = 'notpossible app_past';
					} // Then check if this time is blocked
					elseif ( isset( $options['app_lower_limit'] ) && $options['app_lower_limit'] && ( $current_time + $options['app_lower_limit'] * 3600) > $cce ) {
						$class_name = 'notpossible app_blocked';
					} // Check today is holiday
					elseif ( appointments_is_worker_holiday( $args['worker_id'], $ccs, $cce ) ) {
						$class_name = 'notpossible app_holiday';
					} // Check if we are working today
					elseif ( ! in_array( date( 'l', $ccs ), $working_days ) && ! appointments_is_exceptional_working_day( $ccs, $cce, $args['worker_id'], $args['location_id'] ) ) {
						$class_name = 'notpossible notworking';
					} // Check if we are exceeding app limit at the end of day
					elseif ( $cce > $current_time + ( $appointments->get_app_limit() + 1 ) * 86400 ) {
						$class_name = 'notpossible';
					} // If nothing else, then it must be free unless all time slots are taken
					else {
						// At first assume all cells are busy
						$appointments->is_a_timetable_cell_free = false;

						//Do not include the timetable in the widget, but run the appointments_get_timetable to check if free or busy
						if ( ! $args['widget'] ) {
							$time_table .= appointments_get_timetable( $ccs, $capacity, $schedule_key, $args['hide_today_times'], $args['worker_id'] );
						} else {
							appointments_get_timetable( $ccs, $capacity, $schedule_key, $args['hide_today_times'], $args['worker_id'] );
						}
						// Look if we have at least one cell free from get_timetable function
						if ( $appointments->is_a_timetable_cell_free ) {
							$class_name = 'free';
						} else {
							$class_name = 'busy';
						}
					}

						// Check for today
					if ( $current_time > $ccs && $current_time < $cce ) {
						$class_name = $class_name . ' today';
					}

						?>
						<td class="<?php echo esc_attr( $class_name ); ?>" title="<?php echo esc_attr( date_i18n( appointments_get_date_format( 'date' ), $ccs ) ); ?>">
							<p><?php echo $i; ?></p>
							<input type="hidden" class="appointments_select_time" value="<?php echo esc_attr( $ccs ); ?>" />
						</td>
						<?php
					?>
				<?php endfor; ?>

				<?php if ( 0 == ( 6 - $last + $start_of_week ) ) :  ?>
					</tr>
				<?php elseif ( $last > $start_of_week ) :  ?>
						<td class="no-right-border" colspan="<?php echo (6 - $last + $start_of_week); ?>">&nbsp;</td>
					</tr>
				<?php elseif ( $last + 1 == $start_of_week ) :  ?>
					</tr>
				<?php else : ?>
						<td class="no-right-border" colspan="<?php echo ( 6 + $last - $start_of_week ); ?>">&nbsp;</td>
					</tr>
				<?php endif; ?>

				<?php do_action( 'appointments_monthly_schedule_after_last_row', '' ); ?>
			</tbody>
			<?php echo _appointments_get_table_meta_row_monthly( 'tfoot', $args['long'] ); ?>
		</table>
		<?php do_action( 'appointments_monthly_schedule_after_table', '' ); ?>
	</div>

	<div class="app_timetable_wrapper">
		<?php echo $time_table; ?>
	</div>

	<div style="clear:both"></div>

	<script>
		jQuery( document ).ready( function( $ ) {
            var selector = ".app_monthly_schedule_wrapper table td.free",
				callback = function (e) {
					$( selector ).off( "click", callback );
					var selected_timetable = $( ".app_timetable_" + $( this ).find( ".appointments_select_time" ).val() );
					$( ".app_timetable:not(selected_timetable)" ).hide();
					selected_timetable.show( "slow", function() {
						$( selector ).on( "click", callback );
					} );
            	};

            $( selector ).on( "click", callback );
		});
	</script>
	<?php
	$ret = ob_get_clean();

	if ( $args['echo'] ) {
		echo $ret;
		return;
	}

	return $ret;
}

/**
 * Display the weekly calendar
 */
function appointments_weekly_calendar( $date = false, $args = array() ) {
	$appointments = appointments();

	$current_time = current_time( 'timestamp' );
	$defaults     = array(
		'service_id'  => 0,
		'workers'     => array(),
		'location_id' => 0,
		'class'       => '',
		'long'        => false,
		'echo'        => true,
	);
	$args         = wp_parse_args( $args, $defaults );

	if ( ! isset( $args['worker_id'] ) || empty( $args['worker_id'] ) ) {
		$args['worker_id'] = $args['workers'][0];
	}

	$schedule_key = sprintf( '%sx%s', $date, $date + ( 7 * 86400 ) );

	$tbl_class = esc_attr( $args['class'] );

	$slots = appointments_get_weekly_schedule_slots( $date, $args['service_id'], $args['worker_id'], $args['location_id'] );

	/**
	 * Get an array of working days, by all worksers.
	 */
	$working_days = array();
	foreach ( $args['workers'] as $worker_id ) {
		$working_days = array_merge( $working_days, $appointments->get_working_days( $worker_id, $args['location_id'] ) );
	}
	$working_days = array_unique( $working_days );

	$capacity = $appointments->get_capacity();
	$options = appointments_get_options();
	$worker_id = $appointments->get_worker_id();

	ob_start();
	?>
	<a name="app_schedule">&nbsp;</a>
	<?php do_action( 'app_schedule_before_table', '' ); ?>
	<table width="100%" class="appointments-weekly-calendar-table <?php echo $tbl_class; ?>">
		<thead>
			<tr>
				<th class="hourmin_column">&nbsp;</th>
				<?php echo _appointments_get_table_meta_row( $slots['the_week'], $args['long'] ); ?>
			</tr>
		</thead>
		<tbody>
<?php
	$range_args = array(
		'service_id' => $args['service_id'],
		'location_id' => $args['location_id'],
		'capacity' => $capacity,
	);
	do_action( 'app_schedule_before_first_row', '' );
	foreach ( $slots['time_slots'] as $time_slot ) {
		$from_time = date( appointments_get_date_format( 'time' ), strtotime( $time_slot['from'] ) );
		$to_time = date( appointments_get_date_format( 'time' ), strtotime( $time_slot['to'] ) );
		?>
		<tr>
		<td class='appointments-weekly-calendar-hours-mins'><?php echo $from_time . ' &#45; ' . $to_time; ?></td><?php
		foreach ( $slots['the_week'] as $weekday_date ) {
			$date_start = $weekday_date . ' ' . $time_slot['from'];
			$date_end = $weekday_date . ' ' . $time_slot['to'];
			$datetime_start = strtotime( $date_start ); // Current cell starts
			$datetime_end = strtotime( $date_end ); // Current cell ends
			$is_busy = false;
			if ( 0 < $capacity && 0 == $worker_id ) {
				add_filter( 'app-is_busy', '__return_false' );
				$is_busy = apppointments_is_range_busy( $datetime_start, $datetime_end, $range_args );
				remove_filter( 'app-is_busy', '__return_false' );
			} else {
				$is_busy = $appointments->is_busy( $datetime_start, $datetime_end, $capacity );
			}

			$title = apply_filters(
				'app-schedule_cell-title',
				date_i18n( appointments_get_date_format( 'full' ), $datetime_start ),
				$is_busy,
				$datetime_start,
				$datetime_end,
				$schedule_key
			);

			$class_name = 'free';
			$is_working_day = false;
			if ( $current_time > $datetime_start && $current_time < $datetime_end ) {
				$class_name = 'notpossible now';
			} // Mark passed hours
			else if ( $current_time > $datetime_start ) {
				$class_name = 'notpossible app_past';
			} // Then check if this time is blocked
			else if (
				isset( $options['app_lower_limit'] ) && $options['app_lower_limit']
				&& ( $current_time + $options['app_lower_limit'] * 3600 ) > $datetime_end
			) {
				$class_name = 'notpossible app_blocked';
			} // Check today is holiday
			else {
				foreach ( $args['workers'] as $this_worker_id ) {
					if ( $is_working_day ) {
						continue;
					}
					$is_holiday = appointments_is_worker_holiday( $this_worker_id, $datetime_start, $datetime_end );
					$is_working_day = ! $is_holiday;
				}
				if ( ! $is_working_day ) {
					$class_name = 'notpossible app_holiday';
				} // Check if we are working today
				else {
					$somebody_work = false;
					foreach ( $args['workers'] as $this_worker_id ) {
						if ( $somebody_work ) {
							continue;
						}
						$somebody_work = appointments_is_exceptional_working_day( $datetime_start, $datetime_end, $this_worker_id, $args['location_id'] );
					}
					if ( ! in_array( date( 'l', $datetime_start ), $working_days ) && ! $somebody_work ) {
						$class_name = 'notpossible notworking';
					} // Check if this is break
					else {
						$somebody_work = false;
						foreach ( $args['workers'] as $this_worker_id ) {
							if ( $somebody_work ) {
								continue;
							}
							$somebody_work = ! appointments_is_interval_break( $datetime_start, $datetime_end, $this_worker_id );
						}
						if ( ! $somebody_work ) {
							$class_name = 'notpossible app_break';
						} // Then look for appointments
						else if ( $is_busy ) {
							$class_name = 'busy';
						} // Then check if we have enough time to fulfill this app
						else if ( ! $appointments->is_service_possible( $datetime_start, $datetime_end, $capacity ) ) {
							$class_name = 'notpossible service_notpossible';
						} // If nothing else, then it must be free
					}
				}
			}
			$class_name = apply_filters( 'app_class_name', $class_name, $datetime_start, $datetime_end );
?>
				<td class="app_week_timetable_cell <?php echo esc_attr( $class_name ); ?>" title="<?php echo esc_attr( $title ); ?>">
					<input type="hidden" class="appointments_take_appointment" value="<?php echo $appointments->pack( $datetime_start, $datetime_end ); ?>" />
				</td>
			<?php } ?></tr>
	<?php } ?>
		</tbody>
		<?php do_action( 'app_schedule_after_table', '' ); ?>
		<tfoot>
			<tr>
				<th class="hourmin_column">&nbsp;</th>
				<?php echo _appointments_get_table_meta_row( $slots['the_week'], $args['long'] ); ?>
			</tr>
		</tfoot>
	</table>
	<?php
	$ret = ob_get_clean();
	if ( ! $args['echo'] ) {
		return $ret;
	}
	echo $ret;
}

/**
 * @internal
 */
function _appointments_get_table_meta_row( $the_week, $long = false ) {
	$appointments = appointments();
	if ( $long ) {
		$days = $appointments->get_day_names();
	} else {
		$days = $appointments->get_short_day_names();
	}

	$list = array();
	foreach ( $the_week as $date ) {
		$weekday_number = date( 'N', strtotime( $date ) );
		if ( 7 == $weekday_number ) {
			$weekday_number = 0;
		}

		$title = apply_filters( 'appointments_week_schedule_table_head_day', $days[ $weekday_number ], strtotime( $date ) );
		$list[] = $title;
	}

	$cells = '<th>' . join( '</th><th>', $list ) . '</th>';
	return "{$cells}";
}

/**
 * @internal
 *
 * @param $which
 * @param $long
 *
 * @return string
 */
function _appointments_get_table_meta_row_monthly( $which, $long ) {
	$appointments = appointments();
	$start_of_week = appointments_week_start();
	if ( ! $long ) {
		$day_names_array = $appointments->get_short_day_names();
	} else {
		$day_names_array = $appointments->get_day_names();
	}

	$extracted = array_splice( $day_names_array, 0, $start_of_week );
	$day_names_array = array_merge( $day_names_array, $extracted );

	$cells = '<th>' . join( '</th><th>', $day_names_array ) . '</th>';
	return "<{$which}><tr>{$cells}</tr></{$which}>";
}


/**
 * Return the price for a given service and worker
 *
 * @param $service_id
 * @param $worker_id
 *
 * @return mixed
 */
function appointments_get_price( $service_id, $worker_id ) {
	$service = appointments_get_service( $service_id );
	$worker = appointments_get_worker( $worker_id );
	if ( ! $service ) {
		return 0;
	}
	$worker_price = ( $worker && $worker->price ) ? $worker->price : 0;
	if (
		isset( $service->price )
		&& ! empty( $service->price )
		&& is_numeric( $service->price )
	) {
		$worker_price += $service->price;
	}
	return $worker_price;
}


/**
 * Enqueue SweetAlert styles/scripts
 *
 * @internal
 */
function _appointments_enqueue_sweetalert() {
	$version = '1.1.3';
	wp_enqueue_style( 'app-sweetalert', appointments_plugin_url() . 'includes/external/sweetalert/sweetalert.css', array(), $version );
	wp_enqueue_script( 'app-sweetalert', appointments_plugin_url() . 'includes/external/sweetalert/sweetalert.min.js', array(), $version, true );
}

/**
 * Return if the version of Appointments is the premium version
 *
 * @internal
 *
 * @return bool
 */
function _appointments_is_pro() {
	return apply_filters( 'appointments_is_pro', is_readable( appointments_plugin_dir() . 'includes/pro/class-app-pro.php' ) );
}

function _appointments_enqueue_jquery_ui_datepicker() {
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_style( 'app-jquery-ui', appointments_plugin_url() . 'admin/css/jquery-ui/jquery-ui.min.css', array(), appointments_get_db_version() );

	/**
 * add some inline styles
 */
	$style = '';
	$style .= '.ui-state-highlight a, .ui-widget-content .ui-state-highlight a, .ui-widget-header .ui-state-highlight a {background:#333;color:#fff}';
	$style .= '.app-datepick .ui-datepicker-inline .ui-datepicker-group .ui-datepicker-calendar td { padding: 0; }';
	wp_add_inline_style( 'app-jquery-ui', $style );

	$i18n = array(
		'weekStart' => appointments_week_start(),
	);
	wp_localize_script( 'jquery-ui-datepicker', 'AppointmentsDateSettings', $i18n );
}

/**
 * Convert minutes to human readable format.
 *
 * @since 2.3.0
 *
 */
function appointment_convert_minutes_to_human_format( $duration ) {
	if ( 60 > $duration ) {
		return sprintf( _x( '%d minutes', 'less than 60 minut', 'appointments' ), $duration );
	}
	$hours = floor( $duration / 60 );
	$text = sprintf(
		_nx( '%d hour', '%d hours', $hours, 'more than 60 minuts, hours', 'appointments' ),
		$hours
	);
	$minutes = $duration - $hours * 60;
	if ( 0 < $minutes ) {
		$text .= ' ';
		$text .= sprintf(
			_nx( '%d minute', '%d minutes', $minutes, 'more than 60 minuts, minutes', 'appointments' ),
			$minutes
		);
	}
	return $text;
}


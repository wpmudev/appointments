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

	return isset ( $tables[ $table ] ) ? $tables[ $table ] : false;
}

function appointments_get_db_version() {
	return get_option( 'app_db_version' );
}

function appointments_delete_timetables_cache() {
	delete_transient( 'app_timetables' );
}

function appointments_get_timetable( $day_start, $capacity, $schedule_key=false ) {
	global $appointments;
	return $appointments->get_timetable( $day_start, $capacity, $schedule_key );
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

function appointments_get_view_path( $name, $from_front = false ){

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
	$time_format = get_option('time_format');
	$time_format = empty( $time_format ) ? 'H:i' : $time_format;
	if ( 'date' === $type ) {
		return $date_format;
	}
	elseif ( 'time' === $type ) {
		return $time_format;
	}

	return $date_format . ' ' . $time_format;
}
<?php

function appointments_delete_workers_cache() {
	wp_cache_delete( 'appointments_workers_orderby', 'appointments_workers' );
	wp_cache_delete( 'appointments_workers_results', 'appointments_workers' );
}

function appointments_delete_work_breaks_cache( $l, $w ) {
	$cache_key = 'appointments_work_breaks-' . $l . '-' . $w;
	wp_cache_delete( $cache_key );
}

function appointments_delete_exceptions_cache( $l, $w ) {
	$cache_key = 'exceptions-' . $l . '-' . $w;
	wp_cache_delete( $cache_key );
}

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
	);

	return isset ( $tables[ $table ] ) ? $tables[ $table ] : false;
}

function appointments_get_db_version() {
	return get_option( 'app_db_version' );
}
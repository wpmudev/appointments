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
		'services' => $wpdb->prefix . 'app_services'
	);

	return isset ( $tables[ $table ] ) ? $tables[ $table ] : false;
}
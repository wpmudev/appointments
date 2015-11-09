<?php


function appointments_delete_services_cache() {
	wp_cache_delete( 'appointments_services_orderby', 'appointments_services' );
	wp_cache_delete( 'appointments_services_results', 'appointments_services' );
	wp_cache_delete( 'min_service_id', 'appointments_services' );
}

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
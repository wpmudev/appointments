<?php

class Appointments_Appointment {
	public $ID = '';
	public $created = '';
	public $user = '';
	public $name = '';
	public $email = '';
	public $phone = '';
	public $address = '';
	public $city = '';
	public $location = '';
	public $service = '';
	public $worker = '';
	public $price = '';
	public $status = '';
	public $start = '';
	public $end = '';
	public $sent = '';
	public $sent_worker = '';
	public $note = '';
	public $gcal_ID = '';
	public $gcal_updated = '';
}

function appointments_get_appointments( $l, $s, $w, $week=0 ) {
	global $wpdb;

	$table = appointments_get_table( 'appointments' );
	$where = array();

	$where[] = $wpdb->prepare( "service = %d", $s );
	$where[] = $wpdb->prepare( "worker = %d", $w );

	if ( $l ) {
		$where[] = $wpdb->prepare( "location = %d", $l );
	}

	if ( 0 == $week ) {
		$where[] = "(status='pending' OR status='paid' OR status='confirmed' OR status='reserved')";
	}
	else {
		// @FIX: Problem: an appointment might already be ticked as "completed",
		// because of it's start time being in the past. Its end time, however, can still easily be
		// in the future. For long-running appointments (e.g. 2-3h) this could break the schedule slots
		// and show a registered- and paid for- slot as "available", when it's actually not.
		// E.g. http://premium.wpmudev.org/forums/topic/appointments-booking-conflictoverlapping-bookings
		$where[] = "(status='pending' OR status='paid' OR status='confirmed' OR status='reserved' OR status='completed')";

		$where[] = $wpdb->prepare( "WEEKOFYEAR(start)=%d", $week );
		// *ONLY* applied to weekly-scoped data gathering, because otherwise this would possibly
		// return all kinds of irrelevant data (appointments passed LONG time ago).
		// End @FIX
	}

	$where = "WHERE " . implode( " AND ", $where );

	$query = "SELECT * FROM $table $where";
	$cache_key = md5( $query . '-get_appointments' );
	$cached_queries = wp_cache_get( 'app_get_appointments' );
	if ( ! is_array( $cached_queries ) ) {
		$cached_queries = array();
	}

	if ( ! isset( $cached_queries[ $cache_key ] ) ) {
		$apps = $wpdb->get_results( $query );
		$cached_queries[ $cache_key ] = $apps;
		wp_cache_set( 'app_get_appointments', $cached_queries );

		foreach ( $apps as $app ) {
			wp_cache_add( $app->ID, $app, 'app_appointments' );
		}
	}
	else {
		$apps = $cached_queries[ $cache_key ];
	}

	return $apps;
}

function appointments_clear_appointment_cache( $app_id = false ) {
	global $wpdb;

	if ( $app_id ) {
		wp_cache_delete( $app_id, 'app_appointments' );
	}
	else {
		$table =appointments_get_table( 'appointments' );
		$ids = $wpdb->get_col( "SELECT ID FROM $table" );
		foreach ( $ids as $id )
			wp_cache_get( $id, 'app_appointments' );
	}


	wp_cache_delete( 'app_get_appointments' );
}
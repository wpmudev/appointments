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

	public function __construct( $appointment ) {
		foreach ( get_object_vars( $appointment ) as $key => $value ) {
			$this->$key = $this->_sanitize_field( $key, $value );
		}
	}

	private function _sanitize_field( $field, $value ) {
		// @TODO Sanitize
		//$int_fields = array( 'ID', 'user', 'service', 'location', 'worker' );

//		if ( in_array( $field, $int_fields ) )
//			return absint( $value );
//		else
			return $value;
	}

	public function get_service() {
		return appointments_get_service( $this->service );
	}

	public function get_service_id() {
		return $this->service;
	}

	public function get_worker() {
		return appointments_get_worker( $this->worker );
	}

	public function get_worker_id() {
		return $this->worker;
	}

	public function get_sent_worker_hours() {
		if ( ! $this->sent_worker ) {
			return array();
		}

		$sent_worker_hours = trim( $this->sent_worker, ':' );
		return explode( ':', $sent_worker_hours );
	}

	public function get_sent_user_hours() {
		if ( ! $this->sent ) {
			return array();
		}

		$sent_hours = trim( $this->sent, ':' );
		return explode( ':', $sent_hours );
	}

	public function get_start_gmt_date( $format = 'Y-m-d H:i:s' ) {
		return get_gmt_from_date( $this->start, $format );
	}

	public function get_end_gmt_date( $format = 'Y-m-d H:i:s' ) {
		return get_gmt_from_date( $this->end, $format );
	}

	public function get_email() {
		global $appointments;

		if ( is_email( $this->email ) ) {
			return $this->email;
		}
		elseif ( $this->worker ) {
			return $appointments->get_worker_email( $this->worker );
		}
		else {
			return $appointments->get_admin_email();
		}
	}


}

/**
 * Get a single Appointment data
 *
 * @param int|object $app_id Appointment ID or an object with an Appointment fields
 *
 * @return bool|Appointments_Appointment
 */
function appointments_get_appointment( $app_id ) {
	global $wpdb;

	if ( is_a( $app_id, 'Appointments_Appointment' ) ) {
		return $app_id;
	}
	elseif ( is_object( $app_id ) ) {
		wp_cache_add( $app_id->ID, $app_id, 'app_appointments' );
		return new Appointments_Appointment( $app_id );
	}

	$table = appointments_get_table( 'appointments' );

	$app = wp_cache_get( $app_id, 'app_appointments' );

	if ( ! $app ) {
		$app = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE ID = %d",
				$app_id
			)
		);

		wp_cache_add( $app_id, $app, 'app_appointments' );
	}


	if ( $app ) {
		return new Appointments_Appointment( $app );
	}

	return false;
}

/**
 * Get an Appointment based on GCal ID
 *
 * @param string $gcal_id
 *
 * @return Appointments_Appointment|bool
 */
function appointments_get_appointment_by_gcal_id( $gcal_id ) {
	global $wpdb;

	$table = appointments_get_table( 'appointments' );

	$_app = wp_cache_get( $gcal_id, 'app_appointments_by_gcal' );
	if ( false === $_app ) {
		$_app = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE gcal_ID = %s",
				$gcal_id
			)
		);

		if ( ! $_app ) {
			return false;
		}
	}

	wp_cache_add( $gcal_id, $_app, 'app_appointments_by_gcal' );
	return appointments_get_appointment( $_app );
}

/**
 * Insert a new Appointment
 *
 * @param array $args
 *
 * @return bool|int
 */
function appointments_insert_appointment( $args ) {
	global $wpdb, $appointments;

	$defaults = array(
		'user' => 0,
		'email' => '',
		'name' => '',
		'phone' => '',
		'address' => '',
		'city' => '',
		'service' => '',
		'worker' => '',
		'price' => '',
		'date' => '',
		'time' => '',
		'created' => current_time( 'mysql' ),
		'note' => '',
		'status' => 'pending',
		'location' => '',
		'gcal_updated' => '',
		'gcal_ID' => '',
		'duration' => false
	);

	/**
	 * Filter the arguments before inserting an Appointment
	 *
	 * @param array $args
	 */
	$args = apply_filters( 'appointments_insert_appointment_args', wp_parse_args( $args, $defaults ) );

	$insert = array();
	$insert_wildcards = array();

	$insert['created'] = $args['created'];
	$insert_wildcards[] = '%s';

	if ( $user = get_userdata( $args['user'] ) ) {
		$insert['user'] = $args['user'];
		$insert_wildcards[] = '%d';
	}
	else {
		$insert['user'] = 0;
		$insert_wildcards[] = '%d';
	}

	if ( is_email( $args['email'] ) ) {
		$insert['email'] = $args['email'];
		$insert_wildcards[] = '%s';
	}

	$insert['name'] = $args['name'];
	$insert_wildcards[] = '%s';

	$insert['phone'] = $args['phone'];
	$insert_wildcards[] = '%s';

	$insert['address'] = $args['address'];
	$insert_wildcards[] = '%s';

	$insert['city'] = $args['city'];
	$insert_wildcards[] = '%s';


	$service = appointments_get_service( $args['service'] );

	if ( $args['duration'] ) {
		$duration = $args['duration'] * 60;
	}
	elseif ( $service ) {
		$duration = $service->duration * 60;
	}
	else {
		$duration = $appointments->get_min_time() * 60;
	}

	if ( $service ) {
		$insert['service'] = $service->ID;
	}
	else {
		$insert['service'] = 0;
	}
	$insert_wildcards[] = '%d';


	$worker = appointments_get_worker( $args['worker'] );
	if ( $worker ) {
		$insert['worker'] = $worker->ID;
		$insert_wildcards[] = '%d';
	}
	else {
		$insert['worker'] = 0;
		$insert_wildcards[] = '%d';
	}

	$price = preg_replace( "/[^0-9,.]/", "", $args['price'] );
	if ( $price !== '' ) {
		if ( ! $price ) {
			$price = '';
		}
	}
	$insert['price'] = $price;
	$insert_wildcards[] = '%s';

	$time = date( 'H:i', strtotime( $args['time'] ) );
	if ( is_numeric( $args['date'] ) ) {
		// It's a timestamp
		$datetime = $args['date'];
		$insert['start'] = date( 'Y-m-d H:i:s', $datetime );
		$insert_wildcards[] = '%s';
	}
	else {
		$datetime = strtotime( str_replace(',', '', $appointments->to_us( $args['date'] ) ) . " " . $time );
		$insert['start'] = date( 'Y-m-d H:i:s', $datetime );
		$insert_wildcards[] = '%s';
	}

	$insert['end'] = date( 'Y-m-d H:i:s', $datetime + $duration );
	$insert_wildcards[] = '%s';

	$insert['note'] = $args['note'];
	$insert_wildcards[] = '%s';

	$insert['gcal_updated'] = $args['gcal_updated'];
	$insert_wildcards[] = '%s';

	$insert['gcal_ID'] = $args['gcal_ID'];
	$insert_wildcards[] = '%s';

	$allowed_status = appointments_get_statuses();
	if ( ! array_key_exists( $args['status'], $allowed_status ) ) {
		$args['status'] = 'pending';
	}
	$insert['status'] = $args['status'];
	$insert_wildcards[] = '%s';

	$location_id = ! empty( $args['location'] ) ? absint( $args['location'] ) : 0;
	$insert['location'] = $location_id;
	$insert_wildcards[] = '%d';

	$table = appointments_get_table( 'appointments' );
	$result = $wpdb->insert( $table, $insert, $insert_wildcards );

	if ( ! $result )
		return false;

	$app_id = $wpdb->insert_id;
	appointments_clear_appointment_cache( $app_id );

	appointments_update_user_appointment_data( $app_id );

	do_action( 'app_new_appointment', $app_id );
	do_action( 'wpmudev_appointments_insert_appointment', $app_id );

	return $app_id;
}

function appointments_is_busy( $start, $end, $capacity ) {
	global $appointments;
	return $appointments->is_busy( $start, $end, $capacity );
}

/**
 * Send a confirmation email fro this appointment
 *
 * @param $app_id
 */
function appointments_send_confirmation( $app_id ) {
	global $appointments;
	$appointments->send_confirmation( $app_id );
}

/**
 * Send an email when an appointment has been removed
 *
 * @param $app_id
 */
function appointments_send_removal_notification( $app_id ) {
	global $appointments;
	$appointments->send_removal_notification( $app_id );
}


/**
 * Legacy: Update a user address, city, name...
 *
 * @param $app_id
 */
function appointments_update_user_appointment_data( $app_id ) {

	if ( defined('APP_USE_LEGACY_USERDATA_OVERWRITING') && APP_USE_LEGACY_USERDATA_OVERWRITING ) {
		$app = appointments_get_appointment( $app_id );
		if ( ! $app )
			return;

		$user = get_userdata( $app->user );
		if ( ! $user )
			return;

		if ( $app->name )
			update_user_meta( $app->user, 'app_name',  $app->name );
		if (  $app->email )
			update_user_meta( $app->user, 'app_email', $app->email );
		if ( $app->phone )
			update_user_meta( $app->user, 'app_phone', $app->phone );
		if ( $app->address )
			update_user_meta( $app->user, 'app_address', $app->address );
		if ( $app->city )
			update_user_meta( $app->user, 'app_city', $app->city );

		do_action( 'app_save_user_meta', $app->user, (array)$app );
	}

}

/**
 * Update an appointment data
 *
 * @param $app_id
 * @param $args
 * @param bool $resend
 *
 * @return bool True in case of success
 */
function appointments_update_appointment( $app_id, $args ) {
	global $wpdb, $appointments;

	$old_appointment = appointments_get_appointment( $app_id );
	if ( ! $old_appointment )
		return false;

	$fields = array(
		'user' => '%d',
		'email' => '%s',
		'name' => '%s',
		'phone' => '%s',
		'address' => '%s',
		'city' => '%s',
		'service' => '%d', // Add it manually
		'worker' => '%d',
		'price' => '%s',
		'note' => '%s',
		'status' => false, // We'll not update in the same query execution
		'location' => '%d',
		'gcal_updated' => '%s',
		'gcal_ID' => '%s',
		'sent_worker' => '%s',
		'sent' => '%s',
	);

	$defaults = array(
		'date' => false, // There are no fields like these in the table but they can be passed to the function
		'time' => false,
		'datetime' => false, // Pass a date and a time (timestamp or mysql date) here instead of date and time fields
		'duration' => false
	);

	$args = wp_parse_args( $args, $defaults );

	$update = array();
	$update_wildcards = array();
	foreach ( $fields as $field => $wildcard ) {
		if ( isset( $args[ $field ] ) && $wildcard ) {
			$update[ $field ] = $args[ $field ];
			$update_wildcards[] = $wildcard;
		}
	}

	if ( isset( $update['service'] ) ) {
		$service = appointments_get_service( $update['service'] );
		if ( ! $service ) {
			$update['service'] = 0;
		}
	}
	else {
		$service = appointments_get_service( $old_appointment->service );
	}

	if ( isset( $update['worker'] ) ) {
		$worker = false;
		if ( 0 != $args['worker'] ) {
			$worker = appointments_get_worker( $update['worker'] );
		}

		if ( ! $worker ) {
			$update['worker'] = 0;
		}
	}

	if ( isset( $update['price'] ) ) {
		$update['price'] = preg_replace( "/[^0-9,.]/", "", $update['price'] );
	}

	if ( isset( $update['user'] ) ) {
		$user = get_userdata( $update['user'] );
		if ( ! $user ) {
			$update['user'] = 0;
		}
	}

	if ( isset( $update['sent_worker'] ) && is_array( $update['sent_worker'] ) ) {
		// Let's convert the array to a string
		$update['sent_worker'] = ':' . implode( ':', $update['sent_worker'] ) . ':';
	}

	if ( isset( $update['sent'] ) && is_array( $update['sent'] ) ) {
		// Let's convert the array to a string
		$update['sent'] = ':' . implode( ':', $update['sent'] ) . ':';
	}

	if ( $args['duration'] ) {
		$duration = $args['duration'] * 60;
	}
	elseif ( $service ) {
		$duration = $service->duration * 60;
	}
	else {
		$duration = $appointments->get_min_time() * 60;
	}

	if ( is_numeric( $args['datetime'] ) ) {
		// A timestamp has been passed
		$update['start'] = date( 'Y-m-d H:i:s', $args['datetime'] );
		$update_wildcards[] = '%s';

		$update['end'] = date( 'Y-m-d H:i:s', $args['datetime'] + ( $duration ) );
		$update_wildcards[] = '%s';
	}
	elseif ( ! empty( $args['date'] ) && ! empty( $args['time'] ) ) {
		$time = date( 'H:i', strtotime( $args['time'] ) );
		$datetime = strtotime( str_replace( ',', '', $appointments->to_us( $args['date'] ) ) . " " . $time );

		$update['start'] = date( 'Y-m-d H:i:s', $datetime );
		$update_wildcards[] = '%s';

		$update['end'] = date( 'Y-m-d H:i:s', $datetime + ( $duration ) );
		$update_wildcards[] = '%s';
	}

	// Change status?
	$updated_status = false;
	if ( ! empty( $args['status'] ) ) {
		// Yeah, maybe change status
		$update['status'] = $args['status'];
		$update_wildcards[] = '%s';
	}


	if ( empty( $update ) )
		return false;

	$result = false;
	if ( ! empty( $update ) ) {
		$table = appointments_get_table( 'appointments' );

		$result = $wpdb->update(
			$table,
			$update,
			array( 'ID' => $app_id ),
			$update_wildcards,
			array( '%d' )
		);
	}


	if ( ! $result && ! $updated_status ) {
		// Nothing has changed
		return false;
	}

	appointments_update_user_appointment_data( $app_id );
	appointments_clear_appointment_cache( $app_id );

	do_action( 'wpmudev_appointments_update_appointment', $app_id, $args, $old_appointment );

	return true;
}

/**
 * Update an appointment status
 *
 * @param int $app_id Appointment ID
 * @param string $new_status New appointment status
 *
 * @return bool True in case of success
 */
function appointments_update_appointment_status( $app_id, $new_status ) {
	global $wpdb;

	$app = appointments_get_appointment( $app_id );
	if ( ! $app ) {
		return false;
	}

	$old_status = $app->status;
	if ( $old_status === $new_status ) {
		return false;
	}

	$allowed_status = appointments_get_statuses();
	if ( ! array_key_exists( $new_status, $allowed_status ) ) {
		return false;
	}

	$table = appointments_get_table( 'appointments' );

	$result = appointments_update_appointment( $app->ID, array( 'status' => $new_status ) );

	if ( $result ) {
		if ( 'removed' == $new_status ) {
			do_action( 'app_removed', $app_id );
		}

		appointments_clear_appointment_cache( $app_id );

		if ( 'removed' === $new_status ) {
			appointments_send_removal_notification( $app_id );
		}

		/**
		 * Fired when an Appointment changes its status
		 *
		 * @used-by AppointmentsGcal::app_change_status()
		 * @used-by App_Users_AdditionalFields::manual_cleanup_data()
		 */
		do_action( 'wpmudev_appointments_update_appointment_status', $app_id, $new_status, $old_status );

		/**
		 * Fired when an Appointment changes its status
		 *
		 * @deprecated since 1.5.7.1
		 */
		do_action( 'app_change_status', $new_status, $app_id );

		return true;
	}

	return false;
}

/**
 * Get the appointments list given its location, service, worker and week of the year and filters it by service
 *
 * @param array $args {
 *      An array of arguments to execute the query
 *
 *      @type bool|int $location Location ID
 *      @type bool|int $service Service ID
 *      @type int $worker Worker ID
 *      @type int $week Number of week in the year. Set to 0 if not searching by week
 * }
 *
 * @return array|null|object
 */
function appointments_get_appointments_filtered_by_services( $args = array() ) {
	global $wpdb;

	$defaults = array(
		'location' => false,
		'service' => false,
		'worker' => 0,
		'week' => 0
	);

	$args = wp_parse_args( $args, $defaults );

	$location_id = absint( $args['location'] );
	$service_id = absint( $args['service'] );
	$worker_id = absint( $args['worker'] );
	$week = absint( $args['week'] );

	$cache_args = $args;
	unset( $cache_args['service'] ); // We don't query based on service

	$cache_key = md5( 'app-get-appointments-' . maybe_serialize( $cache_args ) );
	$cached_queries = wp_cache_get( 'app_get_appointments_filtered_by_service' );
	if ( ! is_array( $cached_queries ) ) {
		$cached_queries = array();
	}

	if ( isset( $cached_queries[ $cache_key ] ) ) {
		$apps = $cached_queries[ $cache_key ];
	}
	else {
		$table = appointments_get_table( 'appointments' );
		$where = array();

		$where[] = $wpdb->prepare( "worker = %d", $worker_id );

		if ( $location_id ) {
			$where[] = $wpdb->prepare( "location = %d", $location_id );
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

		$apps = $wpdb->get_results( $query );
	}


	if ( empty( $apps ) ) {
		$apps = array();
	}

	foreach ( $apps as $app ) {
		wp_cache_add( $app->ID, $app, 'app_appointments' );
	}

	$cached_queries[ $cache_key ] = $apps;
	wp_cache_set( 'app_get_appointments_filtered_by_service', $cached_queries );

	// Now filter by service
	$filtered_apps = array();
	foreach ( $apps as $app ) {
		/** @var Appointments_Appointment $app */
		$app_service = absint( $app->service );
		if ( $app_service && $app_service == $service_id ) {
			$filtered_apps[] = $app;
		}
	}



	return $filtered_apps;
}


function appointments_get_appointments( $args = array() ) {
	global $wpdb;

	$table = appointments_get_table( 'appointments' );

	$defaults = array(
		'worker' => false,
		'service' => false,
		'location' => false,
		'user' => false,
		'date_query' => array(),
		'app_id' => array(),
		'status' => false,
		'per_page' => -1,
		'page' => 1,
		's' => false,
		'orderby' => 'ID',
		'order' => 'ASC',
		'count' => false // Will return only the number of rows found
	);

	$args = wp_parse_args( $args, $defaults );

	$cache_key = md5( maybe_serialize( $args ) );
	$cached_queries = wp_cache_get( 'app_get_appointments' );
	if ( ! is_array( $cached_queries ) ) {
		$cached_queries = array();
	}

	if ( isset( $cached_queries[ $cache_key ] ) ) {
		$results = $cached_queries[ $cache_key ];
	}
	else {
		$where = array();

		if ( false !== $args['worker'] && $worker = appointments_get_worker( $args['worker'] ) ) {
			$where[] = $wpdb->prepare( "worker = %d", $worker->ID );
		}

		if ( false !== $args['location'] ) {
			$where[] = $wpdb->prepare( "location = %d", $args['location'] );
		}

		if ( false !== $args['user'] ) {
			$where[] = $wpdb->prepare( "user = %d", $args['user'] );
		}

		if ( false !== $args['service'] && ! is_array( $args['service'] ) ) {
			// Only one service, let's make it an array
			$args['service'] = array( $args['service'] );
		}

		if ( ! empty( $args['service'] ) && is_array( $args['service'] ) ) {
			$where_services = array();
			foreach ( $args['service'] as $service_id ) {
				$service = appointments_get_service( $service_id );
				if ( ! $service ) {
					continue;
				}

				$where_services[] = absint( $service_id );
			}
			$where[] = 'service IN (' . implode( ',', $where_services ) . ')';
		}

		if ( ! empty( $args['date_query'] ) && is_array( $args['date_query'] ) ) {
			$date_query_where = array();
			$date_queries = $args['date_query'];

			// Set the date queries conditions
			$allowed_conditions = array( 'AND', 'OR' );
			if ( ! isset( $args['date_query']['condition'] ) ) {
				$condition = 'AND';
			}
			else {
				$condition = strtoupper( $args['date_query']['condition'] );
			}

			if ( ! in_array( $condition, $allowed_conditions ) ) {
				$condition = 'AND';
			}

			// Parse every Date query
			foreach ( $date_queries as $date_query ) {
				$date_query = _appointments_parse_date_query( $date_query );
				if ( $date_query ) {
					$date_query_where[] = $wpdb->prepare( $date_query['field'] . $date_query['compare'] . "%s", $date_query['value'] );
				}
			}

			if ( $date_query_where ) {
				$where[] = '(' . implode( " " . $condition . " ", $date_query_where ) . ')';
			}
		}

		if ( ! empty( $args['app_id'] ) && is_array( $args['app_id'] ) ) {
			$args['app_id'] = array_map( 'absint', $args['app_id'] );
			$where[] = 'ID IN ( ' . implode( ',', $args['app_id'] ) .  ' )';
		}

		if ( $args['status'] ) {
			$statuses = array();
			if ( is_array( $args['status'] ) ) {
				foreach ( $args['status'] as $status ) {
					if ( array_key_exists( $status, appointments_get_statuses() ) ) {
						$statuses[] = $status;
					}
				}
			}
			elseif ( is_string( $args['status'] ) && array_key_exists( $args['status'], appointments_get_statuses() ) ) {
				$statuses = array( $args['status'] );
			}

			$where[] = 'status IN ("' . implode( '","', $statuses ) . '")';
		}

		if ( false !== $args['s'] ) {
			$args['s'] = trim( $args['s'] );
			if ( $args['s'] ) {
				// Search by user name
				$where[] = $wpdb->prepare(
					"name LIKE %s OR email LIKE %s OR ID IN ( SELECT ID FROM $wpdb->users WHERE user_login LIKE %s )",
					'%' . $args['s'] . '%',
					'%' . $args['s'] . '%',
					'%' . $args['s'] . '%'
				);
			}
		}

		if ( ! empty( $where ) ) {
			$where = "WHERE " . implode( " AND ", $where );
		}
		else {
			$where = '';
		}

		$allowed_orderby = array( 'ID', 'created', 'user', 'name', 'email', 'location',
			'service', 'worker', 'price', 'status', 'start', 'end' );
		$allowed_order = array( 'DESC', 'ASC', '' );
		$order_query = '';
		$args['order'] = strtoupper( $args['order'] );
		if ( in_array( $args['orderby'], $allowed_orderby ) && in_array( $args['order'], $allowed_order ) ) {
			$orderby = $args['orderby'];
			$order = $args['order'];
			$order_query = "ORDER BY $orderby $order";
		}

		$limit = '';
		if ( $args['per_page'] > 0 ) {
			$limit = $wpdb->prepare( "LIMIT %d, %d", intval( ( $args['page'] - 1 ) * $args['per_page'] ), intval( $args['per_page'] ) );
		}

		$found_rows = '';
		if ( $args['count'] ) {
			$found_rows = 'SQL_CALC_FOUND_ROWS';
		}

		$query = "SELECT $found_rows * FROM $table $where $order_query $limit";
		$results = $wpdb->get_results( $query );

		if ( $args['count'] ) {
			$results = $wpdb->get_var( "SELECT FOUND_ROWS()" );
		}

		if ( $results && ! $args['count'] ) {
			$cached_queries[ $cache_key ] = $results;
			wp_cache_set( 'app_get_appointments', $cached_queries );
		}
	}

	if ( ! $args['count'] ) {
		$apps = array();
		foreach ( $results as $row ) {
			wp_cache_add( $row->ID, $row, 'app_appointments' );
			$apps[] = new Appointments_Appointment( $row );
		}

		return $apps;
	}

	return $results;

}

function appointments_get_month_appointments( $args ) {
	global $wpdb;

	$defaults = array(
		'worker' => false,
		'status' => false,
		'start' => current_time( 'mysql' )
	);

	$args = wp_parse_args( $args, $defaults );

	if ( ! $args['worker'] ) {
		return array();
	}

	$worker = appointments_get_worker( $args['worker'] );
	if ( ! $worker ) {
		return array();
	}

	$cache_key = md5( maybe_serialize( $args ) );
	$cached_queries = wp_cache_get( 'app_get_month_appointments' );
	if ( ! is_array( $cached_queries ) ) {
		$cached_queries = array();
	}

	if ( isset( $cached_queries[ $cache_key ] ) ) {
		$results = $cached_queries[ $cache_key ];
	}
	else {
		$where = array();

		$services_ids = wp_list_pluck( appointments_get_worker_services( $args['worker'] ), 'ID' );
		if ( $services_ids ) {
			$where[] = $wpdb->prepare( "( worker = %d OR service IN (" . implode( ',', $services_ids ) . ") )", $args['worker'] );
		}
		else {
			$where[] = $wpdb->prepare( "worker = %d", $args['worker'] );
		}

		if ( ! is_array( $args['status'] ) && $args['status'] ) {
			$args['status'] = array( $args['status'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$where[] = "status IN ('" . implode( "','", $args['status'] ) . "')";
		}

		// The dates
		$first = date( 'Y-m-01 00:00:00', strtotime( $args['start'] ) ); // Start of month
		$first_timestamp = strtotime( $first );
		$last_timestamp = ($first_timestamp + (date('t', $first_timestamp) * 86400 )) - 1; // End of month
		$last = date( 'Y-m-d H:i:s', $last_timestamp );

		$where[] = $wpdb->prepare( "start > %s", $first );
		$where[] = $wpdb->prepare( "end < %s", $last );

		$table = appointments_get_table( 'appointments' );

		$where = "WHERE " . implode( " AND ", $where );
		$query = "SELECT * FROM $table $where ORDER BY start ASC";

		$results = $wpdb->get_results( $query );
	}

	$apps = array();
	foreach ( $results as $row ) {
		$apps[] = new Appointments_Appointment( $row );
	}

	return $apps;
}

/**
 * @param $user_id
 *
 * @return array
 */
function appointments_get_user_appointments( $user_id ) {
	global $wpdb;

	$user_id = absint( $user_id );
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return array();
	}

	$table = appointments_get_table( 'appointments' );
	$statuses_in = array( 'paid', 'confirmed' );
	$where = "WHERE status IN ( '" . implode( "','", $statuses_in ) . "' ) AND user = $user_id";
	$results = $wpdb->get_results( "SELECT * FROM $table $where" );

	if ( ! $results ) {
		return array();
	}

	$appointments = array();
	foreach ( $results as $row ) {
		$appointments[] = new Appointments_Appointment( $row );
	}

	return $appointments;
}

/**
 * Clear the appointments cache
 *
 * @param bool $app_id
 */
function appointments_clear_appointment_cache( $app_id = false ) {
	global $wpdb;

	if ( $app_id ) {
		wp_cache_delete( $app_id, 'app_appointments' );
		wp_cache_delete( $app_id, 'app_appointments_by_gcal' );
	}
	else {
		$table = appointments_get_table( 'appointments' );
		$apps = $wpdb->get_results( "SELECT ID, gcal_ID FROM $table" );
		foreach ( $apps as $app ) {
			wp_cache_delete( $app->ID, 'app_appointments' );
			if ( $app->gcal_ID ) {
				wp_cache_delete( $app->ID, 'app_appointments_by_gcal' );
			}
		}
	}

	wp_cache_delete( 'app_count_appointments' );
	wp_cache_delete( 'app_get_appointments_filtered_by_service' );
	wp_cache_delete( 'app_get_appointments' );
	wp_cache_delete( 'app_get_month_appointments' );
	//@ TODO: Delete capacity_ cache
	appointments_delete_timetables_cache();
}


/**
 * Return all available statuses
 * @return array
 */
function appointments_get_statuses() {
	return apply_filters( 'app_statuses',
		array(
			'pending'	=> __('Pending', 'appointments'),
			'paid'		=> __('Paid', 'appointments'),
			'confirmed'	=> __('Confirmed', 'appointments'),
			'completed'	=> __('Completed', 'appointments'),
			'reserved'	=> __('Reserved by GCal', 'appointments'),
			'removed'	=> __('Removed', 'appointments')
		)
	);
}

/**
 * Delete an appointment forever
 *
 * @param $app_id
 *
 * @return bool|false|int
 */
function appointments_delete_appointment( $app_id ) {
	global $wpdb;

	$app = appointments_get_appointment( $app_id );
	if ( ! $app )
		return false;

	$table = appointments_get_table( 'appointments' );
	$result = $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE ID = %d", $app_id ) );

	$meta_table = appointments_get_table( 'appmeta' );
	$meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM $meta_table WHERE app_appointment_id = %d ", $app_id ));
	foreach ( $meta_ids as $mid ) {
		delete_metadata_by_mid( 'app_appointment', $mid );
	}


	appointments_clear_appointment_cache( $app_id );

	/**
	 * Triggered after an appointment has been deleted
	 *
	 * @param Appointments_Appointment $app
	 */
	do_action( 'appointments_delete_appointment', $app );

	return (bool)$result;
}

/**
 * Return the expired appointments
 *
 * @param int $pending_seconds If > 0, it will also include those that are pending and were created more than $pending_seconds seconds ago
 *
 * @return array List of expired appointments
 */
function appointments_get_expired_appointments( $pending_seconds = 0 ) {
	global $wpdb;

	$pending_seconds = absint( $pending_seconds );

	$current_time = current_time( 'timestamp' );

	$table = appointments_get_table( 'appointments' );
	$expired = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table
			WHERE start < %s
			AND status NOT IN ('completed', 'removed')",
			date( 'Y-m-d H:i:s', $current_time )
		)
	);

	if ( $pending_seconds ) {
		// Get all those that are pending and $pending_seconds old
		$pending_expired = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table
					WHERE status='pending'
					AND created < %s",
				date( 'Y-m-d H:i:s', $current_time - $pending_seconds )
			)
		);

		foreach ( $pending_expired as $pending_expired_app ) {
			$expired[] = $pending_expired_app;
		}
	}

	$results = array();
	foreach ( $expired as $app ) {
		// With this, we're caching the rows
		$results[] = appointments_get_appointment( $app );
	}

	return $results;
}

/**
 * @internal
 *
 * @param $date_query
 *
 * @return array
 */
function _appointments_parse_date_query( $date_query = array() ) {
	$allowed_fields = array( 'created', 'end', 'start' );
	$allowed_comparers = array( '=', '>', '<', '<=', '>=' );

	$date_query_defaults = array(
		'compare' => '=',
		'field' => 'created',
		'value' => current_time( 'mysql' )
	);

	$date_query = wp_parse_args( $date_query, $date_query_defaults );

	if ( ! in_array( $date_query['field'], $allowed_fields ) ) {
		return false;
	}

	if ( ! in_array( $date_query['compare'], $allowed_comparers ) ) {
		return false;
	}

	return $date_query;
}


function appointments_count_appointments() {
	global $wpdb;

	$counts = wp_cache_get( 'app_count_appointments' );

	if ( false === $counts ) {
		$table = appointments_get_table( 'appointments' );

		$results = $wpdb->get_results( "SELECT status, COUNT(*) num_apps FROM $table GROUP BY status", ARRAY_A );
		$counts = array_fill_keys( array_keys( appointments_get_statuses() ), 0 );

		foreach ( $results as $row ) {
			$counts[ $row['status'] ] = absint( $row['num_apps'] );
		}

		wp_cache_set( 'app_count_appointments', $counts );
	}


	/**
	 * Modify returned appointments counts by status
	 *
	 * @since 1.5.8.1
	 *
	 * @param array $counts  An array containing the counts by status.
	 */
	return apply_filters( 'appointments_count_appontments', $counts );
}

/**
 * Return Appointments that have not been reminded to users given an hour
 *
 * @param int $hour
 * @param string $type user|worker return unsent appointments for user or worker
 *
 * @return array
 */
function appointments_get_unsent_appointments( $hour, $type = 'user' ) {
	global $wpdb;

	$date = current_time( 'mysql' );
	$table = appointments_get_table( 'appointments' );

	$query = $wpdb->prepare(
		"SELECT * FROM $table
			WHERE (status='paid' OR status='confirmed')
			AND DATE_ADD( %s, INTERVAL %d HOUR) > start ",
		$date,
		$hour
	);

	if ( 'worker' == $type ) {
		$query .= $wpdb->prepare( "AND (sent_worker NOT LIKE %s OR sent_worker IS NULL)", '%:' . $hour . ':%' );
	}
	else {
		$query .= $wpdb->prepare( "AND (sent NOT LIKE %s OR sent IS NULL)", '%:' . $hour . ':%' );
	}



	$results = $wpdb->get_results( $query );

	$apps = array();
	foreach ( $results as $result ) {
		$apps[] = appointments_get_appointment( $result );
	}

	return $apps;
}

/**
 * Return a list of Google Calendar Event IDs saved on Database
 *
 * @param integer|boolean $worker_id Filter by Worker ID
 *
 * @return array
 */
function appointments_get_gcal_ids( $worker_id = false ) {
	global $wpdb;

	$table = appointments_get_table( 'appointments' );
	$query = "SELECT gcal_ID FROM $table WHERE gcal_ID IS NOT NULL";
	if ( false !== $worker_id ) {
		$query .= $wpdb->prepare( " AND worker = %d", $worker_id );
	}
	$current_gcal_event_ids = $wpdb->get_col( $query );

	if ( ! $current_gcal_event_ids ) {
		$current_gcal_event_ids = array();
	}

	return $current_gcal_event_ids;
}

function appointments_get_appointment_meta( $app_id, $meta_key = '' ) {
	$value = get_metadata( 'app_appointment', $app_id, $meta_key, true );
	if ( '' === $meta_key && is_array( $value ) ) {
		foreach ( $value as $key => $val ) {
			$value[ $key ] = $val[0];
		}
	}

	return $value;
}

function appointments_update_appointment_meta( $app_id, $meta_key, $meta_value ) {
	return update_metadata( 'app_appointment', $app_id, $meta_key, $meta_value );
}

function appointments_delete_appointment_meta( $app_id, $meta_key ) {
	return delete_metadata( 'app_appointment', $app_id, $meta_key );
}
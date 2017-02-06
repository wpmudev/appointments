<?php

class Appointments_Worker {

	public $ID = '';
	public $price = 0;
	public $services_provided = '';
	public $page = 0;
	public $dummy = '';

	public function __construct( $worker ) {
		foreach ( get_object_vars( $worker ) as $key => $value ) {
			$this->$key = $this->_sanitize_field( $key, $value );
		}
	}

	private function _sanitize_field( $field, $value ) {
		$int_fields = array( 'ID', 'page' );
		$array_fields = array( 'services_provided' );

		if ( in_array( $field, $int_fields ) )
			return absint( $value );
		elseif ( in_array( $field, $array_fields ) )
			return array_filter( explode( ':' , ltrim( $value , ":") ) );
		else
			return $value;
	}

	public function get_working_hours() {
		global $wpdb;
		$table = appointments_get_table( 'wh' );
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE worker = %d", $this->ID ) );
	}

	public function get_exceptions( $status, $location = 0 ) {
		$exceptions = appointments_get_worker_exceptions( $this->ID, $status, $location );
		if ( is_object( $exceptions ) ) {
			return explode( ',', $exceptions->days );
		}
		return array();
	}

	public function update_exceptions( $status, $days, $location = 0 ) {
		return appointments_update_worker_exceptions( $this->ID, $status, $days, $location );
	}

	public function get_services() {
		if ( empty( $this->services_provided ) )
			return array();

		return array_map( 'appointments_get_service', $this->services_provided );
	}

	public function get_name( $field = 'default' ) {
		$userdata = get_userdata( $this->ID );
		$name = '';

		if ( ! empty( $userdata->app_name ) ) {
			// If app_name meta exists, use it
			$name = $userdata->app_name;
		}

		if ( empty( $name ) ) {

			if ( 'default' == $field || 'display_name' == $field ) {
				$name = $userdata->display_name;
			}
			else {
				$name = $userdata->user_login;
			}

			if ( empty( $name ) ) {
				$first_name = get_user_meta( $this->ID, 'first_name', true );
				$last_name = get_user_meta( $this->ID, 'last_name', true );
				$name = $first_name . " " . $last_name;
			}

			if ( "" == trim( $name ) ) {
				$name = $userdata->user_login;
			}
		}

		return $name;

	}

}

function appointments_get_worker( $worker_id ) {
	global $wpdb;

	if ( ! $worker_id ) {
		return false;
	}

	if ( is_a( $worker_id, 'Appointments_Worker' ) ) {
		$worker_id = $worker_id->ID;
	}

	$table = appointments_get_table( 'workers' );

	$worker = wp_cache_get( $worker_id, 'app_workers' );

	if ( ! $worker ) {
		$worker = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * from $table WHERE ID = %d",
				$worker_id
			)
		);

		if ( $worker ) {
			wp_cache_add( $worker->ID, $worker, 'app_workers' );
		}

	}


	if ( $worker )
		return new Appointments_Worker( $worker );

	return false;
}

/**
 * Get a worker name
 *
 * @param int $worker_id Worker ID
 * @param string $field Field to return default, display_name or user_login
 *
 * @return string Worker name
 */
function appointments_get_worker_name( $worker_id, $field = 'display_name' ) {
	$worker = appointments_get_worker( $worker_id );
	$name = '';
	if ( is_a( $worker, 'Appointments_Worker' ) ) {
		$name = $worker->get_name( $field );
	}

	if ( empty( $name ) ) {
		// Show different text to authorized people
		// @TODO Take this code out from this function responsibility
		$current_user_id = get_current_user_id();
		if ( is_admin() || App_Roles::current_user_can( 'manage_options', App_Roles::CTX_STAFF ) || appointments_is_worker( $current_user_id ) ) {
			$name = __('Our staff', 'appointments');
		}
		else {
			$name = __('A specialist', 'appointments');
		}
	}

	return apply_filters( 'app_get_worker_name', $name, $worker_id );
}

function appointments_is_worker( $id ) {
	return appointments_get_worker( $id ) ? true : false;
}

function appointments_insert_worker( $args = array() ) {
	global $wpdb;

	$table = appointments_get_table( 'workers' );

	$defaults = array(
		'ID' => false,
		'price' => '',
		'services_provided' => array(),
		'page' => 0
	);

	$args = wp_parse_args( $args, $defaults );

	$insert = array();
	$insert_wildcards = array();

	// Worker ID
	$ID = absint( $args['ID'] );
	$user = get_userdata( $ID );

	if ( ! $user )
		return false;

	// Check if the user is already in workers table
	$worker = appointments_get_worker( $ID );
	if ( $worker )
		return false;

	$insert['ID'] = $ID;
	$insert_wildcards[] = '%d';


	// Price
	$price = preg_replace( "/[^0-9,.]/", "", $args['price'] );
	if ( $price !== '' ) {
		if ( ! $price )
			$price = '';
	}
	$insert['price'] = $price;
	$insert_wildcards[] = '%s';


	// Services provided
	$_services_provided = $args['services_provided'];
	if ( ! is_array( $_services_provided ) || empty( $_services_provided ) )
		$_services_provided = false;

	if ( $_services_provided ) {
		$services_provided = array();
		foreach ( $_services_provided as $service_id ) {
			if ( appointments_get_service( $service_id ) )
				$services_provided[] = $service_id;
		}
		$insert['services_provided'] = ':'. implode( ':', array_filter( $services_provided ) ) . ':';
	}
	else {
		$insert['services_provided'] = '::';
	}

	$insert_wildcards[] = '%s';

	// Page
	$page_id = absint( $args['page'] );
	$page = get_post( $page_id );
	if ( $page && $page->post_type == 'page' ) {
		$insert['page'] = $page_id;
		$insert_wildcards[] = '%d';
	}

	// Dummy
	$db_version = appointments_get_db_version();

	if ( $db_version ) {
		$insert['dummy'] = isset( $args['dummy'] ) && $args['dummy'] ? true : '';
		$insert_wildcards[] = '%s';
	}

	$r = $wpdb->insert( $table, $insert, $insert_wildcards );

	if ( $r ) {

		// Set default working hours
		$ex_table = appointments_get_table( 'exceptions' );

		// Insert the default working hours and holidays to the worker's working hours
		foreach ( array( 'open', 'closed' ) as $stat ) {
			$result_wh = appointments_get_worker_working_hours( $stat, 0, 0 );
			if ( $result_wh ) {
				appointments_update_worker_working_hours( $ID, $result_wh->hours, $stat );
			}

			$result_ex = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $ex_table WHERE location=0 AND service=0 AND status=%s", $stat ), ARRAY_A );
			if ( $result_ex != null ) {
				unset( $result_ex["ID"] );
				$result_ex["worker"] = $args['ID'];
				$wpdb->insert(
					$ex_table,
					$result_ex
				);
			}
		}

		appointments_delete_worker_cache( $ID );

		do_action( 'appointments_insert_worker', $ID );

		return true;
	}

	return false;
}

function appointments_update_worker( $worker_id, $args = array() ) {
	global $wpdb;

	$old_worker = appointments_get_worker( $worker_id );
	if ( ! $old_worker )
		return false;

	$fields = array( 'services_provided' => '%s', 'dummy' => '%s', 'price' => '%s', 'page' => '%d', 'ID' => '%d' );

	$update = array();
	$update_wildcards = array();
	foreach ( $fields as $field => $wildcard ) {
		if ( isset( $args[ $field ] ) ) {
			$update[ $field ] = $args[ $field ];
			$update_wildcards[] = $wildcard;
		}
	}

	if ( isset( $update['price'] ) )
		$update['price'] = $price = preg_replace( "/[^0-9,.]/", "", $update['price'] );

	if ( isset( $insert['dummy'] ) && $insert['dummy'] ) {
		$insert['dummy'] = true;
	}
	elseif ( isset( $insert['dummy'] ) && ! $insert['dummy'] ) {
		$insert['dummy'] = '';
	}

	if ( isset( $update['services_provided'] ) ) {
		if ( ! is_array( $update['services_provided'] ) || empty( $update['services_provided'] ) ) {
			return false;
		}

		$services_provided = array();
		foreach ( $update['services_provided'] as $service_id ) {
			if ( appointments_get_service( $service_id ) )
				$services_provided[] = $service_id;
		}

		if ( empty( $services_provided ) )
			return false;

		$update['services_provided'] = ':'. implode( ':', array_filter( $services_provided ) ) . ':';
	}

	if ( isset( $update['ID'] ) ) {
		$user_id = absint( $update['ID'] );
		if ( ! get_userdata( $user_id ) )
			return false;

		$update['ID'] = $user_id;
	}

	if ( empty( $update ) )
		return false;

	$table = appointments_get_table( 'workers' );

	$result = $wpdb->update(
		$table,
		$update,
		array( 'ID' => $worker_id ),
		$update_wildcards,
		array( '%d' )
	);

	if ( $result ) {
		appointments_delete_worker_cache( $worker_id );
		if ( isset( $update['ID'] ) )
			appointments_delete_worker_cache( $update['ID'] );

		// Update working hours and exceptions if we have changed the ID
		if ( isset( $update['ID'] ) ) {
			$wh_table = appointments_get_table( 'wh' );
			$ex_table = appointments_get_table( 'exceptions' );

			$wpdb->update(
				$wh_table,
				array( 'worker' => $update['ID'] ),
				array( 'worker' => $worker_id ),
				array( '%d' ),
				array( '%d' )
			);

			$wpdb->update(
				$ex_table,
				array( 'worker' => $update['ID'] ),
				array( 'worker' => $worker_id ),
				array( '%d' ),
				array( '%d' )
			);
		}

	}

	do_action( 'wpmudev_appointments_update_worker', $worker_id, $args, $old_worker );

	return (bool)$result;
}

/**
 * Get a list of workers
 *
 * @param array $args {
 *     Optional. Arguments to retrieve workers.
 *
 *     @type int            $user_id          Filter workers by user ID. Default false
 *     @type string         $orderby          orderby field (possible values ID, ID ASC, ID DESC, name ASC, name DESC). Default ID
 *     @type bool|int       $page             Filter by attached page to worker. Default false
 *     @type bool           $count            If set to true, it will return the number of workers found. Default false
 *     @type bool|string    $fields           Fields to be returned (false or 'ID'). If false it will return all fields. Default false.
 *     @type bool|int       $service          Filter by service ID. Default false.
 *     @type bool           $with_page        Retrieve only workers with page attached. Default false.
 *     @type bool|int       $limit            Max number of workers to return. If false will return all. Default false.
 * }
 *
 * @return array of Appointments_Worker
 */
function appointments_get_workers( $args = array() ) {
	global $wpdb;

	$defaults = array(
		'user_id' => false, // Filter by user ID
		'orderby' => 'ID',
		'page' => false, // Filter by page ID
		'count' => false,
		'fields' => false,
		'service' => false, // Filter by service
		'with_page' => false, // Only pages IDs > 0
		'limit' => false
	);

	$args = wp_parse_args( $args, $defaults );

	$serialized_args = maybe_serialize( $args );
	$cache_key = md5( $serialized_args );
	$cached_queries = wp_cache_get( 'app_get_workers' );
	if ( ! is_array( $cached_queries ) ) {
		$cached_queries = array();
	}

	if ( isset( $cached_queries[ $cache_key ] ) && ! $args['count'] ) {
		return $cached_queries[ $cache_key ];
	}

	$table = appointments_get_table( 'workers' );

	$where = array();
	$page_id = absint( $args['page'] );

	if ( $page_id )
		$where[] = $wpdb->prepare( "w.page = %d", $page_id );

	$service_id = absint( $args['service'] );
	if ( $service_id ) {
		$where[] = $wpdb->prepare( "w.services_provided LIKE %s", '%:' . $service_id . ':%' );
	}

	$user_id = absint( $args['user_id'] );
	if ( $user_id )
		$where[] = $wpdb->prepare( "w.ID = %d", $user_id );

	if ( $args['with_page'] ) {
		$where[] = "w.page > 0";
	}

	// @TODO: We need to move this to somewhere else
	$order_by = '';
	$order = '';
	if ( $args['orderby'] ) {
		$order_by = explode( ' ', $args['orderby'] );
		if ( is_array( $order_by ) && count( $order_by ) == 2 ) {
			// orderby is like "ID ASC"
			$order = strtoupper( $order_by[1] );
			$order_by = $order_by[0];
		}
		elseif ( is_array( $order_by ) && count( $order_by ) == 1 ) {
			$order_by = $order_by[0];
		}
		else {
			$order_by = $args['orderby'];
		}
	}

	// We need to make this complex due to legacy stuff

	// Allowed to add into the query itself
	$allowed_orderby_in_query = array( 'ID' );

	// This will be the order post-query
	$allowed_orderby = array( 'name' );

	$allowed_order = array( 'ASC', 'DESC' );
	if ( ! in_array( $order, $allowed_order ) ) {
		$order = '';
	}

	if ( ! in_array( $order_by, $allowed_orderby_in_query ) ) {
		$order_by = 'ID';
	}

	//$allowed_orderby = $whitelist = apply_filters( 'app_order_by_whitelist', array( 'ID', 'name', 'start', 'end', 'duration', 'price',
		//'ID DESC', 'name DESC', 'start DESC', 'end DESC', 'duration DESC', 'price DESC', 'RAND()', 'name ASC', 'name DESC' ) );

	$order_by = apply_filters( 'app_get_workers_orderby', $order_by );
	$order_query = "";
	if ( in_array( $args['orderby'], $allowed_orderby ) ) {
		$order_query = "ORDER BY $order_by $order";
	}

	if ( ! in_array( $order_by, $allowed_orderby_in_query ) ) {
		$order_by = 'ID';
	}

	//$allowed_orderby = $whitelist = apply_filters( 'app_order_by_whitelist', array( 'ID', 'name', 'start', 'end', 'duration', 'price',
		//'ID DESC', 'name DESC', 'start DESC', 'end DESC', 'duration DESC', 'price DESC', 'RAND()', 'name ASC', 'name DESC' ) );

	$order_by = apply_filters( 'app_get_workers_orderby', $order_by );
	$order_query = "ORDER BY $order_by $order";

	$limit_query = '';
	$limit = absint( $args['limit'] );
	if ( $limit ) {
		$limit_query = $wpdb->prepare( "LIMIT %d", $limit );
	}

	if ( $where )
		$where = "WHERE " . implode( ' AND ', $where );
	else
		$where = "";

	if ( ! $args['count'] ) {

		$allowed_fields = array( 'ID' );
		$field = $args['fields'];
		if ( $field && in_array( $field, $allowed_fields ) )
			$get_col = true;
		else
			$get_col = false;

		if ( $get_col ) {
			$query = "SELECT $field FROM $table w $where $order_query $limit_query";
		} else {
			$query = "SELECT * FROM $table w $where $order_query $limit_query";
		}


		if ( $get_col ) {
			$results = $wpdb->get_col( $query );
		} else {
			$results = $wpdb->get_results( $query );
		}

		$workers = array();
		if ( ! $get_col ) {
			foreach ( $results as $result ) {
				wp_cache_add( $result->ID, $result, 'app_workers' );
				$workers[] = new Appointments_Worker( $result );
			}
		}
		else {
			$workers = $results;
		}

		// Post-query ordering
		$allowed_orderby = array( 'name' );

		// And, yes, we need to do this one more time
		if ( $args['orderby'] ) {
			$order_by = explode( ' ', $args['orderby'] );
			if ( is_array( $order_by ) && count( $order_by ) == 2 ) {
				// orderby is like "ID ASC"
				$order = strtoupper( $order_by[1] );
				$order_by = $order_by[0];
			}
			elseif ( is_array( $order_by ) && count( $order_by ) == 1 ) {
				$order_by = $order_by[0];
			}
			else {
				$order_by = $args['orderby'];
			}
		}

		if ( ! in_array( $order, $allowed_order ) ) {
			$order = '';
		}

		if ( ! in_array( $order_by, $allowed_orderby ) ) {
			$order_by = '';
		}

		if ( in_array( $order_by, $allowed_orderby ) ) {
			if ( 'DESC' === $order ) {
				@usort( $workers, '_appointments_get_workers_desc' );
			}
			else {
				@usort( $workers, '_appointments_get_workers_asc' );
			}
		}


		if ( ! empty( $workers ) ) {
			$cached_queries[ $cache_key ] = $workers;
			wp_cache_set( 'app_get_workers', $cached_queries  );
		}

		return $workers;

	}
	else {
		$query = "SELECT COUNT(ID) FROM $table s $where";

		$cache_key = md5( $query . '-' . 'app_count_workers' );
		$cached_queries = wp_cache_get( 'app_count_workers' );

		if ( ! is_array( $cached_queries ) )
			$cached_queries = array();

		if ( ! isset( $cached_queries[ $cache_key ] ) ) {
			$result = $wpdb->get_var( $query );
			$result = absint( $result );
			$cached_queries[ $cache_key ] = absint( $result );
			wp_cache_set( 'app_count_workers', $cached_queries );
		}
		else {
			$result = $cached_queries[ $cache_key ];
		}


		return $result;
	}
}

/**
 * @param Appointments_Worker $a
 * @param Appointments_Worker $b
 *
 * @return int
 */
function _appointments_get_workers_desc( $a, $b ) {
	return strcmp( $b->get_name(), $a->get_name() );
}

/**
 * @param Appointments_Worker $a
 * @param Appointments_Worker $b
 *
 * @return int
 */
function _appointments_get_workers_asc( $a, $b ) {
	return strcmp( $a->get_name(), $b->get_name() );
}

/**
 * Get a list of workers attached to a service
 *
 * @param $service_id
 * @param string $order_by See appointments_get_workers() for more details
 *
 * @return array of Appointments_Worker
 */
function appointments_get_workers_by_service( $service_id, $order_by = 'ID' ) {
	$workers_by_service = wp_cache_get( 'app_workers_by_service' );
	if ( false === $workers_by_service ) {
		$workers_by_service = array();
	}

	$cache_key = $service_id . $order_by;
	if ( isset( $workers_by_service[ $cache_key ] ) ) {
		return $workers_by_service[ $cache_key ];
	}

	$workers = appointments_get_workers( array( 'orderby' => $order_by ) );
	$filtered_workers = array();
	foreach ( $workers as $worker ) {
		/** @var Appointments_Worker $worker */
		if ( in_array( $service_id, $worker->services_provided ) ) {
			$filtered_workers[] = $worker;
		}
	}

	$workers_by_service[ $cache_key ] = $filtered_workers;
	wp_cache_set( 'app_workers_by_service', $workers_by_service );

	return $filtered_workers;

}

function appointments_delete_worker( $worker_id ) {
	global $wpdb;

	if ( ! appointments_get_worker( $worker_id ) ) {
		return false;
	}

	$table = appointments_get_table( 'workers' );

	$worker_id = absint( $worker_id );

	$result = $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM $table WHERE ID = %d" ,
			$worker_id
		)
	);

	appointments_delete_worker_working_hours( $worker_id );
	appointments_delete_worker_exceptions( $worker_id );

	// Update the worker appointments
	$app_table = appointments_get_table( 'appointments' );
	$apps = appointments_get_appointments( array( 'worker' => $worker_id ) );

	foreach ( $apps as $app ) {
		appointments_update_appointment( $app->ID, array( 'worker' => 0 ) );
	}

	if ( $result ) {
		appointments_delete_worker_cache( $worker_id );

		do_action( 'appointments_delete_worker', $worker_id );
		return true;
	}

	return false;
}


function appointments_get_worker_services( $worker_id ) {
	$worker = appointments_get_worker( $worker_id );
	if ( $worker ) {
		return $worker->get_services();
	}


	return array();
}

function appointments_delete_worker_exceptions( $worker_id ) {
	global $wpdb;

	$table = appointments_get_table( 'exceptions' );

	$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE worker = %d", $worker_id ) );
}

function appointments_get_all_workers() {
	global $wpdb;

	$table = appointments_get_table( 'workers' );

	$workers = wp_cache_get( 'app_all_workers' );
	if ( false === $workers ) {
		$workers = array();
		$_workers = $wpdb->get_results( "SELECT * FROM $table" );
		foreach ( $_workers as $_worker ) {
			$workers[] = new Appointments_Worker( $_worker );
		}
		wp_cache_set( 'app_all_workers', $workers );
	}

	return $workers;
}

function appointments_update_worker_working_hours( $worker_id, $wh, $status, $location = 0 ) {
	global $wpdb;

	$table = appointments_get_table( 'wh' );

	$wh = maybe_serialize( $wh );

	if ( appointments_get_worker_working_hours( $status, $worker_id, $location ) ) {
		$result = $wpdb->update(
			$table,
			array(
				'hours'  => $wh,
				'status' => $status
			),
			array(
				'location' => $location,
				'worker'   => $worker_id,
				'status'   => $status
			),
			array( '%s', '%s' ),
			array( '%d', '%d', '%s' )
		);
	}
	else {
		$result = $wpdb->insert(
			$table,
			array(
				'location' => $location,
				'worker'   => $worker_id,
				'hours'    => $wh,
				'status'   => $status
			),
			array( '%d', '%d', '%s', '%s' )
		);
	}

	appointments_delete_work_breaks_cache( $location, $worker_id );
	appointments_delete_timetables_cache();
	wp_cache_delete( 'app_working_hours' );

	return $result;

}

/**
 * @param string $status open or closed
 * @param int $worker_id
 * @param int $location
 *
 * @return array|bool|mixed|null|object
 */
function appointments_get_worker_working_hours( $status, $worker_id = 0, $location = 0 ) {
	global $wpdb;

	$table = appointments_get_table( 'wh' );

	$cached = wp_cache_get( 'app_working_hours' );
	$cache_key = 'working_hours_' . $location . '_' . $worker_id . '_' . $status;
	if ( ! is_array( $cached ) ) {
		$cached = array();
	}

	$work_breaks = isset( $cached[ $cache_key ] ) ? $cached[ $cache_key ] : false;

	if ( false === $work_breaks ) {
		$work_breaks = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table WHERE worker=%d AND location=%d AND status = %s", $worker_id, $location, $status ) );
		if ( $work_breaks ) {
			$work_breaks->hours = maybe_unserialize( $work_breaks->hours );
			if ( is_array( $work_breaks->hours ) ) {
				foreach ( $work_breaks->hours as $weekday => $hour ) {
					// Transform weekday to weekday number for a better handling later
					$weekday_number = '';
					switch ( $weekday ) {
						case 'Monday': { $weekday_number = 1; break; }
						case 'Tuesday': { $weekday_number = 2; break; }
						case 'Wednesday': { $weekday_number = 3; break; }
						case 'Thursday': { $weekday_number = 4; break; }
						case 'Friday': { $weekday_number = 5; break; }
						case 'Saturday': { $weekday_number = 6; break; }
						case 'Sunday': { $weekday_number = 7; break; }
					}
					$work_breaks->hours[ $weekday ]['weekday_number'] = $weekday_number;
				}
			}
		}
		else {
			$work_breaks = false;
		}

		$cached[ $cache_key ] = $work_breaks;
		wp_cache_set( 'app_working_hours', $cached );
	}

	return $work_breaks;
}

function appointments_delete_worker_working_hours( $worker_id ) {
	global $wpdb;

	$table = appointments_get_table( 'wh' );

	$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE worker = %d", $worker_id ) );

	wp_cache_delete( 'app_working_hours' );
	appointments_delete_timetables_cache();
	appointments_delete_work_breaks_cache( 0, $worker_id );

}

/**
 * Get a worker list of exceptions
 *
 * @param int $worker_id
 * @param int $status
 *
 * @return false|object
 */
function appointments_get_worker_exceptions( $worker_id, $status, $location = 0 ) {
	global $wpdb;

	$worker_id = absint( $worker_id );
	if ( $worker_id && ! appointments_is_worker( $worker_id ) ) {
		return false;
	}

	$exception = null;
	$exceptions = wp_cache_get( 'app_worker_exceptions-' . $location . '-' . $worker_id );

	if ( false === $exceptions ) {
		$table = appointments_get_table( 'exceptions' );
		$exceptions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE worker=%d AND location=%d", $worker_id, $location ) );
		wp_cache_set( 'app_worker_exceptions-' . $location . '-' . $worker_id, $exceptions );
	}

	if ( $exceptions ) {
		foreach ( $exceptions as $e ) {
			if ( $e->status == $status ) {
				$exception = $e;
				break;
			}
		}
	}

	return $exception;
}

/**
 * @param $worker_id
 * @param string $status open or closed
 * @param string $days list of dates separated by comma
 * @param int $location
 *
 * @return bool|false|int
 */
function appointments_update_worker_exceptions( $worker_id, $status, $days, $location = 0 ) {
	global $wpdb;

	$worker_id = absint( $worker_id );
	if ( $worker_id && ! appointments_is_worker( $worker_id ) ) {
		return false;
	}

	if ( ! in_array( $status, array( 'open', 'closed' ) ) ) {
		return false;
	}

	$current_days = appointments_get_worker_exceptions( $worker_id, $status );
	$table = appointments_get_table( 'exceptions' );

	if ( ! $days ) {
		$days = '';
	}

	appointments_delete_worker_exceptions_cache( absint( $location ), $worker_id );
	if ( is_null( $current_days ) ) {
		$wpdb->insert(
			$table,
			array(
				'worker'   => $worker_id,
				'days'     => $days,
				'status'   => $status,
				'location'   => $location
			),
			array( '%d', '%s', '%s', '%d' )
		);

		return $wpdb->insert_id;
	}
	else {
		return $wpdb->update( $table,
			array(
				'days'   => $days
			),
			array(
				'worker'   => $worker_id,
				'status'   => $status,
				'location'   => $location
			),
			array( '%s', '%s' ),
			array( '%d', '%s', '%d' )
		);
	}
}


/**
 * Return the number of workers available for a given service during a interval of time
 *
 * Note: This function does not check agains worker's appointments
 *
 * @param int $start
 * @param int $end
 * @param int $service_id
 * @param bool|int $location
 *
 * @return int
 */
function appointments_get_available_workers_for_interval( $start, $end, $service_id, $location = false ) {
	$capacity = apply_filters( 'app_get_capacity', false, $service_id, null );
	if ( false !== $capacity ) {
		// Dont proceed further if capacity is forced
		return $capacity;
	}

	$service = appointments_get_service( $service_id );
	if ( ! $service ) {
		// This will mostly happen when Appointments->service = 0
		return 1;
	}

	$workers = appointments_get_workers_by_service( $service_id );
	if ( ! $workers ) {
		// If there are no workers for this service, apply the service capacity
		return appointments_get_capacity();
	}

	$capacity_available = 0;
	$available_workers = array();

	foreach( $workers as $worker ) {
		if ( appointments_is_worker_holiday( $worker->ID, $start, $end, $location ) ) {
			// If it's a worker exception, do not account this worker
			continue;
		}

		if ( appointments_is_interval_break( $start, $end, $worker->ID, $location ) ) {
			// Break for the worker, do not account
			continue;
		}

		// Try getting cached preprocessed hours
		$days = array();
		$result_days = appointments_get_worker_working_hours( 'open', $worker->ID, $location );
		if ( $result_days && is_object( $result_days ) && ! empty( $result_days->hours ) ) {
			$days = $result_days->hours;
		}

		if ( ! is_array( $days ) || empty( $days ) ) {
			continue;
		}


		if ( is_array( $days ) ) {
			// Filter days. Just get the correspondant weekday and if it's active
			$active_day = wp_list_filter(
				$days,
				array(
					'weekday_number' => date( "N", $start ),
					'active'         => 'yes'
				)
			);
			if ( ! empty( $active_day ) ) {
				// Results must only contain one result, let's get that result
				$active_day = current( $active_day );

				// The worker is working this weekday. Let's check the interval
				$start_active_datetime = strtotime( date( 'Y-m-d', $start ) . ' ' . $active_day['start'] . ':00' );
				if ( $active_day['end'] === '00:00' ) {
					// This means that the end time is on the next day
					$end_active_datetime = strtotime( date( 'Y-m-d 00:00:00', strtotime( '+1 day', $start ) ) );
				}
				else {
					$end_active_datetime   = strtotime( date( 'Y-m-d', $start ) . ' ' . $active_day['end'] . ':00' );
				}

				if (
					$start >= $start_active_datetime // The start time is greater than the active day start time
					&& $end <= $end_active_datetime // The end time is less than the active day end time. At this point we know that the searched dates are inside the interval
				) {
					$capacity_available++;
				}
			}
			unset( $active_day );
		}
	}

	// We have to check service capacity too
	if ( ! $service->capacity ) {
		// No service capacity limit
		return $capacity_available;
	}

	// Return whichever smaller
	return min( $service->capacity, $capacity_available );
}


/**
 * Check if an interval is a break
 *
 * @param $start
 * @param $end
 * @param int $worker_id Worker ID or 0 to check agains the main working days
 *
 * @return bool
 */
function appointments_is_interval_break( $start, $end, $worker_id = 0, $location = 0 ) {
	// Try getting cached preprocessed hours
	$days = array();

	// Preprocess and cache workhours
	// Look where our working hour ends
	$result_days = appointments_get_worker_working_hours( 'closed', $worker_id, $location );
	if ( $result_days && is_object( $result_days ) && ! empty( $result_days->hours ) ) {
		$days = $result_days->hours;
	}

	if ( ! is_array( $days ) || empty( $days ) ) {
		return false;
	}

	if ( is_array( $days ) ) {
		$weekday_number = date( "N", $start );

		foreach ( $days as $weekday => $day ) {
			$start_break_datetime = strtotime( date( 'Y-m-d', $start ) . ' ' . $day['start'] . ':00' );
			if ( $day['end'] === '00:00' ) {
				// This means that the end time is on the next day
				$end_break_datetime = strtotime( date( 'Y-m-d 00:00:00', strtotime( '+1 day', $start ) ) );
			}
			else {
				$end_break_datetime   = strtotime( date( 'Y-m-d', $start ) . ' ' . $day['end'] . ':00' );
			}


			if ( absint( $weekday_number ) === absint( $day['weekday_number'] ) && 'yes' === $day['active'] ) {
				// The weekday we're looking for and the break time is active
				if (
					$start >= $start_break_datetime // The start time is greater than the break day start time
					&& $end <= $end_break_datetime // The end time is less than the break day end time. At this point we know that the searched dates are inside the interval)
				) {
					return true;
				}
			} elseif ( absint( $weekday_number ) === absint( $day['weekday_number'] ) && is_array( $day['active'] ) ) {
				// The weekday we're looking for and the break day is composed by several times
				foreach ( $day["active"] as $idx => $active ) {
					if (
						$start >= $start_break_datetime // The start time is greater than the break day start time
						&& $end <= $end_break_datetime // The end time is less than the break day end time. At this point we know that the searched dates are inside the interval)
					) {
						return true;
					}
				}
			}
		}
	}

	return false;
}

/**
 * Check if an interval is a holiday for a worker
 *
 * @param $worker_id
 * @param $start
 * @param $end
 *
 * @return bool
 */
function appointments_is_worker_holiday( $worker_id, $start, $end, $location = false ) {
	$is_holiday = false;

	$worker = appointments_get_worker( $worker_id );
	if ( ! $worker ) {
		$worker_exceptions = array();
		$exceptions = appointments_get_worker_exceptions( $worker, 'closed', $location );

		if ( is_object( $exceptions ) ) {
			$worker_exceptions = explode( ',', $exceptions->days );
		}
	}
	else {
		$worker_exceptions = $worker->get_exceptions( 'closed' );
	}

	if (
		in_array( date( 'Y-m-d', $start ), $worker_exceptions )
		|| in_array( date( 'Y-m-d', $end ), $worker_exceptions )
	) {
		return true;
	}

	return apply_filters( 'app_is_holiday', $is_holiday, $start, $end, null, $worker_id );
}


function appointments_delete_worker_cache( $worker_id = 0 ) {
	wp_cache_delete( $worker_id, 'app_workers' );
	wp_cache_delete( 'app_get_workers' );
	wp_cache_delete( 'app_count_workers' );
	wp_cache_delete( 'app_all_workers' );
	wp_cache_delete( 'app_workers_by_service' );
	wp_cache_delete( 'app_working_hours' );
	//@ TODO: Delete capacity_ cache
	appointments_delete_timetables_cache();
}

function appointments_delete_work_breaks_cache( $l, $w ) {
	$cache_key = 'appointments_work_breaks-' . $l . '-' . $w;
	wp_cache_delete( $cache_key );
	appointments_delete_timetables_cache();
}

function appointments_delete_worker_exceptions_cache( $location, $worker_id ) {
	$cache_key = 'app_worker_exceptions-' . $location . '-' . $worker_id;
	wp_cache_delete( $cache_key );
	appointments_delete_timetables_cache();
}
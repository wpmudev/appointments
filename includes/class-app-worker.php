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

	public function get_exceptions() {
		global $wpdb;

		$table = appointments_get_table( 'exceptions' );

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE worker = %d", $this->ID ) );
	}

	public function get_services() {
		if ( empty( $this->services_provided ) )
			return array();

		return array_map( 'appointments_get_service', $this->services_provided );
	}

}

function appointments_get_worker( $worker_id ) {
	global $wpdb;

	$table = appointments_get_table( 'workers' );

	$worker = wp_cache_get( $worker_id, 'app_workers' );

	if ( ! $worker ) {
		$worker = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * from $table WHERE ID = %d",
				$worker_id
			)
		);

		if ( $worker )
			wp_cache_add( $worker->ID, $worker, 'app_workers' );
	}


	if ( $worker )
		return new Appointments_Worker( $worker );

	return false;
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
		appointments_delete_worker_cache( $ID );

		// Set default working hours
		$wh_table = appointments_get_table( 'wh' );
		$ex_table = appointments_get_table( 'exceptions' );

		// Insert the default working hours and holidays to the worker's working hours
		foreach ( array( 'open', 'closed' ) as $stat ) {
			$result_wh = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wh_table WHERE location=0 AND service=0 AND status=%s", $stat ), ARRAY_A );
			if ( $result_wh != null ) {
				unset( $result_wh["ID"] );
				$result_wh["worker"] = $args['ID'];
				$wpdb->insert(
					$wh_table,
					$result_wh
				);
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
	$allowed_orderby = $whitelist = apply_filters( 'app_order_by_whitelist', array( 'ID', 'name', 'start', 'end', 'duration', 'price',
		'ID DESC', 'name DESC', 'start DESC', 'end DESC', 'duration DESC', 'price DESC', 'RAND()', 'name ASC', 'name DESC' ) );

	$order_query = "";

	if ( in_array( $args['orderby'], $allowed_orderby ) ) {
		$orderby = apply_filters( 'app_get_workers_orderby', $args['orderby'] );
		$order_query = "ORDER BY $orderby";
	}

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

		if ( $get_col )
			$query = "SELECT $field FROM $table w $where $order_query $limit_query";
		else
			$query = "SELECT * FROM $table w $where $order_query $limit_query";

		$cache_key = md5( $query . '-' . 'app_get_workers' );
		$cached_queries = wp_cache_get( 'app_get_workers' );

		if ( ! is_array( $cached_queries ) )
			$cached_queries = array();

		if ( ! isset( $cached_queries[ $cache_key ] ) ) {

			if ( $get_col )
				$results = $wpdb->get_col( $query );
			else
				$results = $wpdb->get_results( $query );

			if ( ! empty( $results ) ) {
				$cached_queries[ $cache_key ] = $results;
				wp_cache_set( 'app_get_workers', $cached_queries );
			}

		}
		else {
			$results = $cached_queries[ $cache_key ];
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

function appointments_get_workers_by_service( $service_id, $order_by = 'ID' ) {
	$workers = appointments_get_workers( array( 'orderby' => $order_by ) );
	$filtered_workers = array();
	foreach ( $workers as $worker ) {
		/** @var Appointments_Worker $worker */
		if ( in_array( $service_id, $worker->services_provided ) ) {
			$filtered_workers[] = $worker;
		}
	}

	return $filtered_workers;

}

function appointments_delete_worker( $worker_id ) {
	global $wpdb;

	if ( ! appointments_get_worker( $worker_id ) )
		return false;

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

	if ( $result ) {
		appointments_delete_worker_cache( $worker_id );
		return true;
	}

	return false;
}


function appointments_get_worker_services( $worker_id ) {
	$worker = appointments_get_worker( $worker_id );
	if ( $worker )
		return $worker->get_services();

	return array();
}

function appointments_delete_worker_working_hours( $worker_id ) {
	global $wpdb;

	$table = appointments_get_table( 'wh' );

	$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE worker = %d", $worker_id ) );
}

function appointments_delete_worker_exceptions( $worker_id ) {
	global $wpdb;

	$table = appointments_get_table( 'exceptions' );

	$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE worker = %d", $worker_id ) );
}


function appointments_delete_worker_cache( $worker_id ) {
	wp_cache_delete( $worker_id, 'app_workers' );
	wp_cache_delete( 'app_get_workers' );
	wp_cache_delete( 'app_count_workers' );
	appointments_delete_timetables_cache();
}
<?php

class Appointments_Service {

	public $ID = '';
	public $name = '';
	public $capacity = 0;
	public $duration = 0;
	public $price = '';
	public $page = 0;

	public function __construct( $service ) {
		foreach ( get_object_vars( $service ) as $key => $value ) {
			$this->$key = $this->_sanitize_field( $key, $value );
		}
	}

	private function _sanitize_field( $field, $value ) {
		$int_fields = array( 'ID', 'duration', 'page', 'capacity' );

		if ( in_array( $field, $int_fields ) )
			return absint( $value );
		else
			return $value;
	}
}

/**
 * Insert a Service
 *
 * @param array $args
 *
 * @return bool
 */
function appointments_insert_service( $args = array() ) {
	global $wpdb;

	$table = appointments_get_table( 'services' );

	$defaults = array(
		'ID' => false,
		'name' => '',
		'capacity' => 0,
		'duration' => 0,
		'price' => '',
		'page' => 0
	);

	$args = wp_parse_args( $args, $defaults );

	$insert = array();
	$insert_wildcards = array();

	// Service ID
	$ID = absint( $args['ID'] );
	if ( $ID ) {
		$insert['ID'] = $ID;
		$insert_wildcards[] = '%d';
	}
	else {
		// No ID, insert the next ID
		$ID = $wpdb->get_var( "SELECT MAX(ID) FROM $table" );
		if ( ! $ID )
			$ID = 1;

		$ID++;

		$insert['ID'] = $ID;
		$insert_wildcards[] = '%d';
	}

	// Service name
	$name = trim( $args['name'] );
	if ( empty( $name ) )
		return false;

	$insert['name'] = $name;
	$insert_wildcards[] = '%s';

	// Capacity
	$insert['capacity'] = absint( $args['capacity'] );
	$insert_wildcards[] = '%d';

	// Duration
	$duration = absint( $args['duration'] );
	if ( ! $duration )
		$duration = 30;

	$insert['duration'] = $duration;
	$insert_wildcards[] = '%d';

	// Price
	$price = preg_replace( "/[^0-9,.]/", "", $args['price'] );
	if ( $price !== '' ) {
		if ( ! $price )
			$price = '';
	}
	$insert['price'] = $price;
	$insert_wildcards[] = '%s';

	// Page
	$page_id = absint( $args['page'] );
	$page = get_post( $page_id );
	if ( $page && $page->post_type == 'page' ) {
		$insert['page'] = $page_id;
		$insert_wildcards[] = '%d';
	}

	$r = $wpdb->insert( $table, $insert, $insert_wildcards );

	if ( $r ) {
		appointments_delete_service_cache( $ID );
		return $ID;
	}

	return false;
}


/**
 *
 * Update a service
 *
 * @param $service_id
 * @param $args
 *
 * @return bool|false|int
 */
function appointments_update_service( $service_id, $args ) {
	global $wpdb;

	$old_service = appointments_get_service( $service_id );
	if ( ! $old_service )
		return false;

	$fields = array( 'ID' => '%d', 'name' => '%s', 'capacity' => '%d', 'duration' => '%d', 'price' => '%s', 'page' => '%d' );

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

	if ( empty( $update ) )
		return false;

	$table = appointments_get_table( 'services' );

	$result = $wpdb->update(
		$table,
		$update,
		array( 'ID' => $service_id ),
		$update_wildcards,
		array( '%d' )
	);

	if ( $result )
		appointments_delete_service_cache( $service_id );

	do_action( 'wpmudev_appointments_update_service', $service_id, $args, $old_service );

	return (bool)$result;
}


/**
 * Get a single service with given ID
 *
 * @param ID: Id of the service to be retrieved
 * @return object
 */
function appointments_get_service( $service_id ) {
	global $wpdb;

	$table = appointments_get_table( 'services' );

	$service = wp_cache_get( $service_id, 'app_services' );

	if ( ! $service ) {
		$service = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * from $table WHERE ID = %d",
				$service_id
			)
		);

		if ( $service )
			wp_cache_add( $service->ID, $service, 'app_services' );
	}


	if ( $service )
		return new Appointments_Service( $service );

	return false;
}

/**
 * Get a list of services
 *
 * @param array $args {
 *     Optional. Arguments to retrieve services.
 *
 *     @type string         $orderby          orderby field (possible values ID, ID ASC, ID DESC, name ASC, name DESC). Default ID
 *     @type bool|int       $page             Filter by attached page to service. Default false
 *     @type bool           $count            If set to true, it will return the number of services found. Default false
 *     @type bool|string    $fields           Fields to be returned (false or 'ID'). If false it will return all fields. Default false.
 * }
 *
 * @return array of Appointments_Service
 */
function appointments_get_services( $args = array() ) {
	global $wpdb;

	$defaults = array(
		'orderby' => 'ID',
		'page' => false, // Filter by page ID
		'count' => false,
		'fields' => false
	);

	$args = wp_parse_args( $args, $defaults );

	$table = appointments_get_table( 'services' );

	$where = array();
	$page_id = absint( $args['page'] );

	if ( $page_id )
		$where[] = $wpdb->prepare( "s.page = %d", $page_id );

	// @TODO: We need to move this to somewhere else
	$allowed_orderby = $whitelist = apply_filters( 'app_order_by_whitelist', array( 'ID', 'name', 'start', 'end', 'duration', 'price',
			'ID DESC', 'name DESC', 'start DESC', 'end DESC', 'duration DESC', 'price DESC', 'RAND()' ) );

	$order_query = "";

	if ( in_array( $args['orderby'], $allowed_orderby ) ) {
		$orderby = $args['orderby'];
		$order_query = "ORDER BY $orderby";
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
			$query = "SELECT $field FROM $table s $where $order_query";
		else
			$query = "SELECT * FROM $table s $where $order_query";

		$cache_key = md5( $query . '-' . 'app_get_services' );

		$cached_queries = wp_cache_get( 'app_get_services' );
		if ( ! is_array( $cached_queries ) )
			$cached_queries = array();

		if ( ! isset( $cached_queries[ $cache_key ] ) ) {

			if ( $get_col )
				$results = $wpdb->get_col( $query );
			else
				$results = $wpdb->get_results( $query );

			if ( ! empty( $results ) ) {
				$cached_queries[ $cache_key ] = $results;
				wp_cache_set( 'app_get_services', $cached_queries );
			}

		}
		else {
			$results = $cached_queries[ $cache_key ];
		}

		$services = array();
		if ( ! $get_col ) {
			foreach ( $results as $result ) {
				wp_cache_add( $result->ID, $result, 'app_services' );
				$services[] = new Appointments_Service( $result );
			}
		}
		else {
			$services = $results;
		}


		return $services;

	}
	else {
		$query = "SELECT COUNT(ID) FROM $table s $where";
		$cache_key = md5( $query . '-' . 'app_count_services' );

		$cached_queries = wp_cache_get( 'app_count_services' );
		if ( ! is_array( $cached_queries ) )
			$cached_queries = array();

		if ( ! isset( $cached_queries[ $cache_key ] ) ) {
			$result = $wpdb->get_var( $query );
			$result = absint( $result );
			$cached_queries[ $cache_key ] = absint( $result );
			wp_cache_set( 'app_count_services', $cached_queries );
		}
		else {
			$result = $cached_queries[ $cache_key ];
		}


		return $result;
	}

}

/**
 * Get smallest service ID
 *
 * @return integer
 */
function appointments_get_services_min_id() {
	global $wpdb;

	$min = wp_cache_get( 'min_service_id', 'appointments_services' );
	if ( false === $min ) {
		$table = appointments_get_table( 'services' );
		$min = $wpdb->get_var( "SELECT MIN(ID) FROM $table");
		if ( ! $min ) {
			$min = 0;
		}

		$min = absint( $min );
		wp_cache_set( 'min_service_id', $min, 'appointments_services' );
	}
	return apply_filters( 'app-services-first_service_id', $min );
}

function appointments_count_services( $args = array() ) {
	$args['count'] = true;
	return appointments_get_services( $args );
}

function appointments_get_services_min_price() {
	global $wpdb;

	$table = appointments_get_table( 'services' );

	$result = $wpdb->get_var( "SELECT MIN(price) FROM $table WHERE price > 0");
	return $result;
}

/**
 * Delete a service
 *
 * @param int $service_id
 *
 * @return bool|false|int
 */
function appointments_delete_service( $service_id ) {
	global $wpdb;

	if ( ! appointments_get_service( $service_id ) )
		return false;

	$table = appointments_get_table( 'services' );

	$service_id = absint( $service_id );

	$result = $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM $table WHERE ID = %d" ,
			$service_id
		)
	);

	// Remove the service from all workers
	$table = appointments_get_table( 'workers' );
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE $table SET services_provided = REPLACE( services_provided,':%d:','' )",
			$service_id
		)
	);

	if ( $result ) {
		appointments_delete_service_cache( $service_id );
		return true;
	}

	return false;

}

function appointments_delete_service_cache( $service_id ) {
	wp_cache_delete( $service_id, 'app_services' );
	wp_cache_delete( 'app_get_services' );
	wp_cache_delete( 'app_count_services' );
	wp_cache_delete( 'min_service_id', 'appointments_services' );
	appointments_delete_timetables_cache();
}


function appointments_delete_services_cache() {
	wp_cache_delete( 'appointments_services_orderby', 'appointments_services' );
	wp_cache_delete( 'appointments_services_results', 'appointments_services' );
	wp_cache_delete( 'min_service_id', 'appointments_services' );
	appointments_delete_timetables_cache();
}


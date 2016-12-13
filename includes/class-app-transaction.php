<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Appointments_Transaction' ) ) {
	class Appointments_Transaction {

		public $transaction_ID = 0;
		public $transaction_app_ID = 0;
		public $transaction_paypal_ID = 0;
		public $transaction_stamp = 0;
		public $transaction_total_amount = 0;
		public $transaction_currency = '';
		public $transaction_status = '';
		public $transaction_note = '';

		public function __construct( $transaction ) {
			foreach ( get_object_vars( $transaction ) as $key => $value ) {
				$this->$key = $this->_sanitize_field( $key, $value );
			}
		}

		public function __get( $name ) {
			$value = false;
			if ( isset( $this->$name ) ) {
				$value = $this->$name;
			}

			$value = apply_filters( 'appointments_get_transaction_attribute', $value, $name );
			return $value;
		}

		private function _sanitize_field( $field, $value ) {
			// @TODO Sanitize
			return $value;
		}

		/**
		 * Return the Appointment associated to this transaction
		 *
		 * @return false|Appointments_Appointment
		 */
		public function get_appointment() {
			return appointments_get_appointment( $this->transaction_app_ID );
		}
	}
}

/**
 * Get transaction records
 * Modified from Membership plugin by Barry
 *
 * @param array $args
 *
 * @return array|null|object
 */
function appointments_get_transactions( $args = array() ) {
	global $wpdb;

	$defaults = array(
		'per_page' => 10,
		'page' => 1,
		'offset' => false,
		'type' => 'any',
		'orderby' => 'transaction_ID',
		'order' => 'DESC',
		'transaction_app_ID' => false,
		'transaction_paypal_ID' => false,
		'transaction_stamp' => false,
		'count' => false

	);
	$args = wp_parse_args( $args, $defaults );

	$table = appointments_get_table( 'transactions' );
	$select = "SELECT SQL_CALC_FOUND_ROWS * FROM $table";

	$where = array( "1=1" );

	switch ( $args['type'] ) {
		case 'past': {
			$where[] = "transaction_status NOT IN ('Pending', 'Future')";
			break;
		}
		case 'pending': {
			$where[] = "transaction_status IN ('Pending')";
			break;
		}
		case 'future': {
			$where[] = "transaction_status IN ('Future')";
			break;
		}
	}

	if ( $args['transaction_app_ID'] ) {
		$where[] = $wpdb->prepare( "transaction_app_ID = %d", $args['transaction_app_ID'] );
	}
	if ( $args['transaction_paypal_ID'] ) {
		$where[] = $wpdb->prepare( "transaction_paypal_ID = %s", $args['transaction_paypal_ID'] );
	}
	if ( $args['transaction_stamp'] ) {
		$where[] = $wpdb->prepare( "transaction_stamp = %d", $args['transaction_stamp'] );
	}

	$allowed_order_by = array( 'transaction_ID' );
	if ( ! in_array( $args['orderby'] , $allowed_order_by ) ) {
		$args['orderby'] = 'transaction_ID';
	}

	$allowed_order = array( 'ASC', 'DESC' );
	if ( ! in_array( strtoupper( $args['order'] ), $allowed_order ) ) {
		$args['order'] = 'DESC';
	}
	else {
		$args['order'] = strtoupper( $args['order'] );
	}

	$order_query = "ORDER BY {$args['orderby']} {$args['order']}";

	if ( $args['offset'] ) {
		$limit = $wpdb->prepare( "LIMIT %d, %d", intval( $args['offset'] ), intval( $args['per_page'] ) );
	}
	else {
		$limit = $wpdb->prepare( "LIMIT %d, %d", intval( ( $args['page'] - 1 ) * $args['per_page'] ), intval( $args['per_page'] ) );
	}

	$where = "WHERE " . implode( " AND ", $where );

	$query = "$select $where $order_query $limit";

	$results = $wpdb->get_results( $query );

	$transactions = array();
	foreach ( $results as $result ) {
		$transactions[] = appointments_get_transaction( $result );
	}

	if ( $args['count'] ) {
		return $wpdb->get_var( "SELECT FOUND_ROWS();" );
	}

	return $transactions;
}

/**
 * Get a transaction instance
 *
 * @param $id
 *
 * @return Appointments_Transaction|bool
 */
function appointments_get_transaction( $id ) {
	global $wpdb;

	if ( is_a( $id, 'Appointments_Transaction' ) ) {
		return $id;
	}
	elseif ( is_object( $id ) ) {
		return new Appointments_Transaction( $id );
	}

	$table = appointments_get_table( 'transactions' );

	$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE transaction_ID = %d", $id ) );

	if ( ! $result ) {
		return false;
	}

	return new Appointments_Transaction( $result );
}

/**
 * Insert a new Transaction
 *
 * @param array $args
 *
 * @return bool|int
 */
function appointments_insert_transaction( $args = array() ) {
	global $wpdb;

	$defaults = array(
		'app_ID' => 0,
		'paypal_ID' => '',
		'stamp' => 0,
		'total_amount' => 0,
		'currency' => '',
		'status' => '',
		'note' => '',
	);

	$table = appointments_get_table( 'transactions' );

	$args = wp_parse_args( $args, $defaults );

	if ( $transaction = appointments_get_transaction_by_paypal_id( $args['paypal_ID'] ) ) {
		// Update
		appointments_update_transaction( $transaction->transaction_ID, $args );
	}

	$args['total_amount'] = (int) round( $args['total_amount'] * 100 );

	$insert = array();
	foreach ( $args as $key => $arg ) {
		$insert[ 'transaction_' . $key ] = $arg;
	}

	$result = $wpdb->insert(
		$table,
		$insert,
		array( '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
	);

	if ( $result ) {
		return $wpdb->insert_id;
	}

	return false;
}

/**
 * Delete an transaction forever
 *
 * @param $id
 *
 * @return bool
 */
function appointments_delete_transaction( $id ) {
	global $wpdb;

	$transaction = appointments_get_transaction( $id );
	if ( ! $transaction ) {
		return false;
	}

	$table = appointments_get_table( 'transactions' );
	$result = $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE transaction_ID = %d", $id ) );

	/**
	 * Triggered after an appointment has been deleted
	 *
	 * @param Appointments_Transaction $transaction
	 */
	do_action( 'appointments_delete_transaction', $transaction );

	return (bool)$result;
}

/**
 * Find if a Paypal transaction is duplicate or not
 *
 * @param $app_id
 * @param $timestamp
 * @param $paypal_ID
 *
 * @return bool
 */
function appointments_is_transaction_duplicated( $app_id, $timestamp, $paypal_ID ) {
	$transactions = appointments_get_transactions( array(
		'per_page' => 1,
		'transaction_app_ID' => $app_id,
		'transaction_paypal_ID' => $paypal_ID,
		'transaction_stamp' => $timestamp,
	) );


	if ( ! empty( $transactions ) ) {
		return true;
	}
	else {
		return false;
	}
}

/**
 * Get a transaction based on its Paypal ID
 *
 * @param $paypal_id
 *
 * @return Appointments_Transaction|bool
 */
function appointments_get_transaction_by_paypal_id( $paypal_id ) {
	global $wpdb;

	$table = appointments_get_table( 'transactions' );
	$result = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT transaction_ID FROM {$table} 
			WHERE transaction_paypal_ID = %s 
			LIMIT 1",
			$paypal_id
		)
	);

	if ( $result ) {
		return appointments_get_transaction( $result );
	}

	return false;
}


/**
 * Update a transaction
 *
 * @param $transaction_id
 * @param $args
 *
 * @return bool
 */
function appointments_update_transaction( $transaction_id, $args ) {
	global $wpdb;

	$old_transaction = appointments_get_transaction( $transaction_id );
	if ( ! $old_transaction ) {
		return false;
	}

	$fields = array(
		'app_ID' => '%d',
		'paypal_ID' => '%s',
		'stamp' => '%d',
		'total_amount' => '%d',
		'currency' => '%s',
		'status' => '%s',
		'note' => '%s',
	);

	$update = array();
	$update_wildcards = array();
	foreach ( $fields as $field => $wildcard ) {
		if ( isset( $args[ $field ] ) && $wildcard ) {
			$update[ 'transaction_' . $field ] = $args[ $field ];
			$update_wildcards[] = $wildcard;
		}
	}

	if ( isset( $update['transaction_total_amount'] ) ) {
		$update['transaction_total_amount'] = (int) round( $update['transaction_total_amount'] * 100 );
	}

	if ( empty( $update ) ) {
		return false;
	}

	$result = false;
	if ( ! empty( $update ) ) {
		$table = appointments_get_table( 'transactions' );

		$result = $wpdb->update(
			$table,
			$update,
			array( 'transaction_ID' => $transaction_id ),
			$update_wildcards,
			array( '%d' )
		);
	}


	$result = apply_filters( 'appointments_update_transaction_result', $result, $transaction_id, $args, $old_transaction );

	if ( ! $result ) {
		// Nothing has changed
		return false;
	}

	do_action( 'appointments_update_transaction', $transaction_id, $args, $old_transaction );

	appointments_get_transaction( $transaction_id );

	return true;
}
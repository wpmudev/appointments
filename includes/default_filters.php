<?php

if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	function _app_catch_wrong_pattern_debug_notices( $where ) {
		if ( apply_filters( 'doing_it_wrong_trigger_error', true ) ) { trigger_error( sprintf( 'Wrong usage pattern detected when calling %s', $where ) ); }
	}
	add_action( 'app-core-doing_it_wrong', '_app_catch_wrong_pattern_debug_notices' );
}

if ( ! (defined( 'APP_DISALLOW_META_WRAPPERS' ) && APP_DISALLOW_META_WRAPPERS) ) {
	function _app_wrap_meta_output( $out ) {
		if ( ! $out ) { return $out; }
		return '<div class="app-settings-column_meta_info">' .
			'<div class="app-settings-column_meta_info-content" style="display:none">' . $out . '</div>' .
			'<div>' .
				'<a href="#toggle" class="app-settings-column_meta_info-toggle" ' .
					'data-off="' . esc_attr( __( 'More Info', 'appointments' ) ) . '" ' .
					'data-on="' . esc_attr( __( 'Less Info', 'appointments' ) ) . '" ' .
				'>' . __( 'More Info', 'appointments' ) . '</a>' .
			'</div>' .
		'</div>';
	}
	add_filter( 'app-settings-services-service-name', '_app_wrap_meta_output', 9991 );
	add_filter( 'app-settings-workers-worker-name', '_app_wrap_meta_output', 9991 );
}

if ( defined( 'APP_GCAL_CLIENT_TEMP_DIR_AUTO_LOOKUP' ) && APP_GCAL_CLIENT_TEMP_DIR_AUTO_LOOKUP ) {
	/**
	 * Wrapper for Google Client cache filepath + open_basedir restriction resolution.
	 */
	function _app_gcal_client_temp_dir_lookup( $params ) {
		if ( ! function_exists( 'get_temp_dir' ) ) { return $params; }
		$params['ioFileCache_directory'] = get_temp_dir() . 'Google_Client';
		return $params;
	}
	add_filter( 'app-gcal-client_parameters', '_app_gcal_client_temp_dir_lookup' );
}

if ( ! (defined( 'APP_USE_LEGACY_MP_INTEGRATION' ) && APP_USE_LEGACY_MP_INTEGRATION) ) {

	/**
	 * Appointment-to-order mapping getter.
	 */
	function app_mp_get_order_for_appointment( $app_id ) {
		$query = new WP_Query(array(
			'post_type' => 'mp_order',
			//'post_status' => 'order_paid', // Can't use this, at least for manual payments gateway because post status transition happens AFTER the paid hook (o.0 yeah)
			'post_status' => array( 'order_paid', 'order_received' ), // This is much less appropriate IMHO, but we'll be narrowing it down with `mp_paid_time` meta
			'meta_query' => array(
			'relation' => 'AND',
			array(
					'key' => '_appointment_purchased_via_mp',
					'value' => $app_id,
				),
			// Since we absolutely can't rely on order status, let's hack our way around
				array(
					'key' => 'mp_paid_time',
					'value' => 0,
					'compare' => '>',
				),
			),
			'fields' => 'ids',
		));
		if ( empty( $query->posts ) || empty( $query->posts[0] ) ) { return false; // Can't map
		}		$order_id = (int) $query->posts[0];

		global $mp;
		if ( ! method_exists( $mp, 'get_order' ) ) { return false; // Erm... no MarketPress
		}
		return $mp->get_order( $order_id );
	}

	/**
	 * Order-to-appointments mapping.
	 */
	function app_mp_get_appointment_ids_from_order( $order ) {
		$order_id = is_object( $order ) && ! empty( $order->ID )
			? $order->ID
			: (is_numeric( $order ) ? $order : false)
		;
		if ( ! $order_id ) { return false; }

		$purchased = get_post_meta( $order_id, '_appointment_purchased_via_mp' );
		if ( ! empty( $purchased ) ) { return $purchased; // Easy way out for already paid order
		}
		// No easy way? Oh well...
		if ( empty( $order->mp_cart_info ) ) { return false; }
		$app_ids = array();
		foreach ( $order->mp_cart_info as $product_id => $product_detail ) {
			foreach ( $product_detail as $var ) {
				list($product_name, $app_id) = split( ':', $var['name'] );
				if ( ! empty( $app_id ) && is_numeric( $app_id ) ) { $app_ids[] = $app_id; }
			}
		}
		return array_values( array_filter( array_map( 'intval', array_unique( $app_ids ) ) ) );
	}

	/**
	 * This is where the link actually gets established.
	 */
	function _app_mp_record_appointment_order_info( $app_id, $order ) {
		if ( empty( $order->ID ) || empty( $app_id ) ) { return false; }
		add_post_meta( $order->ID, '_appointment_purchased_via_mp', $app_id, false );
		wp_cache_delete( $order->ID, 'post_meta' );
	}
	add_filter( 'app_mp_order_paid', '_app_mp_record_appointment_order_info', 10, 2 );
}


/**
 * Triggered when a user cancels an appointment from email link
 */
function appointments_maybe_cancel_appointment() {
	if ( ! isset( $_GET['action'] ) || 'cancel-app' !== $_GET['action'] ) {
		return;
	}
	$app_id = absint( $_GET['id'] );
	$token = rawurldecode( $_GET['t'] );
	$app = appointments_get_appointment( $app_id );
	if ( ! $app ) {
		wp_die( __( 'The appointment does not exist.', 'appointments' ), __( 'Error', 'appointments' ), array( 'response' => 400 ) );
	}
	if ( $token != $app->get_cancel_token() ) {
		wp_die( __( 'You are not allowed to perform this action.', 'appointments' ), __( 'Error', 'appointments' ), array( 'response' => 403 ) );
	}
	// Remove the gcal appointment
	$appointments = appointments();
	$gcal = $appointments->get_gcal_api();
	$args['status'] = 'removed';
	$gcal->on_update_appointment( $app_id, $args, $app );
	$options = appointments_get_options();
	appointments_cancel_appointment( $app_id );
	$url = get_permalink( $options['cancel_page'] );
	if ( $url ) {
		wp_redirect( $url );
		exit;
	} else {
		wp_die( __( 'Your appointment has been cancelled!', 'appointments' ), __( 'Appointment cancelled', 'appointments' ), array( 'response' => 410 ) );
	}
}
add_action( 'init', 'appointments_maybe_cancel_appointment' );

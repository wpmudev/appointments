<?php

global $appointments_deprecated_filters, $appointments_deprecated_actions;

$appointments_deprecated_actions = array(
	'wpmudev_appointments_update_appointment_status' => 'app_change_status',
	'app_new_appointment' => 'wpmudev_appointments_insert_appointment'
);

$appointments_deprecated_filters = array(
	//'wp_footer' => 'app_footer_scripts'
);

foreach ( $appointments_deprecated_actions as $new => $old ) {
	add_action( $new, '_appointments_deprecated_actions_map', 10, 4 );
}

foreach ( $appointments_deprecated_filters as $new => $old ) {
	add_filter( $new, '_appointments_deprecated_filters_map', 10, 4 );
}

/**
 * @private
 */
function _appointments_deprecated_actions_map( $arg_1 = '', $arg_2 = '', $arg_3 = '', $arg_4 = '' ) {
	global $appointments_deprecated_actions;

	$action = current_action();

	if ( isset( $appointments_deprecated_actions[ $action ] ) ) {
		if ( has_action( $appointments_deprecated_actions[ $action ] ) ) {
			do_action( $appointments_deprecated_actions[ $action ], $arg_1, $arg_2, $arg_3, $arg_4 );
			if ( ! defined( 'DOING_AJAX' ) ) {
				_deprecated_function( 'The ' . $appointments_deprecated_actions[ $action ] . ' action', '', $action );
			}
		}
	}
}

function _appointments_deprecated_filters_map( $arg_1 = '', $arg_2 = '', $arg_3 = '', $arg_4 = '' ) {
	global $appointments_deprecated_filters;

	$filter = current_filter();
	if ( isset( $appointments_deprecated_filters[ $filter ] ) ) {
		if ( has_action( $appointments_deprecated_filters[ $filter ] ) ) {
			$arg_1 = apply_filters( $appointments_deprecated_filters[ $filter ], $arg_1, $arg_2, $arg_3, $arg_4 );
			if ( ! defined( 'DOING_AJAX' ) ) {
				_deprecated_function( 'The ' . $appointments_deprecated_filters[ $filter ] . ' filter', '', $filter );
			}
		}
	}

	return $arg_1;
}

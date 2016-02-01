<?php

global $appointments_deprecated_filters, $appointments_deprecated_actions;

$appointments_deprecated_actions = array(
	'wpmudev_appointments_update_appointment_status' => 'app_change_status'
);

$appointments_deprecated_filters = array();

foreach ( $appointments_deprecated_actions as $new => $old ) {
	add_filter( $new, '_appointments_deprecated_actions_map' );
}

/**
 * @private
 */
function _appointments_deprecated_actions_map( $arg_1 = '', $arg_2 = '', $arg_3 = '', $arg_4 = '' ) {
	global $appointments_deprecated_actions;

	$action = current_action();

	if ( isset( $appointments_deprecated_actions[ $action ] ) ) {
		if ( has_action( $appointments_deprecated_actions[ $action ] ) ) {
			add_action( $appointments_deprecated_actions[ $action ], $arg_1, $arg_2, $arg_3, $arg_4 );
			if ( ! is_ajax() ) {
				_deprecated_function( 'The ' . $appointments_deprecated_actions[ $action ] . ' action', '', $action );
			}
		}
	}
}

<?php

/**
 * @internal
 *
 * @param string $slug
 *
 * @return array
 */
function _appointments_get_admin_notice( $slug ) {

	$gcal_tab_url = add_query_arg(
		array( 'page' => 'app_settings', 'tab' => 'gcal' ),
		admin_url( 'admin.php' )
	);

	$notices = array(
		'1-7-gcal' => sprintf(
			_x( '%s has changed on 1.7 version. Review your settings if you were using it prior to 1.7.', 'Google Calendar Settings admin notice fo 1.7 upgrade.', 'appointments' ),
			'<a href="' . esc_url( $gcal_tab_url ) . '">' . __( 'Google Calendar Settings', 'appointments' ) . '</a>'
		)
	);

	return isset( $notices[ $slug ] ) ? $notices[ $slug ] : false;
}

/**
 * @internal
 * @return array
 */
function _appointments_get_admin_notices() {
	return get_option( 'app_admin_notices', array() );
}

/**
 * @internal
 * @return array
 */
function _appointments_get_user_dismissed_notices( $user_id ) {
	$dismissed = get_user_meta( $user_id, 'app_dismissed_notices', true );
	if ( ! is_array( $dismissed ) ) {
		$dismissed = array();
	}
	return $dismissed;
}
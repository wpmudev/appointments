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
			_x( '%s have changed on version 1.7. If you have been using Google Calendar prior to 1.7 please review your settings.', 'Google Calendar Settings admin notice fo 1.7 upgrade.', 'appointments' ),
			'<a href="' . esc_url( $gcal_tab_url ) . '">' . __( 'Google Calendar Settings', 'appointments' ) . '</a>'
		),
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

/**
 * @param $name
 * @internal
 * @return bool|string
 */
function _appointments_get_view_path( $name ) {
	if ( ! function_exists( 'appointments_get_view_path' ) ) {
		include_once( appointments_plugin_dir() . 'includes/helpers.php' ); }

	return appointments_get_view_path( $name );
}

/**
 * @param $tab
 * @internal
 * @return bool|string
 */
function _appointments_get_settings_tab_view_file_path( $tab ) {
	$file = "page-settings-tab-$tab";
	return apply_filters( "appointments_get_settings_tab_view-$tab", _appointments_get_view_path( $file ) );
}

/**
 * @param $tab
 * @param $section
 * @internal
 * @return bool|string
 */
function _appointments_get_settings_section_view_file_path( $tab, $section ) {
	$file = "page-settings-tab-$tab-section-$section";
	return apply_filters( "appointments_get_settings_tab_section_view-$tab", _appointments_get_view_path( $file ) );
}

/**
 * @internal
 * @param string $tab
 * @param string $text Submit button text
 * @param string $class primary|secondary
 */
function _appointments_settings_submit_block( $tab, $text = '', $class = 'primary' ) {
	if ( ! $text ) {
		$text = __( 'Save Changes', 'appointments' );
	}

	?>
		<input type="hidden" name="action_app" value="save_<?php echo $tab; ?>"/>
		<?php wp_nonce_field( 'update_app_settings', 'app_nonce' ); ?>
		<?php submit_button( $text, $class ); ?>
	<?php
}

function _appointments_init_multidatepicker() {
	wp_add_inline_script( 'app-multi-datepicker', 'jQuery( document ).ready( function( $ ) { $( ".app-datepick" ).each( function() { new AppDatepicker( $(this) ); } ); } );' );
}

/**
 * Produce checkbox.switch-button
 *
 * @since 2.2.5
 *
 * @param array $options Options.
 * @param string $name Ooption name.
 * @param string $slave Class of slaves.
 */
function _appointments_html_chceckbox( $options, $name, $slave = '' ) {
	$value = isset( $options[ $name ] )? $options[ $name ]:false;
	if ( 'yes' === $value ) {
		$value = true;
	}
	$checked = checked( $value, true, false );
	printf(
		'<input type="checkbox" name="%s" value="%s" class="switch-button" data-on="%s" data-off="%s" data-slave="%s" %s />',
		esc_attr( $name ),
		esc_attr( 'yes' ),
		esc_attr__( 'Yes', 'appointments' ),
		esc_attr__( 'No', 'appointments' ),
		esc_attr( $slave ),
		$checked
	);
}


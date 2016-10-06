<?php

$strings = new stdClass();
$registered_shortcodes = array(
	'app_worker_montly_calendar',
	'app_worker_monthly_calendar',
	'app_schedule',
	'app_monthly_schedule',
	'app_pagination',
	'app_all_appointments',
	'app_my_appointments',
	'app_services',
	'app_service_providers',
	'app_login',
	'app_paypal',
	'app_confirmation',
);

$strings->label = esc_js( __( 'Appointments', 'pubman' ) );
$strings->shortcodes = new stdClass();
foreach ( $registered_shortcodes as $shortcode ) {
	$instance = App_Shortcodes::get_shortcode_instance( $shortcode );
	if ( $instance && $instance->name ) {
		$strings->shortcodes->$shortcode = new stdClass();
		$strings->shortcodes->$shortcode->defaults = $instance->_defaults;
		$strings->shortcodes->$shortcode->name = $instance->name;
		$strings->shortcodes->$shortcode->shortcode = $shortcode;
	}
}
$strings = wp_json_encode( $strings );

$strings = 'tinyMCE.addI18n({' . _WP_Editors::$mce_locale . ': { appointments_shortcodes: ' . $strings . '}});';
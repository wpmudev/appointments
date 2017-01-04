<?php

$strings = new stdClass();
$appointments = appointments();
$registered_shortcodes = array_keys($appointments->shortcodes );

$strings->label = esc_js( __( 'Appointments', 'appointments' ) );
$strings->shortcodes = new stdClass();
foreach ( $registered_shortcodes as $shortcode ) {
	$instance = App_Shortcodes::get_shortcode_instance( $shortcode );
	if ( $instance && $instance->name ) {
		$strings->shortcodes->$shortcode = new stdClass();
		$_defaults = $instance->get_defaults();
		$defaults = array();
		foreach ( $_defaults as $key => $default ) {
			if ( isset( $default['type'] ) ) {
				$defaults[ $key ] = $default;
			}
		}
		$strings->shortcodes->$shortcode->defaults = $defaults;
		$strings->shortcodes->$shortcode->name = $instance->name;
		$strings->shortcodes->$shortcode->shortcode = $shortcode;
	}
}
$strings = wp_json_encode( $strings );

$strings = 'tinyMCE.addI18n({' . _WP_Editors::$mce_locale . ': { appointments_shortcodes: ' . $strings . '}});';
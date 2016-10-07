<?php

$strings = new stdClass();
$appointments = appointments();
$registered_shortcodes = array_keys($appointments->shortcodes );

$strings->label = esc_js( __( 'Appointments', 'pubman' ) );
$strings->shortcodes = new stdClass();
foreach ( $registered_shortcodes as $shortcode ) {
	$instance = App_Shortcodes::get_shortcode_instance( $shortcode );
	if ( $instance && $instance->name ) {
		$strings->shortcodes->$shortcode = new stdClass();
		$strings->shortcodes->$shortcode->defaults = $instance->get_defaults();
		$strings->shortcodes->$shortcode->name = $instance->name;
		$strings->shortcodes->$shortcode->shortcode = $shortcode;
	}
}
$strings = wp_json_encode( $strings );

$strings = 'tinyMCE.addI18n({' . _WP_Editors::$mce_locale . ': { appointments_shortcodes: ' . $strings . '}});';
<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'appointments_load_integrations', 999 );
function appointments_load_integrations() {
	$integrations = array(
		'buddypress',
		'membership2',
		'marketpress'
	);
	foreach ( $integrations as $integration ) {
		include_once( appointments_plugin_dir() . 'includes/integration/class-app-' . $integration . '.php' );
	}
}

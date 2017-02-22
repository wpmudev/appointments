<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$integrations = array(
	'buddypress',
	'membership2',
	'marketpress'
);
foreach ( $integrations as $integration ) {
	include_once( appointments_plugin_dir() . 'includes/integration/class-app-' . $integration . '.php' );
}
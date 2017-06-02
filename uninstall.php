<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$options = get_option( 'appointments_options', array() );
$delete_options_on_uninstall = ! ( isset( $options["keep_options_on_uninstall"] ) && $options["keep_options_on_uninstall"] );

if( $delete_options_on_uninstall ){
    
    global $wpdb;

    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}app_working_hours" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}app_exceptions" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}app_services" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}app_workers" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}app_appointments" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}app_appointmentmeta" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}app_transactions" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}app_cache" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}app_appointmentmeta" );

    delete_option( 'app_db_version' );
    delete_option( 'app_activated_plugins' );
    delete_option( 'app_admin_notices' );
    delete_option( 'app_last_update' );
    delete_option( 'app_locations_data' );
    delete_option( 'appointments_data' );
    delete_option( 'appointments_options' );
    delete_option( 'appointments_salt' );
    delete_option( 'appointments_services_padding' );
    delete_option( 'appointments_workers_padding' );

    $names = $wpdb->get_col( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'app-service_location%'" );
    foreach ( $names as $name ) {
        delete_option( $name );
    }

    $names = $wpdb->get_col( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'app-worker_location%'" );
    foreach ( $names as $name ) {
        delete_option( $name );
    }

    $user_metas = array(
        'appointments_per_page',
        'app_address',
        'app_api_mode',
        'app_city',
        'app_dismissed_notices',
        'app_email',
        'app_gcal_description',
        'app_gcal_summary',
        'app_gcal_token',
        'app_key_file',
        'app_name',
        'app_phone',
        'app_selected_calendar',
        'app_service_account'
    );
    foreach ( $user_metas as $meta ) {
        $wpdb->delete( $wpdb->usermeta, array( 'meta_key' => $meta ), array( '%s' ) );
    }

    $user_metas = $wpdb->get_col( "SELECT meta_key FROM $wpdb->usermeta WHERE meta_key LIKE 'current-app_tutorial%'" );
    foreach ( $user_metas as $meta ) {
        $wpdb->delete( $wpdb->usermeta, array( 'meta_key' => $meta ), array( '%s' ) );
    }

    wp_unschedule_event( current_time( 'timestamp' ), 'appointments_gcal_sync' );
   
}
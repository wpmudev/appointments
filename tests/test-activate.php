<?php

/**
 * Class App_Activate_Test
 *
 * @group activate
 */
class App_Activate_Test extends App_UnitTestCase {

	function test_activate() {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'app_appointments',
			$wpdb->prefix . 'app_appointmentmeta',
			$wpdb->prefix . 'app_transactions',
			$wpdb->prefix . 'app_working_hours',
			$wpdb->prefix . 'app_exceptions',
			$wpdb->prefix . 'app_services',
			$wpdb->prefix . 'app_workers',
			$wpdb->prefix . 'app_cache'
		);

		foreach ( $tables as $table ) {
			$results = $wpdb->get_results( "DESCRIBE $table" );
			$this->assertNotEmpty( $results );
		}

	}
}


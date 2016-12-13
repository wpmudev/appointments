<?php

/**
 * Class App_Appointments_Test
 *
 * @group shortcodes
 */
class App_Shortcodes_Test extends App_UnitTestCase {

	function test_registered_shortcodes() {
		global $shortcode_tags;

		$app_shortcodes = array(
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

		foreach ( $app_shortcodes as $shortcode ) {
			$this->assertArrayHasKey( $shortcode, $shortcode_tags, sprintf( 'Shortcode %s is not registered', $shortcode ) );
		}
	}
}

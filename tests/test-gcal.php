<?php

/**
 * Class App_Activate_Test
 *
 * @group gcal
 */
class App_GCal_Test extends App_UnitTestCase {

	function test_is_connected() {
		$appointments = appointments();
		$this->_set_gcal_options();

		$api = $appointments->get_gcal_api();
		$this->assertTrue( $api->is_connected() );
	}

	function _set_gcal_options() {
		add_filter( 'appointments_gcal_access_token', array( $this, '_get_api_token' ) );
		$options = appointments_get_options();
		$options['gcal_access_code'] = 'access-code';
		$options['gcal_client_id'] = 'client-id';
		$options['gcal_client_secret'] = 'client-secret';
		appointments_update_options( $options );
	}

	function _get_api_token() {
		$token = new stdClass();
		$token->access_token = 'access-token';
		return json_encode( $token );
	}

}


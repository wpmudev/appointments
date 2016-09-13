<?php

/**
 * Class App_Appointments_Test
 *
 * @group upgrades
 */
class App_Upgrades_Test extends App_UnitTestCase {

	function test_upgrade_1_7() {
		update_option( 'app_db_version', '1.6.5' );
		delete_option( 'app_admin_notices' );
		appointments()->maybe_upgrade();
		$this->assertArrayHasKey( '1-7-gcal', get_option( 'app_admin_notices' ) );
		$this->assertEquals( get_option( 'app_db_version' ), appointments()->version );
	}

	function test_upgrade_1_7_1() {
		update_option( 'app_db_version', '1.7' );

		$worker_id = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id = appointments_insert_service( $service_args );
		$service = appointments_get_service( $service_id );

		$worker_args = array(
			'ID' => $worker_id,
			'services_provided' => array( $service_id )
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'user' => $user_id,
			'service' => $service_id,
			'worker' => $worker_id,
		);
		$app_id_1 = appointments_insert_appointment( $args );
		$app_id_2 = appointments_insert_appointment( $args );
		$app_id_3 = appointments_insert_appointment( $args );

		// Now insert Extra fields
		$options = appointments_get_options();
		$options['additional_fields'] = array(
			array(
				'label' => 'First field',
				'type' => 'checkbox',
				'required' => false
			),
			array(
				'label' => 'Second field',
				'type' => 'text',
				'required' => true
			)
		);
		appointments_update_options( $options );

		$data = array(
			$app_id_1 => array(
				$this->_to_clean_name( 'First field' ) => 1,
				$this->_to_clean_name( 'Second field' ) => 'a text 1'
			),
			$app_id_2 => array(
				$this->_to_clean_name( 'First field' ) => '',
				$this->_to_clean_name( 'Second field' ) => 'a text 2'
			)
		);

		update_option( 'appointments_data', $data );

		appointments()->maybe_upgrade();

		$app_1_fields = appointments_get_appointment_meta( $app_id_1, 'additional_fields' );
		$app_2_fields = appointments_get_appointment_meta( $app_id_2, 'additional_fields' );
		$app_3_fields = appointments_get_appointment_meta( $app_id_3, 'additional_fields' );
		$this->assertEquals( $app_1_fields, $data[ $app_id_1 ] );
		$this->assertEquals( $app_2_fields, $data[ $app_id_2 ] );
		$this->assertEquals( $app_3_fields, '' );

		$this->assertEquals( get_option( 'app_db_version' ), appointments()->version );
	}

	function test_upgrade_1_7_2_beta1() {
		update_option( 'app_db_version', '1.7.1' );
		appointments()->maybe_upgrade();
		$this->assertEquals( get_option( 'app_db_version' ), appointments()->version );
	}

	/**
	 * It should fix a bug that was inserting working hours in the wrong format
	 */
	function test_upgrade_1_9_4() {
		update_option( 'app_db_version', '1.9.4' );
		appointments()->maybe_upgrade();
		$this->assertEquals( get_option( 'app_db_version' ), appointments()->version );
	}

	/**
	 * This is a function on app-users-additional_fields.php
	 *
	 * @param $label
	 *
	 * @return mixed|string
	 */
	private function _to_clean_name ($label) {
		$clean = preg_replace('/[^-_a-z0-9]/', '', strtolower($label));
		if (empty($clean)) $clean = substr(md5($label), 0, 8);
		return $clean;
	}
}

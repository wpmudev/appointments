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
	 *
	 * @group upgrade-1.9.4
	 */
	function test_upgrade_1_9_4_format1() {
		$open = $this->_get_working_hours('g:i A', 'open');
		$closed = $this->_get_working_hours('g:i A', 'closed');

		appointments_update_worker_working_hours( 0, $open, 'open', 0 );
		appointments_update_worker_working_hours( 0, $closed, 'closed', 0 );

		$worker_id = $this->factory->worker->create_object( array( 'user_email' => 'test@test.com' ) );
		appointments_update_worker_working_hours( 0, $open, 'open', $worker_id );
		appointments_update_worker_working_hours( 0, $closed, 'closed', $worker_id );

		update_option( 'app_db_version', '1.9.3' );
		appointments()->maybe_upgrade();
		$this->assertEquals( get_option( 'app_db_version' ), appointments()->version );

		$this->_test_upgrade_1_9_4_single_time_format( 0 );
		$this->_test_upgrade_1_9_4_single_time_format( $worker_id );

	}

	/**
	 * It should fix a bug that was inserting working hours in the wrong format
	 *
	 * @group upgrade-1.9.4
	 */
	function test_upgrade_1_9_4_format2() {
		$open = $this->_get_working_hours('g:i a', 'open');
		$closed = $this->_get_working_hours('g:i a', 'closed');

		appointments_update_worker_working_hours( 0, $open, 'open', 0 );
		appointments_update_worker_working_hours( 0, $closed, 'closed', 0 );

		$worker_id = $this->factory->worker->create_object( array( 'user_email' => 'test@test.com' ) );
		appointments_update_worker_working_hours( 0, $open, 'open', $worker_id );
		appointments_update_worker_working_hours( 0, $closed, 'closed', $worker_id );

		update_option( 'app_db_version', '1.9.3' );
		appointments()->maybe_upgrade();
		$this->assertEquals( get_option( 'app_db_version' ), appointments()->version );

		$this->_test_upgrade_1_9_4_single_time_format( 0 );
		$this->_test_upgrade_1_9_4_single_time_format( $worker_id );

	}

	/**
	 * It should fix a bug that was inserting working hours in the wrong format
	 *
	 * @group upgrade-1.9.4
	 */
	function test_upgrade_1_9_4_format3() {
		$open = $this->_get_working_hours('H:i', 'open');
		$closed = $this->_get_working_hours('H:i', 'closed');

		appointments_update_worker_working_hours( 0, $open, 'open', 0 );
		appointments_update_worker_working_hours( 0, $closed, 'closed', 0 );

		$worker_id = $this->factory->worker->create_object( array( 'user_email' => 'test@test.com' ) );
		appointments_update_worker_working_hours( 0, $open, 'open', $worker_id );
		appointments_update_worker_working_hours( 0, $closed, 'closed', $worker_id );

		update_option( 'app_db_version', '1.9.3' );
		appointments()->maybe_upgrade();
		$this->assertEquals( get_option( 'app_db_version' ), appointments()->version );

		$this->_test_upgrade_1_9_4_single_time_format( 0 );
		$this->_test_upgrade_1_9_4_single_time_format( $worker_id );

	}

	/**
	 * It should fix a bug that was inserting working hours in the wrong format
	 *
	 * @group upgrade-1.9.4
	 */
	function test_upgrade_1_9_4_format4() {
		$open = $this->_get_working_hours('G:i', 'open');
		$closed = $this->_get_working_hours('G:i', 'closed');

		appointments_update_worker_working_hours( 0, $open, 'open', 0 );
		appointments_update_worker_working_hours( 0, $closed, 'closed', 0 );

		$worker_id = $this->factory->worker->create_object( array( 'user_email' => 'test@test.com' ) );
		appointments_update_worker_working_hours( 0, $open, 'open', $worker_id );
		appointments_update_worker_working_hours( 0, $closed, 'closed', $worker_id );

		update_option( 'app_db_version', '1.9.3' );
		appointments()->maybe_upgrade();
		$this->assertEquals( get_option( 'app_db_version' ), appointments()->version );

		$this->_test_upgrade_1_9_4_single_time_format( 0 );
		$this->_test_upgrade_1_9_4_single_time_format( $worker_id );
	}

	/**
	 * It should fix a bug that was inserting working hours in the wrong format
	 *
	 * @group upgrade-1.9.4
	 */
	function test_upgrade_1_9_4_format5() {
		$open = $this->_get_working_hours('G:i:s', 'open');
		$closed = $this->_get_working_hours('G:i:s', 'closed');

		appointments_update_worker_working_hours( 0, $open, 'open', 0 );
		appointments_update_worker_working_hours( 0, $closed, 'closed', 0 );

		$worker_id = $this->factory->worker->create_object( array( 'user_email' => 'test@test.com' ) );
		appointments_update_worker_working_hours( 0, $open, 'open', $worker_id );
		appointments_update_worker_working_hours( 0, $closed, 'closed', $worker_id );

		update_option( 'app_db_version', '1.9.3' );
		appointments()->maybe_upgrade();
		$this->assertEquals( get_option( 'app_db_version' ), appointments()->version );

		$this->_test_upgrade_1_9_4_single_time_format( 0 );
		$this->_test_upgrade_1_9_4_single_time_format( $worker_id );
	}

	/**
	 * It should fix a bug that was inserting working hours in the wrong format
	 *
	 * @group upgrade-1.9.4
	 */
	function test_upgrade_1_9_4_format6() {
		$open = $this->_get_working_hours('g:i:s a', 'open');
		$closed = $this->_get_working_hours('g:i:s a', 'closed');

		appointments_update_worker_working_hours( 0, $open, 'open', 0 );
		appointments_update_worker_working_hours( 0, $closed, 'closed', 0 );

		$worker_id = $this->factory->worker->create_object( array( 'user_email' => 'test@test.com' ) );
		appointments_update_worker_working_hours( 0, $open, 'open', $worker_id );
		appointments_update_worker_working_hours( 0, $closed, 'closed', $worker_id );

		update_option( 'app_db_version', '1.9.3' );
		appointments()->maybe_upgrade();
		$this->assertEquals( get_option( 'app_db_version' ), appointments()->version );

		$this->_test_upgrade_1_9_4_single_time_format( 0 );
		$this->_test_upgrade_1_9_4_single_time_format( $worker_id );
	}

	/**
	 * It should fix a bug that was inserting working hours in the wrong format
	 *
	 * @group upgrade-1.9.4
	 */
	function test_upgrade_1_9_4_format7() {
		$open = $this->_get_working_hours('G:i T', 'open');
		$closed = $this->_get_working_hours('G:i T', 'closed');

		appointments_update_worker_working_hours( 0, $open, 'open', 0 );
		appointments_update_worker_working_hours( 0, $closed, 'closed', 0 );

		$worker_id = $this->factory->worker->create_object( array( 'user_email' => 'test@test.com' ) );
		appointments_update_worker_working_hours( 0, $open, 'open', $worker_id );
		appointments_update_worker_working_hours( 0, $closed, 'closed', $worker_id );

		update_option( 'app_db_version', '1.9.3' );
		appointments()->maybe_upgrade();
		$this->assertEquals( get_option( 'app_db_version' ), appointments()->version );

		$this->_test_upgrade_1_9_4_single_time_format( 0 );
		$this->_test_upgrade_1_9_4_single_time_format( $worker_id );
	}

	private function _get_working_hours( $format, $status ) {
		$_10_am = date( $format, strtotime( '10:00' ) );
		$_10_pm = date( $format, strtotime( '22:00' ) );
		$_7_am  = date( $format, strtotime( '07:00' ) );
		$_1_pm  = date( $format, strtotime( '13:00' ) );
		$_9_am  = date( $format, strtotime( '09:00' ) );
		$_12_am = date( $format, strtotime( '00:00' ) );
		$_12_pm = date( $format, strtotime( '12:00' ) );
		$_3_pm  = date( $format, strtotime( '15:00' ) );
		$_6_pm  = date( $format, strtotime( '18:00' ) );
		$_9_pm  = date( $format, strtotime( '21:00' ) );
		$_11_pm = date( $format, strtotime( '23:00' ) );
		$_8_pm  = date( $format, strtotime( '20:00' ) );

		if ( 'open' == $status ) {
			return array(
				'Sunday'    => array( 'active' => 'no', 'start' => $_10_am, 'end' => $_11_pm, ),
				'Monday'    => array( 'active' => 'no', 'start' => $_10_pm, 'end' => $_11_pm, ),
				'Tuesday'   => array( 'active' => 'no', 'start' => $_7_am, 'end' => $_12_am, ),
				'Wednesday' => array( 'active' => 'yes', 'start' => $_1_pm, 'end' => $_10_pm, ),
				'Thursday'  => array( 'active' => 'yes', 'start' => $_12_am, 'end' => $_8_pm, ),
				'Friday'    => array( 'active' => 'yes', 'start' => $_9_am, 'end' => $_9_pm, ),
				'Saturday'  => array( 'active' => 'yes', 'start' => $_12_am, 'end' => $_8_pm, )
			);
		}
		else {
			return array(
				'Sunday'    => array( 'active' => 'no', 'start' => $_12_am, 'end' => $_12_am, ),
				'Monday'    =>array (
					'active' => array( 0 => 'yes', 1 => 'yes', ),
					'start' => array( 0 => $_12_pm, 1 => $_3_pm, ),
					'end' => array( 0 => $_6_pm, 1 => $_9_pm, ),
				),
				'Tuesday'   => array( 'active' => 'no', 'start' => $_12_pm, 'end' => $_1_pm, ),
				'Wednesday' => array( 'active' => 'no', 'start' => $_12_pm, 'end' => $_1_pm, ),
				'Thursday'  => array( 'active' => 'no', 'start' => $_12_pm, 'end' => $_1_pm, ),
				'Friday'    => array( 'active' => 'no', 'start' => $_12_pm, 'end' => $_1_pm, ),
				'Saturday'  => array( 'active' => 'no', 'start' => $_12_pm, 'end' => $_1_pm, ),
			);
		}
	}

	private function _test_upgrade_1_9_4_single_time_format( $worker_id ) {
		// Test open status
		$new_hours = appointments_get_worker_working_hours( 'open', $worker_id, 0 );
		$this->assertEquals( array( 'active' => 'no', 'start' => '10:00', 'end' => '23:00' , 'weekday_number' => 7 ), $new_hours->hours['Sunday'] );
		$this->assertEquals( array( 'active' => 'no', 'start' => '22:00', 'end' => '23:00' , 'weekday_number' => 1 ), $new_hours->hours['Monday'] );
		$this->assertEquals( array( 'active' => 'no', 'start' => '07:00', 'end' => '00:00' , 'weekday_number' => 2 ), $new_hours->hours['Tuesday'] );
		$this->assertEquals( array( 'active' => 'yes', 'start' => '13:00', 'end' => '22:00', 'weekday_number' => 3  ), $new_hours->hours['Wednesday'] );
		$this->assertEquals( array( 'active' => 'yes', 'start' => '00:00', 'end' => '20:00', 'weekday_number' => 4 ), $new_hours->hours['Thursday'] );
		$this->assertEquals( array( 'active' => 'yes', 'start' => '09:00', 'end' => '21:00', 'weekday_number' => 5  ), $new_hours->hours['Friday'] );
		$this->assertEquals( array( 'active' => 'yes', 'start' => '00:00', 'end' => '20:00', 'weekday_number' => 6  ), $new_hours->hours['Saturday'] );

		// Test closed status
		$new_hours = appointments_get_worker_working_hours( 'closed', $worker_id, 0 );
		$this->assertEquals( array( 'active' => 'no', 'start' => '00:00', 'end' => '00:00', 'weekday_number' => 7 ), $new_hours->hours['Sunday'] );
		$this->assertEquals( array( 'active' => 'no', 'start' => '12:00', 'end' => '13:00', 'weekday_number' => 2 ), $new_hours->hours['Tuesday'] );
		$this->assertEquals( array( 'active' => 'no', 'start' => '12:00', 'end' => '13:00', 'weekday_number' => 3 ), $new_hours->hours['Wednesday'] );
		$this->assertEquals( array( 'active' => 'no', 'start' => '12:00', 'end' => '13:00', 'weekday_number' => 4 ), $new_hours->hours['Thursday'] );
		$this->assertEquals( array( 'active' => 'no', 'start' => '12:00', 'end' => '13:00', 'weekday_number' => 5 ), $new_hours->hours['Friday'] );
		$this->assertEquals( array( 'active' => 'no', 'start' => '12:00', 'end' => '13:00', 'weekday_number' => 6 ), $new_hours->hours['Saturday'] );

		// Monday is special
		$this->assertEquals( array( 0 => 'yes', 1 => 'yes', ), $new_hours->hours['Monday']['active'] );
		$this->assertEquals( array( 0 => '12:00', 1 => '15:00', ), $new_hours->hours['Monday']['start'] );
		$this->assertEquals( array( 0 => '18:00', 1 => '21:00', ), $new_hours->hours['Monday']['end'] );
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

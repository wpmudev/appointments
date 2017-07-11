<?php

/**
 * Class App_Appointments_Test
 *
 * Tests for appointments_is_holiday() function
 *
 * @group timetables
 * @group helpers
 * @group is-holiday
 */
class App_Is_Holiday_Test extends App_UnitTestCase {

	function test_appointments_is_holiday() {
		$options = appointments_get_options();
		$options['min_time'] = 60;
		appointments_update_options( $options );

		$args = $this->factory->service->generate_args();
		$args['duration'] = 60;

		// Create a service
		$service_id = $this->factory->service->create_object( $args );

		// Delete default service
		foreach ( appointments_get_services() as $service ) {
			if ( $service->ID != $service_id ) {
				appointments_delete_service( $service->ID );
			}
		}

		$args = $this->factory->worker->generate_args();
		$args['services_provided'] = array( $service_id );
		$worker_id_1 = $this->factory->worker->create_object( $args );

		$open_hours = $this->get_open_wh();
		$closed_hours = $this->get_closed_wh();
		appointments_update_worker_working_hours( 0, $open_hours, 'open' );
		appointments_update_worker_working_hours( 0, $closed_hours, 'closed' );

		// Worker hours
		$open_hours['Monday'] = array(
			'active'         => 'yes',
			'start'          => '19:00',
			'end'            => '20:00',
			'weekday_number' => 1
		);
		$open_hours['Tuesday'] = array(
			'active'         => 'yes',
			'start'          => '17:00',
			'end'            => '22:00',
			'weekday_number' => 2
		);

		appointments_update_worker_working_hours( $worker_id_1, $open_hours, 'open' );
		appointments_update_worker_working_hours( $worker_id_1, $closed_hours, 'closed' );

		$next_monday =  strtotime( 'Next Monday' );
		$next_monday_at_19_00 = strtotime( date( 'Y-m-d 19:00:00', $next_monday ) );
		$next_monday_at_19_30 = strtotime( date( 'Y-m-d 19:30:00', $next_monday ) );

		$next_2_monday =  strtotime( 'Next Monday' ) + ( 7 * 24 * 3600 );
		$next_2_monday_at_19_00 = strtotime( date( 'Y-m-d 19:00:00', $next_2_monday ) );
		$next_2_monday_at_19_30 = strtotime( date( 'Y-m-d 19:30:00', $next_2_monday ) );

		$next_3_monday =  strtotime( 'Next Monday' ) + ( 14 * 24 * 3600 );
		$next_3_monday_at_19_00 = strtotime( date( 'Y-m-d 19:00:00', $next_3_monday ) );
		$next_3_monday_at_19_30 = strtotime( date( 'Y-m-d 19:30:00', $next_3_monday ) );
		
		// Holiday on next monday for the worker
		appointments_update_worker_exceptions( $worker_id_1, 'closed', date( 'Y-m-d', $next_monday ) );

		// Store is on holiday in 3 mondays
		appointments_update_worker_exceptions( 0, 'closed', date( 'Y-m-d', $next_3_monday ) );

		// Next Monday
		$this->assertTrue( appointments_is_worker_holiday(  $worker_id_1, $next_monday_at_19_00, $next_monday_at_19_30 ) );
		$this->assertFalse( appointments_is_worker_holiday(  0, $next_monday_at_19_00, $next_monday_at_19_30 ) );

		// No holidays
		$this->assertFalse( appointments_is_worker_holiday(  $worker_id_1, $next_2_monday_at_19_00, $next_2_monday_at_19_30 ) );
		$this->assertFalse( appointments_is_worker_holiday(  0, $next_2_monday_at_19_00, $next_2_monday_at_19_30 ) );

		// Holidays for the store
		$this->assertFalse( appointments_is_worker_holiday(  $worker_id_1, $next_3_monday_at_19_00, $next_3_monday_at_19_30 ) );
		$this->assertTrue( appointments_is_worker_holiday(  0, $next_3_monday_at_19_00, $next_3_monday_at_19_30 ) );
	}

	function get_open_wh() {
		return array(
			'Sunday'    =>
				array(
					'active' => 'no',
					'start'  => '07:00',
					'end'    => '20:00',
					'weekday_number' => 7
				),
			'Monday'    =>
				array(
					'active' => 'yes',
					'start'  => '10:30',
					'end'    => '20:00',
					'weekday_number' => 1
				),
			'Tuesday'   =>
				array(
					'active' => 'yes',
					'start'  => '12:30',
					'end'    => '15:00',
					'weekday_number' => 2
				),
			'Wednesday' =>
				array(
					'active' => 'yes',
					'start'  => '10:30',
					'end'    => '20:00',
					'weekday_number' => 3
				),
			'Thursday'  =>
				array(
					'active' => 'yes',
					'start'  => '10:30',
					'end'    => '20:00',
					'weekday_number' => 4
				),
			'Friday'    =>
				array(
					'active' => 'yes',
					'start'  => '10:30',
					'end'    => '20:00',
					'weekday_number' => 5
				),
			'Saturday'  =>
				array(
					'active' => 'yes',
					'start'  => '10:30',
					'end'    => '20:00',
					'weekday_number' => 6
				)
		);
	}

	function get_closed_wh() {

		return array(
			'Sunday'    =>
				array(
					'active' => 'no',
					'start'  => '11:00',
					'end'    => '13:00',
					'weekday_number' => 7
				),
			'Monday'    =>
				array(
					'active' => 'no',
					'start'  => '12:00',
					'end'    => '13:00',
					'weekday_number' => 1
				),
			'Tuesday'   =>
				array(
					'active' => 'no',
					'start'  => '12:00',
					'end'    => '13:00',
					'weekday_number' => 2
				),
			'Wednesday' =>
				array(
					'active' => 'no',
					'start'  => '12:00',
					'end'    => '13:00',
					'weekday_number' => 3
				),
			'Thursday'  =>
				array(
					'active' => 'yes',
					'start'  => '12:00',
					'end'    => '13:00',
					'weekday_number' => 4
				),
			'Friday'    =>
				array(
					'active' => 'no',
					'start'  => '12:00',
					'end'    => '13:00',
					'weekday_number' => 5
				),
			'Saturday'  =>
				array(
					'active' => 'no',
					'start'  => '12:00',
					'end'    => '13:00',
					'weekday_number' => 6
				)
		);
	}

}
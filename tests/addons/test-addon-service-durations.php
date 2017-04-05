<?php

/**
 * Class App_Addons_Service_Durations_Test
 *
 * @group addons
 * @group addons_service_durations
 */
class App_Addons_Service_Durations_Test extends App_UnitTestCase {
	function setUp() {
		parent::setUp();
		$this->load_addon( 'app-schedule-durations' );
	}

	private function __init_addon() {
		App_Schedule_Durations::serve();
	}

	function test_init() {
		$this->assertTrue( class_exists( 'App_Addons_Service_Durations_Test' ) );
		$this->__init_addon();
	}

	function test_options() {
		$this->__init_addon();
		$options = appointments_get_options();
		$options['duration_calculus'] = 'legacy';
		$options['boundaries_calculus'] = 'legacy';
		$options['breaks_calculus'] = 'legacy';
		appointments_update_options( $options );

		$this->assertTrue( appointments_use_legacy_duration_calculus() );
		$this->assertFalse( appointments_use_legacy_break_times_padding_calculus() );
		$this->assertTrue( appointments_use_legacy_boundaries_calculus() );
	}

	/**
	 * https://app.asana.com/0/211855939023775/304885120701127
	 *
	 * @group 211855939023775
	 */
	function test_duration_calculus() {
		$this->__init_addon();

		$options = appointments_get_options();
		$options['min_time'] = 30;
		$options['duration_calculus'] = 'service';
		appointments_update_options( $options );
		$appointments = appointments();

		$args = $this->factory->service->generate_args();
		$args['duration'] = 120;
		$service_id_1 = $this->factory->service->create_object( $args );

		// Delete default service
		foreach ( appointments_get_services() as $service ) {
			if ( $service->ID != $service_id_1 ) {
				appointments_delete_service( $service->ID );
			}
		}

		// Create other services with other durations
		$args['duration'] = 90;
		$service_id_2 = $this->factory->service->create_object( $args );
		$args['duration'] = 60;
		$service_id_3 = $this->factory->service->create_object( $args );

		$args = $this->factory->worker->generate_args();
		$args['services_provided'] = array( $service_id_1, $service_id_2, $service_id_3 );
		$worker_id_1 = $this->factory->worker->create_object( $args );

		$open_hours = $this->get_open_wh();
		$closed_hours = $this->get_closed_wh();
		appointments_update_worker_working_hours( 0, $open_hours, 'open' );
		appointments_update_worker_working_hours( 0, $closed_hours, 'closed' );

		$next_monday = strtotime( date( 'Y-m-d 00:00:00', strtotime( 'next Monday', current_time( 'timestamp' ) ) ) );

		// Test service 1
		$_GET['service'] = $service_id_1;
		$_REQUEST["app_service_id"] = $service_id_1;

		// We expect the slots for the first service (120mins duration)
		$expected = array(
			// Start - End
			array( strtotime( date( 'Y-m-d 10:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 12:30:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 12:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 14:30:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 14:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 16:30:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 16:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 18:30:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 18:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 20:30:00', $next_monday ) ) ),
		);
		$next_monday_slots = $appointments->_get_timetable_slots( $next_monday,1 );
		$starts = wp_list_pluck( $next_monday_slots, 'ccs' );
		$ends = wp_list_pluck( $next_monday_slots, 'cce' );

		$this->assertCount( count( $starts ), $expected );
		foreach ( $expected as $key => $expected_values ) {
			$this->assertEquals( $expected_values[0], $starts[ $key ] );
			$this->assertEquals( $expected_values[1], $ends[ $key ] );
		}

		// Test service 2
		$_GET['service'] = $service_id_2;
		$_REQUEST["app_service_id"] = $service_id_2;

		// We expect the slots for the service (90mins duration)
		$expected = array(
			// Start - End
			array( strtotime( date( 'Y-m-d 10:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 12:00:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 12:00:00', $next_monday ) ), strtotime( date( 'Y-m-d 13:30:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 13:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 15:00:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 15:00:00', $next_monday ) ), strtotime( date( 'Y-m-d 16:30:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 16:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 18:00:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 18:00:00', $next_monday ) ), strtotime( date( 'Y-m-d 19:30:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 19:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 21:00:00', $next_monday ) ) ),
		);
		$next_monday_slots = $appointments->_get_timetable_slots( $next_monday,1 );
		$starts = wp_list_pluck( $next_monday_slots, 'ccs' );
		$ends = wp_list_pluck( $next_monday_slots, 'cce' );

		$this->assertCount( count( $starts ), $expected );
		foreach ( $expected as $key => $expected_values ) {
			$this->assertEquals( $expected_values[0], $starts[ $key ] );
			$this->assertEquals( $expected_values[1], $ends[ $key ] );
		}

		// Test service 3
		$_GET['service'] = $service_id_3;
		$_REQUEST["app_service_id"] = $service_id_3;

		// We expect the slots for the service (60mins duration)
		$expected = array(
			// Start - End
			array( strtotime( date( 'Y-m-d 10:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 11:30:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 11:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 12:30:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 12:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 13:30:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 13:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 14:30:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 14:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 15:30:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 15:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 16:30:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 16:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 17:30:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 17:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 18:30:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 18:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 19:30:00', $next_monday ) ) ),
			array( strtotime( date( 'Y-m-d 19:30:00', $next_monday ) ), strtotime( date( 'Y-m-d 20:30:00', $next_monday ) ) ),
		);
		$next_monday_slots = $appointments->_get_timetable_slots( $next_monday,1 );
		$starts = wp_list_pluck( $next_monday_slots, 'ccs' );
		$ends = wp_list_pluck( $next_monday_slots, 'cce' );

		$this->assertCount( count( $starts ), $expected );
		foreach ( $expected as $key => $expected_values ) {
			$this->assertEquals( $expected_values[0], $starts[ $key ] );
			$this->assertEquals( $expected_values[1], $ends[ $key ] );
		}

		$_GET['service'] = null;
		$_REQUEST["app_service_id"] = null;
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
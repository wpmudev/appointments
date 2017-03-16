<?php

/**
 * Class App_Appointments_Test
 *
 * @group timetables
 */
class App_Timetables_Test extends App_UnitTestCase {


	function test_get_available_workers_for_interval() {
		$options = appointments_get_options();
		$options['min_time'] = 30;
		$options['additional_min_time'] = '';
		$options['admin_min_time'] = '';
		$options['allow_overwork'] = 'no';
		appointments_update_options( $options );

		update_option( 'date_format', 'Y-m-d' );
		update_option( 'time_format', 'H:i' );
		appointments()->time_format = appointments_get_date_format( 'time' );
		appointments()->date_format = appointments_get_date_format( 'date' );
		appointments()->datetime_format = appointments_get_date_format( 'full' );

		$service_id = $this->factory->service->create_object( array( 'duration' => 60, 'name' => 'A service' ) );
		$worker_id_1 = $this->factory->worker->create_object( array( 'services_provided' => array( $service_id ) ) );

		$this->_generate_working_hours( 0 );
		$this->_generate_working_hours( $worker_id_1 );

		$next_sunday = strtotime( 'next Sunday', current_time( 'timestamp' ) );
		$next_monday = strtotime( 'next Monday', current_time( 'timestamp' ) );
		$next_tuesday = strtotime( 'next Tuesday', current_time( 'timestamp' ) );


		$start = strtotime( date( 'Y-m-d 10:00:00', $next_sunday ) );
		$end = strtotime( date( 'Y-m-d 10:30:00', $next_sunday ) );
		$result = appointments_get_available_workers_for_interval( $start, $end, $service_id );
		$this->assertEquals( 0, $result );

		$start = strtotime( date( 'Y-m-d 10:00:00', $next_monday ) );
		$end = strtotime( date( 'Y-m-d 10:30:00', $next_monday ) );
		$result = appointments_get_available_workers_for_interval( $start, $end, $service_id );
		$this->assertEquals( 1, $result );

		$start = strtotime( date( 'Y-m-d 20:00:00', $next_monday ) );
		$end = strtotime( date( 'Y-m-d 20:30:00', $next_monday ) );
		$result = appointments_get_available_workers_for_interval( $start, $end, $service_id );
		$this->assertEquals( 0, $result );

		$start = strtotime( date( 'Y-m-d 12:00:00', $next_tuesday ) );
		$end = strtotime( date( 'Y-m-d 12:30:00', $next_tuesday ) );
		$result = appointments_get_available_workers_for_interval( $start, $end, $service_id );
		$this->assertEquals( 1, $result );


		$worker_id_2 = $this->factory->worker->create_object( array( 'services_provided' => array( $service_id ) ) );
		$worker_wh = array(
			'open' => array(
				'Monday'    => array(
					'active' => 'no',
					'start'  => '08:00',
					'end'    => '18:00'
				),
			)
		);
		$this->_generate_working_hours( $worker_id_2, $worker_wh );

		$start = strtotime( date( 'Y-m-d 10:00:00', $next_sunday ) );
		$end = strtotime( date( 'Y-m-d 10:30:00', $next_sunday ) );
		$result = appointments_get_available_workers_for_interval( $start, $end, $service_id );
		$this->assertEquals( 0, $result );

		$start = strtotime( date( 'Y-m-d 10:00:00', $next_monday ) );
		$end = strtotime( date( 'Y-m-d 10:30:00', $next_monday ) );
		$result = appointments_get_available_workers_for_interval( $start, $end, $service_id );
		$this->assertEquals( 1, $result );

		$start = strtotime( date( 'Y-m-d 20:00:00', $next_monday ) );
		$end = strtotime( date( 'Y-m-d 20:30:00', $next_monday ) );
		$result = appointments_get_available_workers_for_interval( $start, $end, $service_id );
		$this->assertEquals( 0, $result );

		$start = strtotime( date( 'Y-m-d 12:00:00', $next_tuesday ) );
		$end = strtotime( date( 'Y-m-d 12:30:00', $next_tuesday ) );
		$result = appointments_get_available_workers_for_interval( $start, $end, $service_id );
		$this->assertEquals( 2, $result );
	}

	/**
	 * @group temp
	 */
	function test_is_interval_break() {
		$options = appointments_get_options();
		$options['min_time'] = 30;
		$options['additional_min_time'] = '';
		$options['admin_min_time'] = '';
		$options['allow_overwork'] = 'no';
		appointments_update_options( $options );

		update_option( 'date_format', 'Y-m-d' );
		update_option( 'time_format', 'H:i' );
		appointments()->time_format = appointments_get_date_format( 'time' );
		appointments()->date_format = appointments_get_date_format( 'date' );
		appointments()->datetime_format = appointments_get_date_format( 'full' );

		$service_id = $this->factory->service->create_object( array( 'duration' => 60, 'name' => 'A service' ) );
		$worker_id = $this->factory->worker->create_object( array( 'services_provided' => array( $service_id ) ) );

		$this->_generate_working_hours( 0 );

		$worker_wh = array(
			'closed' => array(
				'Monday' =>
					array(
						'active' => 'yes',
						'start'  => '12:00',
						'end'    => '15:00',
					)
			)
		);
		$this->_generate_working_hours( $worker_id, $worker_wh );

		$next_monday = strtotime( 'next Monday', current_time( 'timestamp' ) );
		$next_sunday = strtotime( 'next Sunday', current_time( 'timestamp' ) );

		$start = strtotime( date( 'Y-m-d 10:00:00', $next_sunday ) );
		$end = strtotime( date( 'Y-m-d 10:30:00', $next_sunday ) );
		$result = appointments_is_interval_break( $start, $end, $worker_id );
		$this->assertFalse( $result );

		$start = strtotime( date( 'Y-m-d 10:00:00', $next_monday ) );
		$end = strtotime( date( 'Y-m-d 10:30:00', $next_monday ) );
		$result = appointments_is_interval_break( $start, $end, $worker_id );
		$this->assertFalse( $result );

		$start = strtotime( date( 'Y-m-d 20:00:00', $next_monday ) );
		$end = strtotime( date( 'Y-m-d 20:30:00', $next_monday ) );
		$result = appointments_is_interval_break( $start, $end, $worker_id );
		$this->assertFalse( $result );

		$start = strtotime( date( 'Y-m-d 14:00:00', $next_monday ) );
		$end = strtotime( date( 'Y-m-d 14:30:00', $next_monday ) );
		$result = appointments_is_interval_break( $start, $end, $worker_id );
		$this->assertTrue( $result );
	}


	function _generate_working_hours( $worker_id = 0, $custom = array() ) {
		$defaults = array(
			'open' => array(),
			'closed' => array()
		);
		$custom = wp_parse_args( $custom, $defaults );
		$this->_generate_open_working_hours( $worker_id, $custom['open'] );
		$this->_generate_closed_working_hours( $worker_id, $custom['closed'] );
	}

	function _generate_open_working_hours( $worker_id = 0, $custom = array() ) {
		// Default working hours: 08:00 - 18:00 Mo-Fri and 08:00 - 13:00 Sat.
		// Do not work on Sun
		$defaults = array(
			'Sunday'    => array(
				'active' => 'no',
				'start'  => '08:00',
				'end'    => '13:00'
			),
			'Monday'    => array(
				'active' => 'yes',
				'start'  => '08:00',
				'end'    => '18:00'
			),
			'Tuesday'   => array(
				'active' => 'yes',
				'start'  => '08:00',
				'end'    => '18:00'
			),
			'Wednesday' => array(
				'active' => 'yes',
				'start'  => '08:00',
				'end'    => '18:00'
			),
			'Thursday'  => array(
				'active' => 'yes',
				'start'  => '08:00',
				'end'    => '18:00'
			),
			'Friday'    => array(
				'active' => 'yes',
				'start'  => '08:00',
				'end'    => '18:00'
			),
			'Saturday'  => array(
				'active' => 'yes',
				'start'  => '08:00',
				'end'    => '13:00'
			),
		);

		$working_hours = wp_parse_args( $custom, $defaults );
		appointments_update_worker_working_hours( $worker_id, $working_hours, 'open' );
	}

	function _generate_closed_working_hours( $worker_id = 0, $custom = array() ) {
		// There are no closing hours by default
		$defaults = array(
			'Sunday'    =>
				array(
					'active' => 'no',
					'start'  => '12:00',
					'end'    => '13:00',
				),
			'Monday'    =>
				array(
					'active' => 'no',
					'start'  => '12:00',
					'end'    => '13:00',
				),
			'Tuesday'   =>
				array(
					'active' => 'no',
					'start'  => '12:00',
					'end'    => '13:00',
				),
			'Wednesday' =>
				array(
					'active' => 'no',
					'start'  => '12:00',
					'end'    => '13:00',
				),
			'Thursday'  =>
				array(
					'active' => 'no',
					'start'  => '12:00',
					'end'    => '13:00',
				),
			'Friday'    =>
				array(
					'active' => 'no',
					'start'  => '12:00',
					'end'    => '13:00',
				),
			'Saturday'  =>
				array(
					'active' => 'no',
					'start'  => '12:00',
					'end'    => '13:00',
				),
		);

		$closing_hours = wp_parse_args( $custom, $defaults );
		appointments_update_worker_working_hours( $worker_id, $closing_hours, 'closed' );
	}


	function test_timetables_cache() {
		//  @TODO Skip temporary
		return;
		global $appointments;

		// Insert a worker
		$args = $this->factory->user->generate_args();
		$user_id_1 = $this->factory->user->create_object( $args );

		$args = $this->factory->user->generate_args();
		$user_id_2 = $this->factory->user->create_object( $args );

		$service_id = appointments_insert_service( array( 'name' => 'My Service' ) );

		$args = array(
			'ID' => $user_id_1,
			'price' => '19.7',
			'services_provided' => array( $service_id ),
			'dummy' => true
		);
		$result = appointments_insert_worker( $args );

		$time = time();
		$date = date( 'Y-m-01', $time );
		$capacity = appointments_get_capacity();
		$date_start = strtotime("{$date} 00:00");
		$service = appointments_get_service($service_id);
		$min_step_time = $appointments->get_min_time() * 60;
		$step = (!empty($service->duration) ? $service->duration : $min_step_time) * 60;
		$key = $date_start . '-' . $capacity . '-0' . '-' . $appointments->worker . '-' . date( 'Ym', $appointments->local_time ) . '-' . $step;



		// WORKERS

		// Trigger the timetables cache
		appointments_get_timetable( $date_start, $capacity );
		// This saves the timetables
		do_action( 'shutdown' );
		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertNotEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );

		// Insert another worker
		$args = array(
			'ID' => $user_id_2,
			'price' => '19.7',
			'services_provided' => array( $service_id ),
			'dummy' => true
		);
		appointments_insert_worker( $args );
		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );

		// Trigger the timetables cache
		appointments_get_timetable( $date_start, $capacity );

		// This saves the timetables
		do_action( 'shutdown' );

		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertNotEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );

		// Delete a worker
		appointments_delete_worker( $user_id_2 );
		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );

		// Trigger the timetables cache
		appointments_get_timetable( $date_start, $capacity );

		// This saves the timetables
		do_action( 'shutdown' );

		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertNotEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );

		// Update worker
		appointments_update_worker( $user_id_1, array( 'price' => '10' ) );
		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );


		// APPOINTMENTS

		// Trigger the timetables cache
		appointments_get_timetable( $date_start, $capacity );

		// This saves the timetables
		do_action( 'shutdown' );

		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertNotEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );

		// Insert appointment
		$args = array(
			'user' => $user_id_2,
			'email' => 'tester@tester.com',
			'name' => 'Tester',
			'phone' => '667788',
			'address' => 'An address',
			'city' => 'Madrid',
			'service' => $service_id,
			'worker' => $user_id_1,
			'price' => '90',
			'date' => 'December 18, 2024',
			'time' => '07:30',
			'note' => 'It\'s a note',
			'status' => 'paid',
			'location' => 5,
			'gcal_updated' => '2015-12-01',
			'gcal_ID' => 'test'
		);
		$app_id = appointments_insert_appointment( $args );

		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );

		// Trigger the timetables cache
		appointments_get_timetable( $date_start, $capacity );

		// This saves the timetables
		do_action( 'shutdown' );

		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertNotEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );

		// Update Appointment
		appointments_update_appointment( $app_id, array( 'address' => 'New address' ) );
		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );

		// Trigger the timetables cache
		appointments_get_timetable( $date_start, $capacity );

		// This saves the timetables
		do_action( 'shutdown' );

		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertNotEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );

		// Delete appointment
		appointments_delete_appointment( $app_id );
		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );


		// SERVICES

		// Insert a service

		// Trigger the timetables cache
		appointments_get_timetable( $date_start, $capacity );

		// This saves the timetables
		do_action( 'shutdown' );

		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertNotEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );

		$service_id_2 = appointments_insert_service( array( 'name' => 'My Service 2' ) );

		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );

		// Trigger the timetables cache
		appointments_get_timetable( $date_start, $capacity );

		// This saves the timetables
		do_action( 'shutdown' );

		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertNotEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );

		// Update service
		appointments_update_service( $service_id_2, array( 'name' => 'My Service updated' ) );
		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );

		// Trigger the timetables cache
		appointments_get_timetable( $date_start, $capacity );

		// This saves the timetables
		do_action( 'shutdown' );

		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertNotEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );

		// Delete service
		appointments_delete_service( $service_id_2 );
		$timetables_cache = get_transient( 'app_timetables' );
		$this->assertEmpty( $timetables_cache[ $key . '-' . $appointments->service ] );

	}


	/**
	 * @group is-busy
	 */
	function test_is_busy() {
		$next_monday = strtotime( 'next monday', time() );

		$service_id = $this->factory->service->create_object($this->factory->service->generate_args());

		$worker_args = $this->factory->worker->generate_args();
		$worker_args['services_provided'] = array( $service_id );
		$worker_id = $this->factory->worker->create_object( $worker_args );

		$app_args           = $this->factory->appointment->generate_args();
		$app_args['status'] = 'reserved';
		$app_args['name'] = 'Holidays';
		$app_args['date']   = strtotime( date( 'Y-m-d', $next_monday ) . ' 12:00:00' );
		$app_args['duration'] = 60;
		$app_args['worker'] = $worker_id;
		$app_args['service'] = $service_id;
		$app_id = $this->factory->appointment->create_object( $app_args );

		$appointments = appointments();
		$appointments->worker = $worker_id;
		$appointments->service = $service_id;

		$from = strtotime( date( 'Y-m-d', $next_monday ) . ' 12:00:00' );
		$to = strtotime( date( 'Y-m-d', $next_monday ) . ' 12:15:00' );

		$busy = $appointments->is_busy( $from, $to, 1 );
		$this->assertTrue( $busy );

		$from = strtotime( date( 'Y-m-d', $next_monday ) . ' 10:00:00' );
		$to = strtotime( date( 'Y-m-d', $next_monday ) . ' 10:15:00' );

		$busy = $appointments->is_busy( $from, $to, 1 );
		$this->assertFalse( $busy );
	}

	function test_get_available_workers() {
		$options = appointments_get_options();
		$options['min_time'] = 60;
		appointments_update_options( $options );
		$service_id = $this->factory->service->create_object($this->factory->service->generate_args());
		$worker_args = $this->factory->worker->generate_args();
		$worker_args['services_provided'] = array( $service_id );
		$worker_id_1 = $this->factory->worker->create_object( $worker_args );

		$worker_args = $this->factory->worker->generate_args();
		$worker_args['services_provided'] = array( $service_id );
		$worker_id_2 = $this->factory->worker->create_object( $worker_args );

		// Let's put worker 1 on holiday on 2016-12-28
		appointments_update_worker_exceptions( $worker_id_1, 'closed', '2016-12-28' );

		// Appointment for worker 2 on 2016-12-28
		$app_id = appointments_insert_appointment(
			array(
				'service'  => $service_id,
				'worker' => $worker_id_2,
				'status'   => 'confirmed',
				'date'     => strtotime( '2016-12-28 11:00:00' ),
				'duration' => 480
			)
		);

		appointments()->service = $service_id;
		appointments()->worker = 0;

		// Now there should not be any worker available on 2016-12-28 from 11:00 to 19:00
		$available = appointments()->available_workers(
			strtotime( '2016-12-28 11:00:00' ), // From
			strtotime( '2016-12-28 12:00:00' ) // To
		);

		// Though worker 2 has an appointment, this function does not check that
		$this->assertEquals( 1, $available );

		$available = appointments()->available_workers(
			strtotime( '2016-12-29 11:00:00' ), // From
			strtotime( '2016-12-29 12:00:00' ) // To
		);
		$this->assertEquals( 2, $available );

		// Now set a capacity for the service
		appointments_update_service( $service_id, array( 'capacity' => 1 ) );

		// Now it should return just one
		$available = appointments()->available_workers(
			strtotime( '2016-12-29 11:00:00' ), // From
			strtotime( '2016-12-29 12:00:00' ) // To
		);
		$this->assertEquals( 1, $available );
	}

	function test_get_timetable_slots() {
		$options = appointments_get_options();
		$options['min_time'] = 60;
		appointments_update_options( $options );
		$appointments = appointments();
		$next_monday = strtotime( date( 'Y-m-d 00:00:00', strtotime( 'next Monday', current_time( 'timestamp' ) ) ) );
		$next_tuesday = strtotime( date( 'Y-m-d 00:00:00', strtotime( 'next Tuesday', current_time( 'timestamp' ) ) ) );
		$next_wednesday = strtotime( date( 'Y-m-d 00:00:00', strtotime( 'next Wednesday', current_time( 'timestamp' ) ) ) );
		$next_thursday = strtotime( date( 'Y-m-d 00:00:00', strtotime( 'next Thursday', current_time( 'timestamp' ) ) ) );

		$args = $this->factory->service->generate_args();
		$args['duration'] = 60;
		$service_id = $this->factory->service->create_object( $args );
		// Delete default service
		foreach ( appointments_get_services() as $service ) {
			if ( $service->ID != $service_id ) {
				appointments_delete_service( $service->ID );
			}
		}

		$open_hours = $this->get_open_wh();
		$closed_hours = $this->get_closed_wh();
		appointments_update_worker_working_hours( 0, $open_hours, 'open' );
		appointments_update_worker_working_hours( 0, $closed_hours, 'closed' );

		// Monday slots
		$next_monday_slots = $appointments->_get_timetable_slots( $next_monday,1 );
		$expected_timeslots = $this->_get_expected_slots( 'Monday', $next_monday );
		$this->_test_expected_timeslots( $expected_timeslots, $next_monday_slots );
		foreach ( $next_monday_slots as $slot ) {
			if ( '19:30' === $slot['hours'] ) {
				// The service is not possible because it would end at 20:30 but Monday is opened until 20:00
				$this->assertEquals( 'notpossible service_notpossible', $slot['class'] );
			}
			else {
				$this->assertEquals( 'free', $slot['class'] );
			}
		}

		// Tuesday slots
		$next_tuesday_slots = $appointments->_get_timetable_slots( $next_tuesday,1 );
		$expected_timeslots = $this->_get_expected_slots( 'Tuesday', $next_tuesday );
		$this->_test_expected_timeslots( $expected_timeslots, $next_tuesday_slots );
		foreach ( $next_tuesday_slots as $slot ) {
			if ( $slot['hours'] >= '14:30' ) {
				// The service is not possible because it would end at 15:30 but Tuesday is opened until 15:00
				$this->assertEquals( 'notpossible service_notpossible', $slot['class'] );
			}
			else {
				$this->assertEquals( 'free', $slot['class'] );
			}
		}

		// Wednesday slots
		// Insert an appointment on Wednesday of 90 minutes
		$app_id = appointments_insert_appointment( array(
			'service' => $service_id,
			'date' => strtotime( date( 'Y-m-d 12:00:00', $next_wednesday ) ),
			'duration' => 90,
			'status' => 'confirmed'
		) );

		$next_wednesday_slots = $appointments->_get_timetable_slots( $next_wednesday, 1 );
		$expected_timeslots = $this->_get_expected_slots( 'Wednesday', $next_wednesday );
		$this->_test_expected_timeslots( $expected_timeslots, $next_wednesday_slots );
		foreach ( $next_wednesday_slots as $slot ) {
			if ( $slot['hours'] >= '11:30' && $slot['hours'] < '13:30' ) {
				// Busy by the appointment
				$this->assertEquals( 'busy', $slot['class'] );
			}
			elseif ( $slot['hours'] === '19:30' ) {
				$this->assertEquals( 'notpossible service_notpossible', $slot['class'] );
			}
			else {
				$this->assertEquals( 'free', $slot['class'] );
			}
		}

		// Thursday
		$next_thursday_slots = $appointments->_get_timetable_slots( $next_thursday, 1 );
		$expected_timeslots = $this->_get_expected_slots( 'Thursday', $next_thursday );
		$this->_test_expected_timeslots( $expected_timeslots, $next_thursday_slots );
		foreach ( $next_thursday_slots as $slot ) {
			if ( $slot['hours'] >= '11:30' && $slot['hours'] <= '12:30' ) {
				// The service is not possible because it would end at 15:30 but Tuesday is opened until 15:00
				$this->assertEquals( 'notpossible app_break', $slot['class'] );
			}
			elseif ( $slot['hours'] === '19:30' ) {
				$this->assertEquals( 'notpossible service_notpossible', $slot['class'] );
			}
			else {
				$this->assertEquals( 'free', $slot['class'] );
			}
		}
	}


	function test_undefined_service_should_be_busy_for_worker() {
//		$next_monday = strtotime( 'next monday', time() );
//
//		$service_id = $this->factory->service->create_object($this->factory->service->generate_args());
//
//		$worker_args = $this->factory->worker->generate_args();
//		$worker_args['services_provided'] = array( $service_id );
//		$worker_id = $this->factory->worker->create_object( $worker_args );
//
//		$app_args           = $this->factory->appointment->generate_args();
//		$app_args['status'] = 'reserved';
//		$app_args['name'] = 'Holidays';
//		$app_args['date']   = strtotime( date( 'Y-m-d', $next_monday ) . ' 12:00:00' );
//		$app_args['duration'] = 60;
//		$app_args['worker'] = $worker_id;
//		$app_id = $this->factory->appointment->create_object( $app_args );
//
//		$appointments = appointments();
//		$appointments->worker = $worker_id;
//		$appointments->service = $service_id;
//
//		$from = strtotime( date( 'Y-m-d', $next_monday ) . ' 12:00:00' );
//		$to = strtotime( date( 'Y-m-d', $next_monday ) . ' 12:15:00' );
//
//		$busy = $appointments->is_busy( $from, $to, 1 );
//		$this->assertTrue( $busy );
//
//		$from = strtotime( date( 'Y-m-d', $next_monday ) . ' 10:00:00' );
//		$to = strtotime( date( 'Y-m-d', $next_monday ) . ' 10:15:00' );
//
//		$busy = $appointments->is_busy( $from, $to, 1 );
//		$this->assertFalse( $busy );
	}

	function _test_expected_timeslots( $expected_timeslots, $day_slots ) {
		$this->assertCount( count( $expected_timeslots ), $day_slots );
		foreach ( $expected_timeslots as $key => $expected ) {
			$this->assertEquals( $expected['hours'], $day_slots[ $key ]['hours'] );
			$this->assertEquals( $expected['title'], $day_slots[ $key ]['title'] );
			$this->assertEquals( $expected['ccs'], $day_slots[ $key ]['ccs'] );
			$this->assertEquals( $expected['cce'], $day_slots[ $key ]['cce'] );
		}
	}

	function _get_expected_slots( $day, $start_date ) {
		$options = appointments_get_options();
		$open_hours = $this->get_open_wh();

		$open_max_hour = max( wp_list_pluck( wp_list_filter( $open_hours, array( 'active' => 'yes' ) ), 'end' ) );

		$expected_timeslots = array();
		$opened_wh = $open_hours[ $day ];
		$step = $options['min_time'] * 60;
		$start_time = strtotime( date( 'Y-m-d ' . $opened_wh['start'], $start_date ) );
		$end_time = strtotime( date( 'Y-m-d ' . $open_max_hour, $start_date ) );
		$time = $start_time;
		while ( $time <= $end_time ) {
			$expected_timeslot = array(
				'title' => date( 'Y-m-d H:i', $time ),
				'hours' => date( 'H:i', $time ),
				'ccs' => $time,
				'cce' => $time + $step
			);
			$expected_timeslots[] = $expected_timeslot;
			$time = $time + $step;
		}
		return $expected_timeslots;
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

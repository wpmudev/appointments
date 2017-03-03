<?php


/**
 * Class App_Workers_Test
 *
 * @group working-hours
 */
class App_Working_Hours_Test extends App_UnitTestCase {

	/**
	 * @group tmp
	 */
	function test_default_working_hours() {
		$open = appointments_get_worker_working_hours( 'open', 0, 0 );
		$closed = appointments_get_worker_working_hours( 'closed', 0, 0 );

		$this->assertNotEmpty( $open );
		$this->assertNotEmpty( $closed );
	}

	function test_update_worker_working_hours() {
		$worker_id = $this->factory->worker->create_object( $this->factory->worker->generate_args() );
		$result = appointments_update_worker_working_hours( $worker_id, $this->get_open_wh(), 'open' );
		$this->assertEquals( 1, $result );

		$open = appointments_get_worker_working_hours( 'open', $worker_id, 0 );
		$this->assertEquals( $open->hours, $this->get_open_wh() );
		$this->assertEquals( $open->worker, $worker_id );
		$this->assertEquals( $open->location, 0 );
		$this->assertEquals( $open->status, 'open' );

		$result = appointments_update_worker_working_hours( $worker_id, $this->get_closed_wh(), 'closed' );
		$this->assertEquals( 1, $result );

		$open = appointments_get_worker_working_hours( 'closed', $worker_id, 0 );
		$this->assertEquals( $open->hours, $this->get_closed_wh() );
		$this->assertEquals( $open->worker, $worker_id );
		$this->assertEquals( $open->location, 0 );
		$this->assertEquals( $open->status, 'closed' );
	}

	function test_delete_working_hours() {
		$worker_id = $this->factory->worker->create_object( $this->factory->worker->generate_args() );
		appointments_update_worker_working_hours( $worker_id, $this->get_open_wh(), 'open' );
		appointments_delete_worker_working_hours( $worker_id );

		$open = appointments_get_worker_working_hours( 'closed', $worker_id, 0 );
		$this->assertFalse( $open );
	}


	function test_working_hours_cache() {
		global $wpdb;

		$worker_id = $this->factory->worker->create_object( $this->factory->worker->generate_args() );
		$result = appointments_update_worker_working_hours( $worker_id, $this->get_open_wh(), 'open' );

		$num_queries = $wpdb->num_queries;
		$open = appointments_get_worker_working_hours( 'open', $worker_id, 0 );
		$this->assertEquals( ++$num_queries, $wpdb->num_queries );

		$open = appointments_get_worker_working_hours( 'open', $worker_id, 0 );
		$this->assertEquals( $num_queries, $wpdb->num_queries );

		$cached = wp_cache_get( 'app_working_hours' );
		$this->assertCount( 4, $cached );

	}

	function test_deprecated_function() {
		$this->remove_deprecated_filters();

		$worker_id = $this->factory->worker->create_object( $this->factory->worker->generate_args() );
		appointments_update_worker_working_hours( $worker_id, $this->get_open_wh(), 'open' );

		$open = appointments()->get_work_break( 0, $worker_id, 'open' );
		$open_new = appointments_get_worker_working_hours( 'open', $worker_id, 0 );

		$this->assertTrue( is_string( $open->hours ) );
		$open->hours = maybe_unserialize( $open->hours );
		$this->assertTrue( is_array( $open->hours ) );
		$this->assertEquals( $open, $open_new );

		appointments_delete_worker_working_hours( $worker_id );
		$open = appointments()->get_work_break( 0, $worker_id, 'open' );
		$open_new = appointments_get_worker_working_hours( 'open', $worker_id, 0 );
		$this->assertNull( $open );
		$this->assertFalse( $open_new );

		$this->add_deprecated_filters();
	}

	public function test_get_working_hours_range() {
		$worker_id = $this->factory->worker->create_object( $this->factory->worker->generate_args() );
		$open_hours = $this->get_open_wh();
		appointments_update_worker_working_hours( $worker_id, $open_hours, 'open' );
		$range = appointments_get_working_hours_range($worker_id);
		$this->assertEquals( array( 'min' => 7, 'max' => 20 ), $range );

		// Test edge case
		$open_hours['Monday']['start'] = '00:00';
		appointments_update_worker_working_hours( $worker_id, $open_hours, 'open' );
		$range = appointments_get_working_hours_range($worker_id);
		$this->assertEquals( array( 'min' => 0, 'max' => 20 ), $range );

		$open_hours['Monday']['start'] = '00:00';
		$open_hours['Monday']['end'] = '00:00';
		appointments_update_worker_working_hours( $worker_id, $open_hours, 'open' );
		$range = appointments_get_working_hours_range($worker_id);
		$this->assertEquals( array( 'min' => 0, 'max' => 24 ), $range );

		$open_hours['Monday']['start'] = '06:12';
		$open_hours['Monday']['end'] = '20:15';
		appointments_update_worker_working_hours( $worker_id, $open_hours, 'open' );
		$range = appointments_get_working_hours_range($worker_id);
		$this->assertEquals( array( 'min' => 6, 'max' => 21 ), $range );
	}


	public function test_get_weekly_schedule_slots() {
		$options = appointments_get_options();
		$worker_id = $this->factory->worker->create_object( $this->factory->worker->generate_args() );
		$open_hours = $this->get_open_wh();
		appointments_update_worker_working_hours( $worker_id, $open_hours, 'open' );

		// Set saturday as week start
		update_option( 'start_of_week', 6 );

		// Test monday
		$next_saturday = strtotime( 'next saturday' );
		$slots = appointments_get_weekly_schedule_slots( $next_saturday );

		$this->assertCount( 20, $slots['time_slots'] );
		$this->assertEquals(
			array(
				date( 'Y-m-d', $next_saturday ),
				date( 'Y-m-d', $next_saturday + ( 24 * 3600 * 1 ) ),
				date( 'Y-m-d', $next_saturday + ( 24 * 3600 * 2 ) ),
				date( 'Y-m-d', $next_saturday + ( 24 * 3600 * 3 ) ),
				date( 'Y-m-d', $next_saturday + ( 24 * 3600 * 4 ) ),
				date( 'Y-m-d', $next_saturday + ( 24 * 3600 * 5 ) ),
				date( 'Y-m-d', $next_saturday + ( 24 * 3600 * 6 ) ),
			),
			$slots['the_week']
		);

		// Test saturday
		$today = strtotime( '2016-12-17' );
		$slots = appointments_get_weekly_schedule_slots( $today );
		$this->assertCount( 18, $slots['time_slots'] );
		$this->assertEquals(
			array(
				'2016-12-17',
				'2016-12-18',
				'2016-12-19',
				'2016-12-20',
				'2016-12-21',
				'2016-12-22',
				'2016-12-23'
			),
			$slots['the_week']
		);
	}

	function test_appointments_is_worker_holiday() {
		$worker_id = $this->factory->worker->create_object( $this->factory->worker->generate_args() );
		$open_hours = $this->get_open_wh();
		appointments_update_worker_working_hours( $worker_id, $open_hours, 'open' );
		$next_monday = strtotime( 'next monday' );

		// Holidays for next monday and tuesday
		$holidays = array(
			date( 'Y-m-d', $next_monday ),
			date( 'Y-m-d', $next_monday + ( 3600 * 24 ) )
		);
		appointments_update_worker_exceptions( $worker_id, 'closed', implode( ',', $holidays ) );

		$check_date_from = strtotime( $holidays[0] . ' 11:00:00' );
		$check_date_to = strtotime($holidays[0] . ' 12:00:00');
		$this->assertTrue( appointments_is_worker_holiday( $worker_id, $check_date_from, $check_date_to ) );
	}

	/**
	 * @group temp
	 */
	function test_appointments_get_min_max_working_hours() {
		$worker_id = $this->factory->worker->create_object( $this->factory->worker->generate_args() );
		$open_hours = $this->get_open_wh();
		appointments_update_worker_working_hours( $worker_id, $open_hours, 'open' );

		$result = appointments_get_min_max_working_hours( $worker_id );
		$this->assertEquals( '7', $result['min'] );
		$this->assertEquals( '20', $result['max'] );

		// Edge cases
		$open_hours['Tuesday'] = array(
			'active' => 'yes',
			'start'  => '00:00',
			'end'    => '00:00',
			'weekday_number' => 2
		);
		appointments_update_worker_working_hours( $worker_id, $open_hours, 'open' );
		$result = appointments_get_min_max_working_hours( $worker_id );
		$this->assertEquals( '0', $result['min'] );
		$this->assertEquals( '24', $result['max'] );
	}


	function get_open_wh() {
		return array(
			'Sunday'    =>
				array(
					'active' => 'yes',
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
					'start'  => '10:30',
					'end'    => '20:00',
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
					'active' => 'no',
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

<?php


/**
 * Class App_Workers_Test
 *
 * @group working-hours
 */
class App_Working_Hours_Test extends App_UnitTestCase {

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
		$cache_key = 'working_hours_0_' . $worker_id . '_open';
		$this->assertEquals( $open, $cached[ $cache_key ] );

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


	function get_open_wh() {
		return array(
			'Sunday'    =>
				array(
					'active' => 'yes',
					'start'  => '07:00',
					'end'    => '20:00',
				),
			'Monday'    =>
				array(
					'active' => 'yes',
					'start'  => '10:30',
					'end'    => '20:00',
				),
			'Tuesday'   =>
				array(
					'active' => 'yes',
					'start'  => '10:30',
					'end'    => '20:00',
				),
			'Wednesday' =>
				array(
					'active' => 'yes',
					'start'  => '10:30',
					'end'    => '20:00',
				),
			'Thursday'  =>
				array(
					'active' => 'yes',
					'start'  => '10:30',
					'end'    => '20:00',
				),
			'Friday'    =>
				array(
					'active' => 'yes',
					'start'  => '10:30',
					'end'    => '20:00',
				),
			'Saturday'  =>
				array(
					'active' => 'yes',
					'start'  => '10:30',
					'end'    => '20:00',
				)
		);
	}

	function get_closed_wh() {

		return array(
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
				)
		);
	}
}

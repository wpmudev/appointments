<?php

/**
 * Class App_Addons_Locations_Test
 *
 * @group addons
 * @group addons_paddings
 */
class App_Addons_Paddings_Test extends App_UnitTestCase {
	function setUp() {
		parent::setUp();
		$this->load_addon( 'app-schedule-paddings' );
		$this->__init_addon();
	}

	private function __init_addon() {
		$addon = App_Schedule_Paddings::serve();
		$addon->initialize();
		$this->addon = $addon;
	}

	function test_init() {
		$this->assertTrue( class_exists( 'App_Schedule_Paddings' ) );
	}

	function test_set_paddings() {
		$options = appointments_get_options();
		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id = appointments_insert_service( $service_args );

		$worker_id = $this->factory->worker->create_object( $this->factory->worker->generate_args() );

		$services_padding = array();
		$services_padding[$service_id] = array(
			App_Schedule_Paddings::PADDING_BEFORE => 0,
			App_Schedule_Paddings::PADDING_AFTER => 15
		);
		update_option( 'appointments_services_padding', $services_padding );

		$options['service_padding'][$service_id] = array( 'before' => 0, 'after' => 15 );
		appointments_update_options( $options );

		// With default working hours
		$now = strtotime( 'next Monday', current_time( 'timestamp' ) );
		$slots = appointments_get_weekly_schedule_slots( $now, $service_id );
		$time_slots = $slots['time_slots'];
	}


}
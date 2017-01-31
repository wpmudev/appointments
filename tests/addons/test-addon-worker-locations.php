<?php

/**
 * @group locations
 * @group worker_locations
 */
class App_Addons_Worker_Locations_Test extends App_UnitTestCase {
	function setUp() {
		parent::setUp();
		$this->load_addon( 'app-locations-location_support' );
		$this->load_addon( 'app-locations-worker_locations' );

		$locations_class = App_Locations_LocationsWorker::serve();
		$locations_class->initialize();
	}

	function test_set_location_when_adding_a_worker() {

		$args = array(
			'address' => 'Location Address'
		);
		$location_id = appointments_insert_location( $args );

		$_POST['worker_location'] = $location_id;

		$worker_id = $this->factory->worker->create_object( array( 'user_email' => 'worker@email.dev' ) );

		/** @var App_Locations_WorkerLocations $addon */
		$worker_location = App_Locations_WorkerLocations::worker_to_location_id( $worker_id );

		$this->assertEquals( $worker_location, $location_id );
	}

}
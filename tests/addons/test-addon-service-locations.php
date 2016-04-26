<?php

/**
 * Class App_Addons_Locations_Test
 *
 * @group addons
 * @group addons_service_locations
 * @group addons_locations
 */
class App_Addons_Service_Locations_Test extends App_UnitTestCase {
	function setUp() {
		parent::setUp();
		$this->load_addon( 'app-locations-location_support' );
		$this->load_addon( 'app-locations-service_locations' );
	}

	private function __init_addon() {
		$locations_class = App_Locations_LocationsWorker::serve();
		$locations_class->initialize();
	}

	function test_init() {
		$this->assertTrue( class_exists( 'App_Locations_LocationsWorker' ) );
		$this->__init_addon();
		$this->assertTrue( class_exists( 'Appointments_Location' ) );
		$this->assertTrue( class_exists( 'App_Locations_ServiceLocations' ) );
	}

	function test_get_service_location() {
		$this->__init_addon();
		
		$args = array(
			'address' => 'Location Address'
		);
		$id = appointments_insert_location( $args );

		$args = $this->factory->service->generate_args();
		$args['ID'] = 5;
		$_POST['service_location'] = array();
		$_POST['service_location'][5] = $id;
		$service_id = $this->factory->service->create_object( $args );
		$service = appointments_get_service( $service_id );
		$this->assertFalse( $service->location );
	}



}
<?php

/**
 * Class App_Addons_Locations_Test
 *
 * @group addons
 * @group addons_locations
 */
class App_Addons_Locations_Test extends App_UnitTestCase {
	function setUp() {
		parent::setUp();
		$this->load_addon( 'app-locations-location_support' );
	}

	function test_init() {
		$this->assertTrue( class_exists( 'App_Locations_LocationsWorker' ) );
	}

	function test_add_location() {
		
	}

}
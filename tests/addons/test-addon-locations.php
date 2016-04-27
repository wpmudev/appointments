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

		$locations_class = App_Locations_LocationsWorker::serve();
		$locations_class->initialize();

		$this->assertTrue( class_exists( 'Appointments_Location' ) );
	}

	function test_add_location() {
		$args = array(
			'address' => 'Location Address'
		);
		$id = appointments_insert_location( $args );
		$this->assertNotEmpty( $id );
	}

	function test_get_location() {
		$args = array(
			'address' => 'Location Address'
		);
		$id = appointments_insert_location( $args );

		$location = appointments_get_location( $id );

		$this->assertInstanceOf( 'Appointments_Location', $location );
		$this->assertEquals( $location->address, $args['address'] );
	}

	function test_get_locations() {
		$args = array(
			'address' => 'Location Address 1'
		);
		$id_1 = appointments_insert_location( $args );

		$args = array(
			'address' => 'Location Address 2'
		);
		$id_2 = appointments_insert_location( $args );

		$locations = appointments_get_locations();
		$this->assertCount( 2, $locations );
	}

	function test_update_location() {
		$args = array(
			'address' => 'Location Address 1'
		);
		$id_1 = appointments_insert_location( $args );

		$args = array(
			'address' => 'Location Address 2'
		);
		$id_2 = appointments_insert_location( $args );

		appointments_update_location( $id_2, array( 'address' => 'New Location 2' ) );

		$location = appointments_get_location( $id_1 );
		$this->assertEquals( $location->address, 'Location Address 1' );

		$location = appointments_get_location( $id_2 );
		$this->assertEquals( $location->address, 'New Location 2' );
	}

	function test_delete_location() {
		$args = array(
			'address' => 'Location Address 1'
		);
		$id_1 = appointments_insert_location( $args );

		$args = array(
			'address' => 'Location Address 2'
		);
		$id_2 = appointments_insert_location( $args );

		appointments_delete_location( $id_2 );

		$location = appointments_get_location( $id_1 );
		$this->assertEquals( $location->address, 'Location Address 1' );

		$location = appointments_get_location( $id_2 );
		$this->assertFalse( $location );
	}

	function test_get_wrong_location() {
		$args = array(
			'address' => 'Location Address 1'
		);
		appointments_insert_location( $args );

		$this->assertFalse( appointments_get_location( 673467 ) );
	}

}
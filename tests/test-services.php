<?php

/**
 * Class App_Services_Test
 *
 * @group services
 */
class App_Services_Test extends App_UnitTestCase {

	function test_insert_service() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'page';
		$page_id = $this->factory->post->create_object( $args );


		$args = array(
			'ID' => 3,
			'name' => 'My Service',
			'capacity' => 10,
			'duration' => 60,
			'price' => '19.7',
			'page' => $page_id
		);
		$service_id = appointments_insert_service( $args );

		$this->assertEquals( $service_id, $args['ID'] );

		$service = appointments_get_service( $service_id );

		$this->assertEquals( $args['name'], $service->name );
		$this->assertEquals( $args['capacity'], $service->capacity );
		$this->assertEquals( $args['capacity'], $service->capacity );
		$this->assertEquals( $args['duration'], $service->duration );
		$this->assertEquals( $args['price'], $service->price );
		$this->assertEquals( $args['page'], $service->page );

		// No ID
		$args = array(
			'name' => 'My Service 2',
			'capacity' => 20,
			'duration' => 30,
			'price' => 40,
		);
		$service_id = appointments_insert_service( $args );

		$service = appointments_get_service( $service_id );

		$this->assertEquals( $args['name'], $service->name );
		$this->assertEquals( $args['capacity'], $service->capacity );
		$this->assertEquals( $args['capacity'], $service->capacity );
		$this->assertEquals( $args['duration'], $service->duration );
		$this->assertEquals( $args['price'], $service->price );
		$this->assertEquals( 0, $service->page );

		// No name
		$args = array(
			'name' => '',
			'capacity' => 20,
			'duration' => 30,
			'price' => 40,
		);
		$service_id = appointments_insert_service( $args );

		$this->assertFalse( $service_id );
	}

	function test_get_service() {
		$args = array(
			'name' => 'My Service',
			'capacity' => 10,
			'duration' => 60,
			'price' => 20,
		);
		$service_id = appointments_insert_service( $args );

		$this->assertInstanceOf( 'Appointments_Service', appointments_get_service( $service_id ) );

		$this->assertFalse( appointments_get_service( 8888 ) );
	}

	function test_delete_service() {
		$args = array(
				'name' => 'My Service 1',
		);
		$service_id = appointments_insert_service( $args );

		appointments_delete_service( $service_id );
		$this->assertFalse( appointments_get_service( $service_id ) );
	}

	function test_get_services() {

		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'page';
		$page_id = $this->factory->post->create_object( $args );

		$args = array(
			'name' => 'My Service 1',
		);
		appointments_insert_service( $args );

		$args = array(
			'name' => 'My Service 2',
			'page' => $page_id
		);
		appointments_insert_service( $args );

		$args = array(
			'name' => 'My Service 3',
		);
		appointments_insert_service( $args );

		$services = appointments_get_services();
		// There should be 3 + the default one
		$this->assertCount( 4, $services );
		$services = appointments_get_services( array( 'count' => true ) );
		$this->assertEquals( 4, $services );

		$services = appointments_get_services( array( 'page' => $page_id ) );
		$this->assertCount( 1, $services );
		$services = appointments_get_services( array( 'count' => true, 'page' => $page_id ) );
		$this->assertEquals( 1, $services );

		$services = appointments_get_services( array( 'page' => 8888 ) );
		$this->assertCount( 0, $services );
		$services = appointments_get_services( array( 'count' => true, 'page' => 8888 ) );
		$this->assertEquals( 0, $services );

		$services = appointments_get_services( array( 'fields' => 'ID' ) );
		$this->assertCount( 4, $services );
		$this->assertEquals( $services[0], '1' );

		$services = appointments_get_services( array( 'fields' => 'ID', 'page' => $page_id ) );
		$this->assertCount( 1, $services );
		$this->assertEquals( $services[0], '3' );
	}

	function test_update_service() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'page';
		$page_id = $this->factory->post->create_object( $args );

		$args = array(
			'name' => 'My Service 1',
			'price' => 10.1,
			'page' => $page_id
		);
		$service_id = appointments_insert_service( $args );

		appointments_update_service( $service_id, array( 'name' => 'New name', 'page' => 0, 'price' => 20.5 ) );

		$service = appointments_get_service( $service_id );
		$this->assertEquals( 'New name', $service->name );
		$this->assertEquals( 0, $service->page );
		$this->assertEquals( 20.5, $service->price );

		// Change ID
		appointments_update_service( $service_id, array( 'ID' => 20 ) );
		$this->assertFalse( appointments_get_service( $service_id ) );
		$service = appointments_get_service( 20 );
		$this->assertEquals( 'New name', $service->name );
		$this->assertEquals( 0, $service->page );
	}


	function test_get_min_price() {
		$args = array(
				'name' => 'My Service 1',
				'price' => 1.1
		);
		appointments_insert_service( $args );

		$args = array(
				'name' => 'My Service 2',
				'price' => 0.5
		);
		appointments_insert_service( $args );

		$args = array(
				'name' => 'My Service 3',
				'price' => 10
		);
		appointments_insert_service( $args );

		$price = appointments_get_services_min_price();
		$this->assertEquals( 0.5, $price );
	}

	function test_get_min_service_id() {
		$args = array(
			'name' => 'My Service 1',
			'price' => 1.1
		);
		appointments_insert_service( $args );

		$args = array(
			'name' => 'My Service 2',
			'price' => 0.5
		);
		appointments_insert_service( $args );

		$args = array(
			'name' => 'My Service 3',
			'price' => 10
		);
		appointments_insert_service( $args );

		$min_id = appointments_get_services_min_id();
		$this->assertEquals( 1, $min_id ); // Default service has ID = 1

		appointments_delete_service( 1 );

		$min_id = appointments_get_services_min_id();
		$this->assertEquals( 2, $min_id ); // Default service has ID = 1
	}


	/**
	 * @group cache
	 */
	function test_services_cache() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'page';
		$page_id = $this->factory->post->create_object( $args );

		$args = array(
			'name' => 'My Service 1'
		);
		$service_id = appointments_insert_service( $args );

		$service = wp_cache_get( $service_id, 'app_services' );
		$this->assertFalse( $service );

		// This will set the cache
		appointments_get_service( $service_id );

		$service = wp_cache_get( $service_id, 'app_services' );
		$this->assertEquals( $service->name, 'My Service 1' );

		appointments_delete_service( $service_id );
		$service = wp_cache_get( $service_id, 'app_services' );
		$this->assertFalse( $service );

		$service_id = appointments_insert_service( $args );
		appointments_get_service( $service_id );
		appointments_update_service( $service_id, array( 'name' => 'New name' ) );
		$service = wp_cache_get( $service_id, 'app_services' );
		$this->assertFalse( $service );

		$service_id = appointments_insert_service( array( 'name' => 'Service 1' ) );
		appointments_insert_service( array( 'name' => 'Service 2', 'page' => $page_id ) );

		$services = appointments_get_services();
		$this->assertCount( 4, $services );
		$cache = wp_cache_get( 'app_get_services' );
		$this->assertCount( 1, $cache );

		$services = appointments_get_services( array( 'page' => 8888 ) );
		$this->assertCount( 0, $services );
		$cache = wp_cache_get( 'app_get_services' );
		$this->assertCount( 1, $cache );

		$services = appointments_get_services( array( 'page' => $page_id ) );
		$this->assertCount( 1, $services );
		$cache = wp_cache_get( 'app_get_services' );
		$this->assertCount( 2, $cache );

		$this->assertNotEmpty( wp_cache_get( $service_id, 'app_services' ) );

		// If we insert another service, cache sould be cleared
		appointments_insert_service( array( 'name' => 'Service 3' ) );
		$this->assertFalse( wp_cache_get( 'app_get_services' ) );

		// If we select again, cache should be refreshed
		$services = appointments_get_services();
		$this->assertCount( 5, $services );
		$cache = wp_cache_get( 'app_get_services' );
		$this->assertCount( 1, $cache );

		// Min ID cache
		$args = array(
			'name' => 'My Service 2',
			'price' => 0.5
		);
		appointments_insert_service( $args );

		$min_id = appointments_get_services_min_id();
		$cached_id = wp_cache_get( 'min_service_id', 'appointments_services' );
		$this->assertEquals( $cached_id, $min_id ); // Should be 1

		appointments_delete_service( $cached_id );
		$this->assertFalse( wp_cache_get( 'min_service_id', 'appointments_services' ) ); // Should be 1

		$min_id = appointments_get_services_min_id();
		$this->assertEquals( wp_cache_get( 'min_service_id', 'appointments_services' ), $min_id );

	}

	function test_get_service_by_name() {
		$args = $this->factory->service->generate_args();
		$service_id = $this->factory->service->create_object( $args );

		$service = appointments_get_service_by_name( $args['name'] );
		$this->assertEquals( $service_id, $service->ID );

		// Let's try with rare characters
		$args = $this->factory->service->generate_args();
		$args['name'] = 'Massage / PT / Gymintro (Ange i kommentar vilken tjänst du vill boka)';
		$service_id = $this->factory->service->create_object( $args );

		$service = appointments_get_service_by_name( $args['name'] );
		$this->assertEquals( $service_id, $service->ID );
	}

	function test_get_service_name() {
		$args = $this->factory->service->generate_args();
		$service_id = $this->factory->service->create_object( $args );
		$name = appointments_get_service_name( $service_id );
		$this->assertEquals( $args['name'], $name );
	}


}


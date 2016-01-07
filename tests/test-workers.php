<?php

/**
 * Class App_Workers_Test
 *
 * @group workers
 */
class App_Workers_Test extends App_UnitTestCase {

	function test_insert_worker() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'page';
		$page_id = $this->factory->post->create_object( $args );

		$args = $this->factory->user->generate_args();
		$user_id = $this->factory->user->create_object( $args );

		$service_id = appointments_insert_service( array( 'name' => 'My Service' ) );

		$args = array(
			'ID' => $user_id,
			'price' => '19.7',
			'page' => $page_id,
			'services_provided' => array( $service_id ),
			'dummy' => true
		);
		$result = appointments_insert_worker( $args );

		$this->assertTrue( $result );
		$worker = appointments_get_worker( $user_id );

		$this->assertEquals( $worker->ID, $user_id );
		$this->assertEquals( $worker->price, $args['price'] );
		$this->assertEquals( $worker->page, $args['page'] );
		$this->assertEquals( $worker->services_provided, array( $service_id ) );
		$this->assertEquals( $worker->dummy, 1 );

		// Reset
		appointments_delete_worker( $user_id );

		// Wrong service ID and page
		$args = array(
			'ID' => $user_id,
			'price' => '19.7',
			'page' => 8888,
			'services_provided' => array( $service_id, 8888 ),
			'dummy' => true
		);
		$result = appointments_insert_worker( $args );

		$this->assertTrue( $result );
		$worker = appointments_get_worker( $user_id );

		$this->assertEquals( $worker->ID, $user_id );
		$this->assertEquals( $worker->price, $args['price'] );
		$this->assertEquals( $worker->page, 0 );
		$this->assertEquals( $worker->services_provided, array( $service_id ) );
		$this->assertEquals( $worker->dummy, 1 );

		// Reset
		appointments_delete_worker( $user_id );

		// Wrong user ID
		$args = array(
			'ID' => 8888,
			'price' => '19.7',
			'page' => 8888,
			'services_provided' => array( $service_id, 8888 ),
			'dummy' => true
		);
		$result = appointments_insert_worker( $args );

		$this->assertFalse( $result );


		// Dummy field tests
		$args = array(
			'ID' => $user_id,
			'price' => '19.7',
			'page' => $page_id,
			'services_provided' => array( $service_id ),
			'dummy' => false
		);
		appointments_insert_worker( $args );

		$worker = appointments_get_worker( $user_id );

		$this->assertEquals( $worker->dummy, '' );

		appointments_delete_worker( $user_id );

		$args = array(
			'ID' => $user_id,
			'price' => '19.7',
			'page' => $page_id,
			'services_provided' => array( $service_id ),
		);
		appointments_insert_worker( $args );

		$worker = appointments_get_worker( $user_id );

		$this->assertEquals( $worker->dummy, '' );

		// Delete the user

		wp_delete_user( $user_id, false );
		$worker = appointments_get_worker( $user_id );
		$this->assertFalse( $worker );

	}

	function test_get_worker() {
		$args = $this->factory->user->generate_args();
		$user_id = $this->factory->user->create_object( $args );
		$service_id = appointments_insert_service( array( 'name' => 'My Service' ) );

		$args = array(
			'ID' => $user_id,
			'services_provided' => array( $service_id ),
		);
		appointments_insert_worker( $args );

		$this->assertInstanceOf( 'Appointments_Worker', appointments_get_worker( $user_id ) );

		$this->assertFalse( appointments_get_worker( 8888 ) );
	}


	function test_get_workers() {

		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'page';
		$page_id = $this->factory->post->create_object( $args );

		$args = $this->factory->user->generate_args();
		$user_id_1 = $this->factory->user->create_object( $args );

		$args = $this->factory->user->generate_args();
		$user_id_2 = $this->factory->user->create_object( $args );

		$service_id_1 = appointments_insert_service( array( 'name' => 'My Service 1' ) );
		$service_id_2 = appointments_insert_service( array( 'name' => 'My Service 2' ) );

		$args = array(
			'ID' => $user_id_1,
			'services_provided' => array( $service_id_1 )
		);
		appointments_insert_worker( $args );

		$args = array(
			'ID' => $user_id_2,
			'services_provided' => array( $service_id_1, $service_id_2 ),
			'page' => $page_id
		);
		appointments_insert_worker( $args );


		$workers = appointments_get_workers();
		$this->assertCount( 2, $workers );

		$workers = appointments_get_workers( array( 'page' => $page_id ) );
		$this->assertCount( 1, $workers );

		$workers = appointments_get_workers( array( 'user_id' => $user_id_1 ) );
		$this->assertCount( 1, $workers );

		$workers = appointments_get_workers( array( 'user_id' => 8888 ) );
		$this->assertEmpty( $workers );

		$workers = appointments_get_workers( array( 'count' => true ) );
		$this->assertEquals( 2, $workers );

		$workers = appointments_get_workers( array( 'limit' => 1 ) );
		$this->assertCount( 1, $workers );

		$workers = appointments_get_workers( array( 'with_page' => true ) );
		$this->assertCount( 1, $workers );

	}

	function test_update_worker() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'page';
		$page_id = $this->factory->post->create_object( $args );

		$args = $this->factory->user->generate_args();
		$user_id_1 = $this->factory->user->create_object( $args );

		$args = $this->factory->user->generate_args();
		$user_id_2 = $this->factory->user->create_object( $args );

		$service_id_1 = appointments_insert_service( array( 'name' => 'My Service 1' ) );
		$service_id_2 = appointments_insert_service( array( 'name' => 'My Service 2' ) );

		$args = array(
			'ID' => $user_id_1,
			'price' => '19.7',
			'page' => $page_id,
			'services_provided' => array( $service_id_1 ),
			'dummy' => true
		);
		appointments_insert_worker( $args );

		$result = appointments_update_worker( $user_id_1, array( 'price' => '10', 'page' => 0, 'dummy' => false, 'services_provided' => array( $service_id_2 ), 'ID' => $user_id_2 ) );
		$this->assertTrue( $result );
		// The old user should not exist now
		$this->assertFalse( appointments_get_worker( $user_id_1 ) );

		// We have changed the user ID
		$worker = appointments_get_worker( $user_id_2 );
		$this->assertEquals( $worker->services_provided, array( $service_id_2 ) );
		$this->assertEquals( 0, $worker->page );
		$this->assertEquals( 10, $worker->price );
		$this->assertEquals( '', $worker->dummy );

		// Wrong user ID
		$result = appointments_update_worker( $user_id_2, array( 'ID' => 8888 ) );
		$this->assertFalse( $result );

	}

	function test_get_worker_services() {
		$args = $this->factory->user->generate_args();
		$user_id = $this->factory->user->create_object( $args );

		$service_id_1 = appointments_insert_service( array( 'name' => 'My Service 1' ) );
		$service_id_2 = appointments_insert_service( array( 'name' => 'My Service 2' ) );

		$args = array(
			'ID' => $user_id,
			'services_provided' => array( $service_id_1, $service_id_2 ),
			'dummy' => false
		);
		appointments_insert_worker( $args );

		$services = appointments_get_worker_services( $user_id );
		$this->assertCount( 2, $services );
		$this->assertInstanceOf( 'Appointments_Service', $services[0] );
		$this->assertInstanceOf( 'Appointments_Service', $services[1] );
	}

	/**
	 * @group cache
	 */
	function test_workers_cache() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'page';
		$page_id = $this->factory->post->create_object( $args );

		$service_id = appointments_insert_service( array( 'name' => 'My Service' ) );

		$args = $this->factory->user->generate_args();
		$user_id = $this->factory->user->create_object( $args );
		$args = $this->factory->user->generate_args();
		$user_id_2 = $this->factory->user->create_object( $args );
		$args = $this->factory->user->generate_args();
		$user_id_3 = $this->factory->user->create_object( $args );

		$args = array(
			'ID' => $user_id,
			'price' => '19.7',
			'page' => $page_id,
			'services_provided' => array( $service_id ),
			'dummy' => true
		);
		appointments_insert_worker( $args );

		$worker = wp_cache_get( $user_id, 'app_workers' );
		$this->assertFalse( $worker );

		// This will set the cache
		appointments_get_worker( $user_id );

		$worker = wp_cache_get( $user_id, 'app_workers' );
		$this->assertEquals( $worker->ID, $user_id );

		appointments_delete_worker( $user_id );
		$worker = wp_cache_get( $service_id, 'app_workers' );
		$this->assertFalse( $worker );

		appointments_insert_worker( $args );
		appointments_get_worker( $user_id );
		appointments_update_worker( $user_id, array( 'dummy' => false ) );
		$worker = wp_cache_get( $user_id, 'app_workers' );
		$this->assertFalse( $worker );

		appointments_insert_worker( array( 'ID' => $user_id_2, 'services_provided' => array( $service_id ) ) );

		$workers = appointments_get_workers();
		$this->assertCount( 2, $workers );
		$cache = wp_cache_get( 'app_get_workers' );
		$this->assertCount( 1, $cache );

		$workers = appointments_get_workers( array( 'page' => 8888 ) );
		$this->assertCount( 0, $workers );
		$cache = wp_cache_get( 'app_get_workers' );
		$this->assertCount( 1, $cache );

		$workers = appointments_get_workers( array( 'page' => $page_id ) );
		$this->assertCount( 1, $workers );
		$cache = wp_cache_get( 'app_get_workers' );
		$this->assertCount( 2, $cache );

		$this->assertNotEmpty( wp_cache_get( $user_id, 'app_workers' ) );

		// If we insert another worker, cache sould be cleared
		appointments_insert_worker( array( 'ID' => $user_id_3, 'services_provided' => array( $service_id ) ) );
		$this->assertFalse( wp_cache_get( 'app_get_workers' ) );

		// If we select again, cache should be refreshed
		$workers = appointments_get_workers();
		$this->assertCount( 3, $workers );
		$cache = wp_cache_get( 'app_get_workers' );
		$this->assertCount( 1, $cache );

		wp_cache_flush();

		// If we get all workers, they should be added to workers list cache
		appointments_get_workers();
		$worker = wp_cache_get( $user_id, 'app_workers' );
		$this->assertEquals( $worker->ID, $user_id );


	}

	function test_get_workers_by_service() {
		$args = $this->factory->user->generate_args();
		$user_id_1 = $this->factory->user->create_object( $args );

		$args = $this->factory->user->generate_args();
		$user_id_2 = $this->factory->user->create_object( $args );

		$service_id_1 = appointments_insert_service( array( 'name' => 'My Service' ) );
		$service_id_2 = appointments_insert_service( array( 'name' => 'My Service 2' ) );

		$args = array(
			'ID' => $user_id_1,
			'price' => '19.7',
			'services_provided' => array( $service_id_1 ),
			'dummy' => true
		);
		appointments_insert_worker( $args );


		$args = array(
			'ID' => $user_id_2,
			'price' => '19.7',
			'services_provided' => array( $service_id_1, $service_id_2 ),
			'dummy' => true
		);
		appointments_insert_worker( $args );

		$workers = appointments_get_workers_by_service( $service_id_1 );
		$this->assertCount( 2, $workers );
		$workers = appointments_get_workers_by_service( $service_id_2 );
		$this->assertCount( 1, $workers );

	}


}
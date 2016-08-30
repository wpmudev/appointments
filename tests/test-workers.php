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

		// Test the deprecated function
		$this->remove_deprecated_filters();
		global $appointments;
		$this->assertEquals( $appointments->get_workers(), appointments_get_workers() );
		$this->assertEquals( $appointments->get_workers( 'name ASC' ), appointments_get_workers( array( 'orderby' => 'name ASC' )) );
		$this->add_deprecated_filters();

	}

	function test_get_all_workers() {
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
		);
		appointments_insert_worker( $args );

		$workers = appointments_get_all_workers();
		$this->assertCount( 2, $workers );

		$this->assertEquals( wp_cache_get( 'app_all_workers' ), $workers );
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

	function test_deprecated_is_single_worker() {
		global $appointments;

		$service_id_1 = appointments_insert_service( array( 'name' => 'My Service' ) );
		$service_id_2 = appointments_insert_service( array( 'name' => 'My Service 2' ) );

		$args = $this->factory->user->generate_args();
		$user_id_1 = $this->factory->user->create_object( $args );
		$args = $this->factory->user->generate_args();
		$user_id_2 = $this->factory->user->create_object( $args );
		$args = $this->factory->user->generate_args();
		$user_id_3 = $this->factory->user->create_object( $args );

		$args = array(
			'ID' => $user_id_1,
			'services_provided' => array( $service_id_1 ),
		);
		appointments_insert_worker( $args );

		$args = array(
			'ID' => $user_id_2,
			'services_provided' => array( $service_id_2 ),
		);
		appointments_insert_worker( $args );

		$args = array(
			'ID' => $user_id_3,
			'services_provided' => array( $service_id_2 ),
		);
		appointments_insert_worker( $args );

		$this->remove_deprecated_filters();
		$this->assertEquals( $appointments->is_single_worker( $service_id_1 ), $user_id_1 );
		$this->assertFalse( $appointments->is_single_worker( $service_id_2 ) );
		$this->add_deprecated_filters();
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

		$this->assertCount( 2, wp_cache_get( 'app_workers_by_service' ) );

		// Test the deprecated function
		$this->remove_deprecated_filters();
		global $appointments;
		$this->assertEquals( $appointments->get_workers_by_service( $service_id_1 ), appointments_get_workers_by_service( $service_id_1 ) );
		$this->assertEquals( $appointments->get_workers_by_service( $service_id_2 ), appointments_get_workers_by_service( $service_id_2 ) );
		$this->add_deprecated_filters();

	}

	function test_appointments_is_worker() {
		$args = $this->factory->user->generate_args();
		$user_id_1 = $this->factory->user->create_object( $args );

		$args = $this->factory->user->generate_args();
		$user_id_2 = $this->factory->user->create_object( $args );

		$service_id = appointments_insert_service( array( 'name' => 'My Service' ) );

		$args = array(
			'ID' => $user_id_1,
			'services_provided' => array( $service_id ),
		);
		appointments_insert_worker( $args );

		$this->assertTrue( appointments_is_worker( $user_id_1 ) );
		$this->assertFalse( appointments_is_worker( $user_id_2 ) );

		// Test deprecated function
		global $appointments;
		$this->remove_deprecated_filters();
		$this->assertTrue( $appointments->is_worker( $user_id_1 ) );
		$this->assertFalse( $appointments->is_worker( $user_id_2 ) );
		$this->add_deprecated_filters();
	}

	function test_get_worker_name() {
		global $appointments;

		$args = $this->factory->user->generate_args();
		$args['user_login'] = 'userlogin';
		$args['display_name'] = 'Display Name';
		$user_id_1 = $this->factory->user->create_object( $args );

		$service_id = appointments_insert_service( array( 'name' => 'My Service' ) );

		$args = array(
			'ID' => $user_id_1,
			'services_provided' => array( $service_id ),
		);
		appointments_insert_worker( $args );

		$this->remove_deprecated_filters();
		$this->assertEquals( 'Display Name', appointments_get_worker_name( $user_id_1 ) );
		$this->assertEquals( 'Display Name', $appointments->get_worker_name( $user_id_1 ) );

		$this->assertEquals( 'userlogin', appointments_get_worker_name( $user_id_1, 'user_login' ) );
		$this->assertEquals( 'userlogin', $appointments->get_worker_name( $user_id_1, false ) );

		$this->assertEquals( 'A specialist', appointments_get_worker_name( 0 ) );
		$this->assertEquals( 'A specialist', $appointments->get_worker_name( 0 ) );
		// Log in the worker
		wp_set_current_user( $user_id_1 );
		$this->assertEquals( 'Our staff', appointments_get_worker_name( 0 ) );
		$this->assertEquals( 'Our staff', $appointments->get_worker_name( 0 ) );

		// If there's a user meta set, it will return it no matter what we pass to the second argument
		update_user_meta( $user_id_1, 'app_name', 'Meta Name' );

		$this->assertEquals( 'Our staff', appointments_get_worker_name( 0 ) );
		$this->assertEquals( 'Our staff', $appointments->get_worker_name( 0 ) );
		$this->assertEquals( 'Meta Name', appointments_get_worker_name( $user_id_1 ) );
		$this->assertEquals( 'Meta Name', $appointments->get_worker_name( $user_id_1 ) );

		$this->add_deprecated_filters();

		wp_set_current_user( 0 );
	}

	/**
	 * @group 161163
	 * @url http://premium.wpmudev.org/forums/topic/updated-appointments-and-now-not-working
	 *
	 * Passing "name" as orderby argument was triggering a DB error
	 */
	function test_order_workers() {
		$args = $this->factory->user->generate_args( array( 'user_login' => 'bbbbb' ) );
		$user_id_1 = $this->factory->user->create_object( $args );

		$args = $this->factory->user->generate_args( array( 'user_login' => 'aaaaa' ) );
		$user_id_2 = $this->factory->user->create_object( $args );

		$args = $this->factory->user->generate_args( array( 'user_login' => 'ccccc' ) );
		$user_id_3 = $this->factory->user->create_object( $args );

		$service_id = appointments_insert_service( array( 'name' => 'My Service' ) );

		$args = array(
			'ID' => $user_id_1,
			'services_provided' => array( $service_id )
		);
		appointments_insert_worker( $args );

		$args = array(
			'ID' => $user_id_2,
			'services_provided' => array( $service_id )
		);
		appointments_insert_worker( $args );

		$args = array(
			'ID' => $user_id_3,
			'services_provided' => array( $service_id )
		);
		appointments_insert_worker( $args );

		$args = array(
			'orderby' => 'ID ASC'
		);
		$workers = appointments_get_workers( $args );
		$ids = wp_list_pluck( $workers, 'ID' );
		$this->assertEquals( $ids, array( $user_id_1, $user_id_2, $user_id_3 ) );

		$args = array(
			'orderby' => 'ID DESC'
		);
		$workers = appointments_get_workers( $args );
		$ids = wp_list_pluck( $workers, 'ID' );
		$this->assertEquals( $ids, array( $user_id_3, $user_id_2, $user_id_1 ) );

		$args = array(
			'orderby' => 'name DESC'
		);
		$workers = appointments_get_workers( $args );
		$names = array();
		foreach ( $workers as $worker ) {
			$names[] = $worker->get_name();
		}
		$this->assertEquals( $names, array( 'ccccc', 'bbbbb', 'aaaaa' ) );

		$args = array(
			'orderby' => 'name ASC'
		);
		$workers = appointments_get_workers( $args );
		$names = array();
		foreach ( $workers as $worker ) {
			$names[] = $worker->get_name();
		}
		$this->assertEquals( $names, array( 'aaaaa', 'bbbbb', 'ccccc' ) );

		$args = array(
			'orderby' => 'name'
		);
		$workers = appointments_get_workers( $args );
		$names = array();
		foreach ( $workers as $worker ) {
			$names[] = $worker->get_name();
		}
		$this->assertEquals( $names, array( 'aaaaa', 'bbbbb', 'ccccc' ) );
	}

	/**
	 * @group exceptions
	 */
	function test_insert_worker_exceptions() {
		$args = $this->factory->user->generate_args( array( 'user_login' => 'bbbbb' ) );
		$user_id_1 = $this->factory->user->create_object( $args );

		$args = $this->factory->user->generate_args( array( 'user_login' => 'aaaaa' ) );
		$user_id_2 = $this->factory->user->create_object( $args );

		$service_id = appointments_insert_service( array( 'name' => 'My Service' ) );

		$args = array(
			'ID' => $user_id_1,
			'services_provided' => array( $service_id )
		);
		appointments_insert_worker( $args );

		$args = array(
			'ID' => $user_id_2,
			'services_provided' => array( $service_id )
		);
		appointments_insert_worker( $args );

		$table = appointments_get_table( 'exceptions' );
		$data = array(
			array(
				'worker' => $user_id_1,
				'status' => 'closed',
				'value' => '2016-09-15,2016-09-21,2016-09-22'
			),
			array(
				'worker' => $user_id_1,
				'status' => 'open',
				'value' => '2016-08-18,2016-08-19,2016-08-20,2016-08-25'
			)
		);

		foreach ( $data as $row ) {
			$result = appointments_update_worker_exceptions( $row['worker'], $row['status'], $row['value'] );
		}

		$open = appointments_get_worker_exceptions( $user_id_1, 'open' );
		$closed = appointments_get_worker_exceptions( $user_id_1, 'closed' );

		$this->assertEquals( $data[1]['value'], $open->days );
		$this->assertEquals( $data[0]['value'], $closed->days );

		$this->assertNull( appointments_get_worker_exceptions( $user_id_2, 'open' ) );
		$this->assertNull( appointments_get_worker_exceptions( $user_id_2, 'closed' ) );

	}

	/**
	 * @group exceptions
	 */
	public function test_update_worker_exceptions() {
		$args = $this->factory->user->generate_args( array( 'user_login' => 'bbbbb' ) );
		$user_id_1 = $this->factory->user->create_object( $args );

		$service_id = appointments_insert_service( array( 'name' => 'My Service' ) );

		$args = array(
			'ID' => $user_id_1,
			'services_provided' => array( $service_id )
		);
		appointments_insert_worker( $args );

		$data = array(
			array(
				'worker' => $user_id_1,
				'status' => 'closed',
				'value' => '2016-09-15,2016-09-21,2016-09-22'
			),
			array(
				'worker' => $user_id_1,
				'status' => 'open',
				'value' => '2016-08-18,2016-08-19,2016-08-20,2016-08-25'
			)
		);

		foreach ( $data as $row ) {
			$result = appointments_update_worker_exceptions( $row['worker'], $row['status'], $row['value'] );
		}

		appointments_update_worker_exceptions( $user_id_1, 'open', '2016-09-15,2016-09-21' );
		$open = appointments_get_worker_exceptions( $user_id_1, 'open' );
		$closed = appointments_get_worker_exceptions( $user_id_1, 'closed' );
		$this->assertEquals( '2016-09-15,2016-09-21', $open->days );
		$this->assertEquals( $data[0]['value'], $closed->days );

		appointments_update_worker_exceptions( $user_id_1, 'closed', '' );
		$open = appointments_get_worker_exceptions( $user_id_1, 'open' );
		$closed = appointments_get_worker_exceptions( $user_id_1, 'closed' );
		$this->assertEquals( '2016-09-15,2016-09-21', $open->days );
		$this->assertEquals( '', $closed->days );
	}

	public function test_worker_exceptions_cache() {
		global $wpdb;

		$args = $this->factory->user->generate_args( array( 'user_login' => 'bbbbb' ) );
		$user_id_1 = $this->factory->user->create_object( $args );

		$service_id = appointments_insert_service( array( 'name' => 'My Service' ) );

		$args = array(
			'ID' => $user_id_1,
			'services_provided' => array( $service_id )
		);
		appointments_insert_worker( $args );

		$data = array(
			array(
				'worker' => $user_id_1,
				'status' => 'closed',
				'value' => '2016-09-15,2016-09-21,2016-09-22'
			),
			array(
				'worker' => $user_id_1,
				'status' => 'open',
				'value' => '2016-08-18,2016-08-19,2016-08-20,2016-08-25'
			)
		);

		foreach ( $data as $row ) {
			$result = appointments_update_worker_exceptions( $row['worker'], $row['status'], $row['value'] );
		}

		$open = appointments_get_worker_exceptions( $user_id_1, 'open' );
		$closed = appointments_get_worker_exceptions( $user_id_1, 'closed' );

		$current_queries = $wpdb->num_queries;

		$open = appointments_get_worker_exceptions( $user_id_1, 'open' );
		$closed = appointments_get_worker_exceptions( $user_id_1, 'closed' );

		$this->assertEquals( $current_queries, $wpdb->num_queries );
	}

	/**
	 * @group exceptions
	 */
	public function test_should_save_null_worker_exceptions() {
		$data = array(
			array(
				'worker' => 0,
				'status' => 'closed',
				'value' => '2016-09-15,2016-09-21,2016-09-22'
			),
			array(
				'worker' => 0,
				'status' => 'open',
				'value' => '2016-08-18,2016-08-19,2016-08-20,2016-08-25'
			)
		);

		foreach ( $data as $row ) {
			appointments_update_worker_exceptions( $row['worker'], $row['status'], $row['value'] );
		}

		$open = appointments_get_worker_exceptions( 0, 'open' );
		$closed = appointments_get_worker_exceptions( 0, 'closed' );

		$this->assertEquals( $data[1]['value'], $open->days );
		$this->assertEquals( $data[0]['value'], $closed->days );
	}


}
<?php

/**
 * @group appointments
 */
class App_Appointments_Test extends App_UnitTestCase {

	function test_insert_appointment() {
		global $appointments;

		define( 'APP_USE_LEGACY_USERDATA_OVERWRITING', true );

		$worker_id = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id = appointments_insert_service( $service_args );
		$service = appointments_get_service( $service_id );

		$worker_args = array(
			'ID' => $worker_id,
			'services_provided' => array( $service_id )
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'user' => $user_id,
			'email' => 'tester@tester.com',
			'name' => 'Tester',
			'phone' => '667788',
			'address' => 'An address',
			'city' => 'Madrid',
			'service' => $service_id,
			'worker' => $worker_id,
			'price' => '90',
			'date' => 'December 18, 2024',
			'time' => '07:30',
			'note' => 'It\'s a note',
			'status' => 'paid',
			'location' => 5,
			'gcal_updated' => '2015-12-01',
			'gcal_ID' => 'test'
		);
		$app_id = appointments_insert_appointment( $args );

		$this->assertNotEmpty( $app_id );
		$app = appointments_get_appointment( $app_id );

		$this->assertEquals( $app_id, $app->ID );
		$this->assertEquals( $args['user'], $app->user );
		$this->assertEquals( $args['name'], $app->name );
		$this->assertEquals( $args['email'], $app->email );
		$this->assertEquals( $args['phone'], $app->phone );
		$this->assertEquals( $args['address'], $app->address );
		$this->assertEquals( $args['city'], $app->city );
		$this->assertEquals( $args['worker'], $app->worker );
		$this->assertEquals( $args['price'], $app->price );
		$this->assertEquals( $args['service'], $app->service );
		$this->assertEquals( '2024-12-18 07:30:00', $app->start );
		$this->assertEquals( '2024-12-18 09:00:00', $app->end );
		$this->assertEquals( $args['note'], $app->note );
		$this->assertEquals( $args['status'], $app->status );
		$this->assertEquals( $args['location'], $app->location );
		$this->assertEquals( $args['gcal_updated'] . ' 00:00:00', $app->gcal_updated );
		$this->assertEquals( $args['gcal_ID'], $app->gcal_ID );
		$this->assertNotEmpty( $app->created );

		$meta = get_user_meta( $app->user, 'app_name',  $app->name );
		$this->assertEquals( $app->name, $meta );

		// Wrong worker
		unset( $args['worker'] );
		$app_id = appointments_insert_appointment( $args );
		$app = appointments_get_appointment( $app_id );
		$this->assertEquals( 0, $app->worker );

		// Wrong email
		$args['service'] = $service_id;
		$args['email'] = 'wrong';
		$app_id = appointments_insert_appointment( $args );
		$app = appointments_get_appointment( $app_id );
		$this->assertEquals( '', $app->email );

		// Now tith timestamp
		$args = array(
			'user' => $user_id,
			'email' => 'tester@tester.com',
			'name' => 'Tester',
			'phone' => '667788',
			'address' => 'An address',
			'city' => 'Madrid',
			'service' => $service_id,
			'worker' => $worker_id,
			'price' => '90',
			'date' => 1728729000,
			'time' => '07:30',
			'note' => 'It\'s a note',
			'status' => 'paid',
			'location' => 5,
			'gcal_updated' => '2015-12-01',
			'gcal_ID' => 'test'
		);
		$app_id = appointments_insert_appointment( $args );

		$app = appointments_get_appointment( $app_id );
		$this->assertEquals( '2024-10-12 10:30:00', $app->start );

	}

	function test_get_appointment_by_gcal() {
		$worker_id = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id = appointments_insert_service( $service_args );
		$service = appointments_get_service( $service_id );

		$worker_args = array(
			'ID' => $worker_id,
			'services_provided' => array( $service_id )
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'user' => $user_id,
			'service' => $service_id,
			'worker' => $worker_id,
			'gcal_updated' => '2015-12-01 10:00:00',
			'gcal_ID' => 'test'
		);

		$app_id = appointments_insert_appointment( $args );
		$app = appointments_get_appointment_by_gcal_id( 'test' );

		$this->assertInstanceOf( 'Appointments_Appointment', $app );

		// Check cache
		$_app = wp_cache_get( $app->gcal_ID, 'app_appointments_by_gcal' );
		$this->assertTrue( $_app->ID == $app->ID );

		appointments_clear_appointment_cache( $app->gcal_ID );
		$_app = wp_cache_get( $app->gcal_ID, 'app_appointments_by_gcal' );
		$this->assertFalse( $_app );
	}

	function test_get_appointment() {
		global $appointments;

		$worker_id = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id = appointments_insert_service( $service_args );
		$service = appointments_get_service( $service_id );

		$worker_args = array(
			'ID' => $worker_id,
			'services_provided' => array( $service_id )
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'user' => $user_id,
			'email' => 'tester@tester.com',
			'name' => 'Tester',
			'phone' => '667788',
			'address' => 'An address',
			'city' => 'Madrid',
			'service' => $service_id,
			'worker' => $worker_id,
			'price' => '90',
			'date' => 'December 18, 2024',
			'time' => '07:30',
			'note' => 'It\'s a note',
			'status' => 'paid',
			'location' => 5,
			'gcal_updated' => '2015-12-01',
			'gcal_ID' => 'test'
		);
		$app_id = appointments_insert_appointment( $args );

		$appointment = appointments_get_appointment( $app_id );
		$this->assertInstanceOf( 'Appointments_Appointment', $appointment );

		// Test deprecated function
		$this->remove_deprecated_filters();
		$deprecated_app = $appointments->get_app( $app_id );
		$this->assertInstanceOf( 'Appointments_Appointment', $deprecated_app );

		$this->assertEquals( get_object_vars( $appointment ), get_object_vars( $deprecated_app ) );
		$this->add_deprecated_filters();


	}

	function test_update_appointment() {
		$worker_id = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$worker_id_2 = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id_2 = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id = appointments_insert_service( $service_args );
		$service = appointments_get_service( $service_id );

		$service_args = array(
			'name' => 'My Service 2',
			'duration' => 90
		);
		$service_id_2 = appointments_insert_service( $service_args );

		$worker_args = array(
			'ID' => $worker_id,
			'services_provided' => array( $service_id )
		);
		appointments_insert_worker( $worker_args );

		$worker_args = array(
			'ID' => $worker_id_2,
			'services_provided' => array( $service_id )
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'user' => $user_id,
			'email' => 'tester@tester.com',
			'name' => 'Tester',
			'phone' => '667788',
			'address' => 'An address',
			'city' => 'Madrid',
			'service' => $service_id,
			'worker' => $worker_id,
			'price' => '90',
			'date' => 'December 18, 2024',
			'time' => '07:30',
			'note' => 'It\'s a note',
			'status' => 'paid',
			'location' => 5,
			'gcal_updated' => '2015-12-01',
			'gcal_ID' => 'test'
		);
		$app_id = appointments_insert_appointment( $args );

		// Only change status
		$args = array(
			'status' => 'confirmed'
		);
		$result = appointments_update_appointment( $app_id, $args );
		$this->assertTrue( $result );
		$app = appointments_get_appointment( $app_id );
		$this->assertEquals( 'confirmed', $app->status );

		// Change all fields except status
		$args = array(
			'user' => $user_id_2,
			'email' => 'tester2@tester.com',
			'name' => 'Tester2',
			'phone' => '6677882',
			'address' => 'An address2',
			'city' => 'Madrid2',
			'service' => $service_id_2,
			'worker' => $worker_id_2,
			'price' => '120',
			'date' => 'November 18, 2024',
			'time' => '10:30',
			'note' => 'It\'s a note2',
			'location' => 6,
			'gcal_updated' => '2018-12-01',
			'gcal_ID' => 'test2'
		);
		$result = appointments_update_appointment( $app_id, $args );
		$this->assertTrue( $result );
		$app = appointments_get_appointment( $app_id );

		$this->assertEquals( $app_id, $app->ID );
		$this->assertEquals( $args['user'], $app->user );
		$this->assertEquals( $args['name'], $app->name );
		$this->assertEquals( $args['email'], $app->email );
		$this->assertEquals( $args['phone'], $app->phone );
		$this->assertEquals( $args['address'], $app->address );
		$this->assertEquals( $args['city'], $app->city );
		$this->assertEquals( $args['worker'], $app->worker );
		$this->assertEquals( $args['price'], $app->price );
		$this->assertEquals( $args['service'], $app->service );
		$this->assertEquals( '2024-11-18 10:30:00', $app->start );
		$this->assertEquals( '2024-11-18 12:00:00', $app->end );
		$this->assertEquals( $args['note'], $app->note );
		$this->assertEquals( 'confirmed', $app->status );
		$this->assertEquals( $args['location'], $app->location );
		$this->assertEquals( $args['gcal_updated'] . ' 00:00:00', $app->gcal_updated );
		$this->assertEquals( $args['gcal_ID'], $app->gcal_ID );
		$this->assertNotEmpty( $app->created );


		// Wrong service
		$args = array(
			'service' => 8888
		);
		appointments_update_appointment( $app_id, $args );
		$app = appointments_get_appointment( $app_id );
		$this->assertEquals( 0, $app->service );

		// Wrong worker
		$args = array(
			'worker' => 8888
		);
		$result = appointments_update_appointment( $app_id, $args );
		$this->assertTrue( $result );
		$app = appointments_get_appointment( $app_id );
		$this->assertEquals( 0, $app->worker );

		// Only location
		$result = appointments_update_appointment( $app_id, array( 'location' => 20 ) );
		$this->assertTrue( $result );
		$app = appointments_get_appointment( $app_id );
		$this->assertEquals( 20, $app->location );
	}

	function test_delete_appointment() {
		$worker_id = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id = appointments_insert_service( $service_args );

		$worker_args = array(
			'ID' => $worker_id,
			'services_provided' => array( $service_id )
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'user' => $user_id,
			'email' => 'tester@tester.com',
			'name' => 'Tester',
			'phone' => '667788',
			'address' => 'An address',
			'city' => 'Madrid',
			'service' => $service_id,
			'worker' => $worker_id,
			'price' => '90',
			'date' => 'December 18, 2024',
			'time' => '07:30',
			'note' => 'It\'s a note',
			'status' => 'paid',
			'location' => 5,
		);
		$app_id = appointments_insert_appointment( $args );

		$app = appointments_get_appointment( $app_id );
		appointments_delete_appointment( $app_id );
		$app = appointments_get_appointment( $app_id );
		$this->assertFalse( $app );
	}

	function test_update_appointment_status() {
		$worker_id = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id = appointments_insert_service( $service_args );

		$worker_args = array(
			'ID' => $worker_id,
			'services_provided' => array( $service_id )
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'user' => $user_id,
			'email' => 'tester@tester.com',
			'name' => 'Tester',
			'phone' => '667788',
			'address' => 'An address',
			'city' => 'Madrid',
			'service' => $service_id,
			'worker' => $worker_id,
			'price' => '90',
			'date' => 'December 18, 2024',
			'time' => '07:30',
			'note' => 'It\'s a note',
			'status' => 'paid',
			'location' => 5,
		);
		$app_id = appointments_insert_appointment( $args );

		$result = appointments_update_appointment_status( $app_id, 'confirmed' );
		$this->assertTrue( $result );

		$appointment = appointments_get_appointment( $app_id );
		$this->assertEquals( $appointment->status, 'confirmed' );

		// Same status, no changes
		$result = appointments_update_appointment_status( $app_id, 'confirmed' );
		$this->assertFalse( $result );

		// Wrong status name
		$result = appointments_update_appointment_status( $app_id, 'fake-status' );
		$this->assertFalse( $result );

		// Test deprecated function
		global $appointments;
		$this->remove_deprecated_filters();
		$result = $appointments->change_status( 'paid', $app_id );
		$this->add_deprecated_filters();

		$this->assertTrue( $result );

		$appointment = appointments_get_appointment( $app_id );
		$this->assertEquals( $appointment->status, 'paid' );

	}

	function test_get_statuses() {
		global $appointments;

		// Test deprecated function
		$statuses = appointments_get_statuses();

		$this->remove_deprecated_filters();
		$deprecated_statuses = $appointments->get_statuses();
		$this->add_deprecated_filters();

		$this->assertEquals( $statuses, $deprecated_statuses );

	}

	function test_get_user_appointments() {
		$worker_id = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id = appointments_insert_service( $service_args );

		$worker_args = array(
			'ID' => $worker_id,
			'services_provided' => array( $service_id )
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'user' => $user_id,
			'email' => 'tester@tester.com',
			'name' => 'Tester',
			'phone' => '667788',
			'address' => 'An address',
			'city' => 'Madrid',
			'service' => $service_id,
			'worker' => $worker_id,
			'price' => '90',
			'date' => 'December 18, 2024',
			'time' => '07:30',
			'note' => 'It\'s a note',
			'status' => 'paid',
			'location' => 5,
		);
		$app_id_1 = appointments_insert_appointment( $args );

		$args = array(
			'user' => $user_id,
			'email' => 'tester@tester.com',
			'name' => 'Tester',
			'phone' => '667788',
			'address' => 'An address',
			'city' => 'Madrid',
			'service' => $service_id,
			'worker' => $worker_id,
			'price' => '90',
			'date' => 'December 19, 2024',
			'time' => '07:30',
			'note' => 'It\'s a note',
			'status' => 'paid',
			'location' => 5,
		);
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'user' => $user_id,
			'email' => 'tester@tester.com',
			'name' => 'Tester',
			'phone' => '667788',
			'address' => 'An address',
			'city' => 'Madrid',
			'service' => $service_id,
			'worker' => $worker_id,
			'price' => '90',
			'date' => 'December 19, 2024',
			'time' => '07:30',
			'note' => 'It\'s a note',
			'status' => 'pending', // Not confirmed
			'location' => 5,
		);
		$app_id_3 = appointments_insert_appointment( $args );

		$apps = wp_list_pluck( appointments_get_user_appointments( $user_id ), 'ID' );
		sort( $apps );
		$this->assertEquals( $apps, array( $app_id_1, $app_id_2 ) );

	}


	function test_delete_worker_with_appointments() {
		global $appointments;

		$worker_id_1 = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service'
		);
		$service_id_1 = appointments_insert_service( $service_args );

		$worker_args = array(
			'ID' => $worker_id_1,
			'services_provided' => array( $service_id_1 )
		);
		appointments_insert_worker( $worker_args );


		$args = array(
			'user' => $user_id,
			'service' => $service_id_1,
			'worker' => $worker_id_1,
		);
		$app_id_1 = appointments_insert_appointment( $args );

		$args = array(
			'user' => $user_id,
			'service' => $service_id_1,
			'worker' => $worker_id_1,
		);
		$app_id_2 = appointments_insert_appointment( $args );

		$this->assertEquals( appointments_get_appointment( $app_id_1 )->worker, $worker_id_1 );
		$this->assertEquals( appointments_get_appointment( $app_id_2 )->worker, $worker_id_1 );

		appointments_delete_worker( $worker_id_1 );

		$this->assertEquals( appointments_get_appointment( $app_id_1 )->worker, 0 );
		$this->assertEquals( appointments_get_appointment( $app_id_2 )->worker, 0 );

		// Same effect than:
		$worker_id_1 = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$worker_args = array(
			'ID' => $worker_id_1,
			'services_provided' => array( $service_id_1 )
		);
		appointments_insert_worker( $worker_args );

		appointments_update_appointment( $app_id_1, array( 'worker' => $worker_id_1 ) );
		appointments_update_appointment( $app_id_2, array( 'worker' => $worker_id_1 ) );

		$this->assertEquals( appointments_get_appointment( $app_id_1 )->worker, $worker_id_1 );
		$this->assertEquals( appointments_get_appointment( $app_id_2 )->worker, $worker_id_1 );

		$appointments->delete_user( $worker_id_1 );

		$this->assertEquals( appointments_get_appointment( $app_id_1 )->worker, 0 );
		$this->assertEquals( appointments_get_appointment( $app_id_2 )->worker, 0 );
	}

	function test_appointments_duration() {
		$worker_id = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 40
		);
		$service_id = appointments_insert_service( $service_args );

		$worker_args = array(
			'ID' => $worker_id,
			'services_provided' => array( $service_id ),
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'service' => $service_id,
		);
		$app_id = appointments_insert_appointment( $args );
		$app = appointments_get_appointment( $app_id );

		$start = $app->start;
		$end = $app->end;
		$this->assertEquals( strtotime( $start ) + ( 40 * 60 ), strtotime( $end ) );

		$args = array(
			'service' => $service_id,
			'duration' => 50
		);
		$app_id = appointments_insert_appointment( $args );
		$app = appointments_get_appointment( $app_id );

		$start = $app->start;
		$end = $app->end;
		$this->assertEquals( strtotime( $start ) + ( 50 * 60 ), strtotime( $end ) );

		global $appointments;
		$args = array();
		$app_id = appointments_insert_appointment( $args );
		$app = appointments_get_appointment( $app_id );

		$start = $app->start;
		$end = $app->end;
		$this->assertEquals( strtotime( $start ) + ( $appointments->get_min_time() * 60 ), strtotime( $end ) );

	}

	function test_appointments_cache() {
		$worker_id_1 = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id_1 = appointments_insert_service( $service_args );


		$worker_args = array(
			'ID' => $worker_id_1,
			'services_provided' => array( $service_id_1 )
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'paid',
		);
		$app_id_1 = appointments_insert_appointment( $args );
		$app = appointments_get_appointment( $app_id_1 );
		$cached_data = wp_cache_get( $app_id_1, 'app_appointments' );

		$this->assertEquals( new Appointments_Appointment( $cached_data ), $app );
	}

	/**
	 * @group emails
	 */
	function test_get_appointment_emails() {
		$worker_args = $this->factory->user->generate_args();
		$worker_args['user_email'] = 'worker@email.dev';
		$worker_id = $this->factory->user->create_object( $worker_args );

		$user_args = $this->factory->user->generate_args();
		$user_args['user_email'] = 'user@email.dev';
		$user_id = $this->factory->user->create_object( $user_args );


		$worker_args = array(
			'ID' => $worker_id
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'user' => $user_id,
			'worker' => $worker_id,
			'status' => 'paid',
			'name' => 'Cname',
			'created' => '2015-11-11 10:00:00'
		);
		$app_id = appointments_insert_appointment( $args );

		$app = appointments_get_appointment( $app_id );

		$appointments = appointments();
		$this->assertEquals( 'user@email.dev', $app->get_customer_email() );
		$this->assertEquals( 'worker@email.dev', $appointments->get_worker_email( $app->worker ) );

		// Unassigned customer appointment
		$args = array(
			'email' => 'customer@email.dev',
			'worker' => $worker_id,
			'status' => 'paid',
			'name' => 'Cname',
			'created' => '2015-11-11 10:00:00'
		);
		$app_id = appointments_insert_appointment( $args );

		$app = appointments_get_appointment( $app_id );

		$appointments = appointments();
		$this->assertEquals( 'customer@email.dev', $app->get_customer_email() );
		$this->assertEquals( 'worker@email.dev', $appointments->get_worker_email( $app->worker ) );

		// Email overriden
		$args = array(
			'email' => 'customer@email.dev', // This email should override the user one
			'user' => $user_id,
			'worker' => $worker_id,
			'status' => 'paid',
			'name' => 'Cname',
			'created' => '2015-11-11 10:00:00'
		);
		$app_id = appointments_insert_appointment( $args );

		$app = appointments_get_appointment( $app_id );

		$appointments = appointments();
		$this->assertEquals( 'customer@email.dev', $app->get_customer_email() );
		$this->assertEquals( 'worker@email.dev', $appointments->get_worker_email( $app->worker ) );
	}


}

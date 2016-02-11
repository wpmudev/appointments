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

		// Wrong service
		$args['worker'] = $worker_id;
		unset( $args['service'] );
		$app_id = appointments_insert_appointment( $args );
		$this->assertFalse( $app_id );

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
		$result = appointments_update_appointment( $app_id, $args );
		$this->assertFalse( $result );
		$app = appointments_get_appointment( $app_id );
		$this->assertEquals( $service_id_2, $app->service );

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

	/**
	 * @group get
	 */
	function test_get_appointments_filtered_by_services() {
		global $appointments;
		$worker_id_1 = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$worker_id_2 = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$worker_id_3 = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id_1 = appointments_insert_service( $service_args );

		$service_args = array(
			'name' => 'My Service 2',
			'duration' => 90
		);
		$service_id_2 = appointments_insert_service( $service_args );

		$worker_args = array(
			'ID' => $worker_id_1,
			'services_provided' => array( $service_id_1 )
		);
		appointments_insert_worker( $worker_args );

		$worker_args = array(
			'ID' => $worker_id_2,
			'services_provided' => array( $service_id_1 )
		);
		appointments_insert_worker( $worker_args );

		$worker_args = array(
			'ID' => $worker_id_3,
			'services_provided' => array( $service_id_2 )
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'user' => $user_id,
			'email' => 'tester@tester.com',
			'name' => 'Tester',
			'phone' => '667788',
			'address' => 'An address',
			'city' => 'Madrid',
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'price' => '90',
			'date' => 1734507000, // 2024-12-18 07:30
			'time' => '07:30',
			'note' => 'It\'s a note',
			'status' => 'paid',
			'location' => 5,
		);
		$week_app_1 = date( 'W', 1734507000 );
		$app_id_1 = appointments_insert_appointment( $args );

		$args = array(
			'user' => $user_id,
			'email' => 'tester@tester.com',
			'name' => 'Tester',
			'phone' => '667788',
			'address' => 'An address',
			'city' => 'Madrid',
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'price' => '90',
			'date' => 1728729000, // 2024-10-12 10:30
			'time' => '07:30',
			'note' => 'It\'s a note',
			'status' => 'paid',
			'location' => 5,
		);
		$week_app_2 = date( 'W', 1728729000 );
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'user' => $user_id,
			'email' => 'tester@tester.com',
			'name' => 'Tester',
			'phone' => '667788',
			'address' => 'An address',
			'city' => 'Madrid',
			'service' => $service_id_1,
			'worker' => $worker_id_2,
			'price' => '90',
			'date' => 1728729000, // 2024-10-12 10:30
			'time' => '07:30',
			'note' => 'It\'s a note',
			'status' => 'paid',
			'location' => 5,
		);
		$week_app_3 = date( 'W', 1728729000 );
		$app_id_3 = appointments_insert_appointment( $args );

		$args = array(
			'user' => $user_id,
			'email' => 'tester@tester.com',
			'name' => 'Tester',
			'phone' => '667788',
			'address' => 'An address',
			'city' => 'Madrid',
			'service' => $service_id_1,
			'price' => '90',
			'date' => 1728729000, // 2024-10-12 10:30
			'time' => '07:30',
			'note' => 'It\'s a note',
			'status' => 'paid',
			'location' => 5,
		);
		$week_app_4 = date( 'W', 1728729000 );
		$app_id_4 = appointments_insert_appointment( $args );

		$args = array(
			'user' => $user_id,
			'email' => 'tester@tester.com',
			'name' => 'Tester',
			'phone' => '667788',
			'address' => 'An address',
			'city' => 'Madrid',
			'worker' => $worker_id_3,
			'service' => $service_id_2,
			'price' => '90',
			'date' => 1728729000, // 2024-10-12 10:30
			'time' => '07:30',
			'note' => 'It\'s a note',
			'status' => 'paid',
			'location' => 5,
		);
		$week_app_5 = date( 'W', 1728729000 );
		$app_id_5 = appointments_insert_appointment( $args );

		global $wpdb;
		$table = appointments_get_table( 'appointments' );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1
		);
		$apps = appointments_get_appointments_filtered_by_services( $args );
		$this->assertCount( 2, $apps );

		// Test deprecated function
		$this->remove_deprecated_filters();
		$deprecated_apps = $appointments->get_reserve_apps( 0, $service_id_1, $worker_id_1 );
		$this->add_deprecated_filters();
		$this->assertCount( 2, $deprecated_apps );

		$args = array(
			'worker' => $worker_id_1
		);
		$apps = appointments_get_appointments_filtered_by_services( $args );
		$this->assertCount( 0, $apps );

		// Test deprecated function
		$this->remove_deprecated_filters();
		$deprecated_apps = $appointments->get_reserve_apps( 0, 0, $worker_id_1 );
		$this->add_deprecated_filters();
		$this->assertCount( 0, $deprecated_apps );

		$apps = appointments_get_appointments_filtered_by_services();
		$this->assertCount( 0, $apps );

		$args = array(
			'worker' => $worker_id_2,
			'service' => $service_id_1
		);
		$apps = appointments_get_appointments_filtered_by_services( $args );
		$this->assertCount( 1, $apps );

		// Test deprecated function
		$this->remove_deprecated_filters();
		$deprecated_apps = $appointments->get_reserve_apps( 0, $service_id_1, $worker_id_2 );
		$this->add_deprecated_filters();
		$this->assertCount( 1, $deprecated_apps );

		$args = array(
			'worker' => $worker_id_3,
			'service' => $service_id_2
		);
		$apps = appointments_get_appointments_filtered_by_services( $args );
		$this->assertCount( 1, $apps );

		// Test deprecated function
		$this->remove_deprecated_filters();
		$deprecated_apps = $appointments->get_reserve_apps( 0, $service_id_2, $worker_id_3 );
		$this->add_deprecated_filters();
		$this->assertCount( 1, $deprecated_apps );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'week' => $week_app_1
		);
		$apps = appointments_get_appointments_filtered_by_services( $args );
		$this->assertCount( 1, $apps );

		// Test deprecated function
		$this->remove_deprecated_filters();
		$deprecated_apps = $appointments->get_reserve_apps( 0, $service_id_1, $worker_id_1, $week_app_1 );
		$this->add_deprecated_filters();
		$this->assertCount( 1, $deprecated_apps );

		$args = array(
			'service' => $service_id_1
		);
		$apps = appointments_get_appointments_filtered_by_services( $args );
		$this->assertCount( 1, $apps );

		// Test deprecated function
		$this->remove_deprecated_filters();
		$deprecated_apps = $appointments->get_reserve_apps_by_service( 0, $service_id_1 );
		$this->add_deprecated_filters();
		$this->assertCount( 1, $deprecated_apps );

	}

	function test_get_appointments() {
		$worker_id_1 = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$worker_id_2 = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service'
		);
		$service_id_1 = appointments_insert_service( $service_args );

		$service_args = array(
			'name' => 'My Service 2'
		);
		$service_id_2 = appointments_insert_service( $service_args );

		$worker_args = array(
			'ID' => $worker_id_1,
			'services_provided' => array( $service_id_1 )
		);
		appointments_insert_worker( $worker_args );

		$worker_args = array(
			'ID' => $worker_id_2,
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
			'service' => $service_id_2,
			'worker' => $worker_id_1,
		);
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'user' => $user_id,
			'service' => $service_id_2,
			'worker' => $worker_id_2,
		);
		$app_id_3 = appointments_insert_appointment( $args );

		$apps = appointments_get_appointments();
		$this->assertCount( 3, $apps );

		$apps = appointments_get_appointments( array( 'worker' => $worker_id_1 ) );
		$this->assertCount( 2, $apps );

		$apps = appointments_get_appointments( array( 'worker' => $worker_id_2 ) );
		$this->assertCount( 1, $apps );

		// Is is get from cache
		$apps = appointments_get_appointments( array( 'worker' => $worker_id_2 ) );
		$this->assertCount( 1, $apps );

		// Test the cache
		$this->assertCount( 3, wp_cache_get( 'app_get_appointments' ) );
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

		appointments_update_appointment( $app_id_1, array( 'worker' => $worker_id_1 ) );
		appointments_update_appointment( $app_id_2, array( 'worker' => $worker_id_1 ) );

		$this->assertEquals( appointments_get_appointment( $app_id_1 )->worker, $worker_id_1 );
		$this->assertEquals( appointments_get_appointment( $app_id_2 )->worker, $worker_id_1 );

		$appointments->delete_user( $worker_id_1 );

		$this->assertEquals( appointments_get_appointment( $app_id_1 )->worker, 0 );
		$this->assertEquals( appointments_get_appointment( $app_id_2 )->worker, 0 );
	}

	function test_get_client_name() {
		global $appointments;
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
			'user' => $user_id,
			'email' => 'tester@tester.com',
			'name' => 'Tester',
			'phone' => '667788',
			'address' => 'An address',
			'city' => 'Madrid',
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'price' => '90',
			'date' => 1734507000, // 2024-12-18 07:30
			'time' => '07:30',
			'note' => 'It\'s a note',
			'status' => 'paid',
			'location' => 5,
		);
		$app_id_1 = appointments_insert_appointment( $args );

		$name = $appointments->get_client_name( $app_id_1 );
		$this->assertContains( 'Tester', $name );

	}


	function test_get_expired_appointments() {
		global $appointments;

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
			'status' => 'confirmed',
			'date' => strtotime( '2016-01-01 10:00:00' ) // Past date
		);
		$app_id_1 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'paid',
			'date' => strtotime( '2016-01-01 11:00:00' ) // Past date
		);
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'completed',
			'date' => strtotime( '2016-01-01 11:00:00' ) // Past date but completed
		);
		$app_id_3 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'paid',
			'date' => strtotime( '2030-01-01 11:00:00' ) // Future date
		);
		$app_id_4 = appointments_insert_appointment( $args );

		$expired = appointments_get_expired_appointments();
		$this->assertCount( 2, $expired );

		$app_ids = wp_list_pluck( $expired, 'ID' );
		sort( $app_ids );
		$this->assertEquals( array( $app_id_1, $app_id_2 ), $app_ids );

		// Check the cache
		$cached_data_app_1 = wp_cache_get( $app_id_1, 'app_appointments' );
		$this->assertEquals( new Appointments_Appointment( $cached_data_app_1 ), $expired[0] );

		// Now insert a pending appointment that is too old
		// And another one that is not too old
		$current_time = current_time( 'timestamp' );
		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'pending',
			'date' => strtotime( '2030-01-01 11:00:00' ), // Future date
			'created' => date( 'Y-m-d H:i:s', $current_time - 10 ) // Only 10 seconds old
		);
		$app_id_5 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'pending',
			'date' => strtotime( '2030-01-01 11:00:00' ), // Future date
			'created' => date( 'Y-m-d H:i:s', $current_time - ( 80 * 60 ) ) // 80 minutes old
		);
		$app_id_6 = appointments_insert_appointment( $args );

		// Search also for pending with more than 60 minutes old
		$expired = appointments_get_expired_appointments( 60 * 60 );
		$this->assertCount( 3, $expired );

		$app_ids = wp_list_pluck( $expired, 'ID' );
		sort( $app_ids );
		$this->assertEquals( array( $app_id_1, $app_id_2, $app_id_6 ), $app_ids );

		// Test the old function
		$appointments->options["clear_time"] = 60;
		$appointments->local_time = $current_time;
		$appointments->remove_appointments();

		$expired = appointments_get_expired_appointments( 60 * 60 );
		$this->assertEmpty( $expired );
		// Check that we haven't deleted others
		$this->assertInstanceOf( 'Appointments_Appointment', appointments_get_appointment( $app_id_3 ) );

		$this->assertEquals( 'completed', appointments_get_appointment( $app_id_1 )->status );
		$this->assertEquals( 'completed', appointments_get_appointment( $app_id_2 )->status );
		$this->assertEquals( 'removed', appointments_get_appointment( $app_id_6 )->status );

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

	function test_count_appointments() {
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
			'status' => 'confirmed',
		);
		$app_id_1 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'paid',
		);
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'completed',
		);
		$app_id_3 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'paid',
		);
		$app_id_4 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'reserved',
		);
		$app_id_5 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'removed',
		);
		$app_id_6 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'completed',
		);
		$app_id_7 = appointments_insert_appointment( $args );

		$result = appointments_count_appointments();
		$this->assertEquals( 0, $result['pending'] );
		$this->assertEquals( 1, $result['confirmed'] );
		$this->assertEquals( 2, $result['paid'] );
		$this->assertEquals( 2, $result['completed'] );
		$this->assertEquals( 1, $result['reserved'] );
		$this->assertEquals( 1, $result['removed'] );

		appointments_delete_appointment( $app_id_6 );
		$result = appointments_count_appointments();
		$this->assertEquals( 0, $result['removed'] );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'pending',
		);
		$app_id_8 = appointments_insert_appointment( $args );
		$result = appointments_count_appointments();
		$this->assertEquals( 1, $result['pending'] );
	}

	function test_get_sent_worker_hours() {
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
			'status' => 'confirmed',
		);
		$app_id_1 = appointments_insert_appointment( $args );
		appointments_update_appointment( $app_id_1, array( 'sent_worker' => ':1:2:4:' ) );
		$app = appointments_get_appointment( $app_id_1 );
		$this->assertEquals( $app->get_sent_worker_hours(), array( 1, 2, 4 ) );

		appointments_update_appointment( $app_id_1, array( 'sent_worker' => array( 1, 2, 4, 6 ) ) );
		$app = appointments_get_appointment( $app_id_1 );
		$this->assertEquals( $app->get_sent_worker_hours(), array( 1, 2, 4, 6 ) );
	}

	function test_get_sent_user_hours() {
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
			'status' => 'confirmed',
		);
		$app_id_1 = appointments_insert_appointment( $args );
		appointments_update_appointment( $app_id_1, array( 'sent' => ':1:2:4:' ) );
		$app = appointments_get_appointment( $app_id_1 );
		$this->assertEquals( $app->get_sent_user_hours(), array( 1, 2, 4 ) );

		appointments_update_appointment( $app_id_1, array( 'sent' => array( 1, 2, 4, 6 ) ) );
		$app = appointments_get_appointment( $app_id_1 );
		$this->assertEquals( $app->get_sent_user_hours(), array( 1, 2, 4, 6 ) );
	}

	/** OLD QUERIES **/
	function test_update_appointment_sent_worker_old() {
		global $wpdb;

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
			'status' => 'confirmed',
		);
		$app_id_1 = appointments_insert_appointment( $args );

		// See Appointments::send_reminder_worker()
		$hours = array( 4, 2, 1 );
		foreach ( $hours as $hour ) {
			$r = appointments_get_appointment( $app_id_1 );
			$wpdb->update(
				appointments_get_table( 'appointments' ),
				array('sent_worker' => rtrim($r->sent_worker, ":") . ":" . trim($hour) . ":"),
				array('ID' => $r->ID),
				array('%s')
			);
			appointments_clear_appointment_cache();
		}

		// It must result in the same thing that:
		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'confirmed',
		);
		$app_id_2 = appointments_insert_appointment( $args );
		appointments_update_appointment( $app_id_2, array( 'sent_worker' => $hours ) );

		$app_1 = appointments_get_appointment( $app_id_1 );
		$app_2 = appointments_get_appointment( $app_id_2 );

		$this->assertEquals( $app_1->sent_worker, ':4:2:1:' );
		$this->assertEquals( $app_1->get_sent_worker_hours(), array( 4,2,1 ) );
		$this->assertEquals( $app_1->sent_worker, $app_2->sent_worker );
		$this->assertEquals( $app_1->get_sent_worker_hours(), $app_2->get_sent_worker_hours() );

	}

	function test_update_appointment_sent_user_old() {
		global $wpdb;

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
			'status' => 'confirmed',
		);
		$app_id_1 = appointments_insert_appointment( $args );

		// See Appointments::send_reminder()
		$hours = array( 4, 2, 1 );
		foreach ( $hours as $hour ) {
			$r = appointments_get_appointment( $app_id_1 );
			$wpdb->update(
				appointments_get_table( 'appointments' ),
				array('sent' => rtrim($r->sent, ":") . ":" . trim($hour) . ":"),
				array('ID' => $r->ID),
				array('%s')
			);
			appointments_clear_appointment_cache();
		}

		// It must result in the same thing that:
		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'confirmed',
		);
		$app_id_2 = appointments_insert_appointment( $args );
		appointments_update_appointment( $app_id_2, array( 'sent' => $hours ) );

		$app_1 = appointments_get_appointment( $app_id_1 );
		$app_2 = appointments_get_appointment( $app_id_2 );

		$this->assertEquals( $app_1->sent, ':4:2:1:' );
		$this->assertEquals( $app_1->get_sent_user_hours(), array( 4,2,1 ) );
		$this->assertEquals( $app_1->sent, $app_2->sent );
		$this->assertEquals( $app_1->get_sent_user_hours(), $app_2->get_sent_user_hours() );

	}

}
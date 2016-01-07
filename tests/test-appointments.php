<?php


/**
 * @group appointments
 */
class App_Appointments_Test extends App_UnitTestCase {

	function test_insert_appointment() {
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

}
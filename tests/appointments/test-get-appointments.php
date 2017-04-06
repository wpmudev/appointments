<?php

/**
 * @group appointments
 * @group get_appointments
 */

class App_Get_Appointments_Test extends App_UnitTestCase {

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

		$args = array(
			'worker' => $worker_id_1
		);
		$apps = appointments_get_appointments_filtered_by_services( $args );
		$this->assertCount( 0, $apps );

		$apps = appointments_get_appointments_filtered_by_services();
		$this->assertCount( 0, $apps );

		$args = array(
			'worker' => $worker_id_2,
			'service' => $service_id_1
		);
		$apps = appointments_get_appointments_filtered_by_services( $args );
		$this->assertCount( 1, $apps );

		$args = array(
			'worker' => $worker_id_3,
			'service' => $service_id_2
		);
		$apps = appointments_get_appointments_filtered_by_services( $args );
		$this->assertCount( 1, $apps );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'week' => $week_app_1
		);
		$apps = appointments_get_appointments_filtered_by_services( $args );
		$this->assertCount( 1, $apps );

		$args = array(
			'service' => $service_id_1
		);
		$apps = appointments_get_appointments_filtered_by_services( $args );
		$this->assertCount( 1, $apps );
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
		$current_time = current_time( 'timestamp' );
		$app_dates    = array(
			array( 'Past and pending', '2016-01-01 11:00:00', 'pending' ), // Past and pending
			array( 'Future and pending', '2030-01-01 11:00:00', 'pending' ), // Future and pending
			array( 'Past and confirmed', '2016-01-01 11:00:00', 'confirmed' ), // Past and confirmed
			array( 'Future and confirmed', '2030-01-01 11:00:00', 'confirmed' ), // Future and confirmed
			array( 'Past and paid', '2016-01-01 11:00:00', 'paid' ), // Past and paid
			array( 'Future and paid', '2030-01-01 11:00:00', 'paid' ), // Future and paid
			array( 'Past and completed', '2016-01-01 11:00:00', 'completed' ), // Past and completed
			array( 'Future and completed', '2030-01-01 11:00:00', 'completed' ), // Future and completed
			array( 'Past and reserved by GCal', '2016-01-01 11:00:00', 'reserved' ), // Past and reserved by GCal
			array( 'Future and reserved by GCal', '2030-01-01 11:00:00', 'reserved' ), // Future and reserved by GCal
			array( 'Past and removed', '2016-01-01 11:00:00', 'removed' ), // Past and removed
			array( 'Future and removed', '2030-01-01 11:00:00', 'removed' ), // Future and removed
			array( 'Future and pending, created 2 days ago', '2030-01-01 11:00:00', 'pending', $current_time - ( 2 * 24 * 60 * 60 ) ), // Future and pending, created 2 days ago
			array( 'Future and pending, created 10 seconds ago', '2030-01-01 11:00:00', 'pending', $current_time - 10 ), // Future and pending, created 10 seconds ago
		);


		$app_ids = array();

		foreach ( $app_dates as $app_date ) {
			$app_args           = $this->factory->appointment->generate_args();
			$app_args['status'] = $app_date[2];
			$app_args['name'] = $app_date[0];
			$app_args['date']   = strtotime( $app_date[1] );
			if ( isset( $app_date[3] ) ) {
				$app_args['created'] = date( 'Y-m-d H:i:s', $app_date[3] );
			}
			$app_ids[ $this->factory->appointment->create_object( $app_args ) ] = $app_date;
		}

		$options               = appointments_get_options();
		$options['clear_time'] = 0;
		appointments_update_options( $options );

		// Should get expired
		$expired = appointments_get_expired_appointments();
		$this->assertCount( 4, $expired );

		// This names should not be on the list
		$shouldnt_be = array(
			'Future and pending',
			'Future and confirmed',
			'Future and paid',
			'Future and completed',
			'Past and completed',
			'Future and reserved by GCal',
			'Past and removed',
			'Future and removed',
			'Future and pending, created 2 days ago',
			'Future and pending, created 10 seconds ago'
		);
		// This names should be on the list
		$should_be = array(
			'Past and pending',
			'Past and confirmed',
			'Past and paid',
			'Past and reserved by GCal'
		);
		$names = wp_list_pluck( $expired, 'name' );
		foreach ( $expired as $exp ) {
			$this->assertFalse( in_array( $exp->name, $shouldnt_be ) );
			$this->asserttrue( in_array( $exp->name, $should_be ) );
		}
	}


	function test_get_expired_appointments_with_pending_seconds() {
		$current_time = current_time( 'timestamp' );
		$app_dates    = array(
			array( 'Past and pending', '2016-01-01 11:00:00', 'pending' ), // Past and pending
			array( 'Future and pending', '2030-01-01 11:00:00', 'pending' ), // Future and pending
			array( 'Past and confirmed', '2016-01-01 11:00:00', 'confirmed' ), // Past and confirmed
			array( 'Future and confirmed', '2030-01-01 11:00:00', 'confirmed' ), // Future and confirmed
			array( 'Past and paid', '2016-01-01 11:00:00', 'paid' ), // Past and paid
			array( 'Future and paid', '2030-01-01 11:00:00', 'paid' ), // Future and paid
			array( 'Past and completed', '2016-01-01 11:00:00', 'completed' ), // Past and completed
			array( 'Future and completed', '2030-01-01 11:00:00', 'completed' ), // Future and completed
			array( 'Past and reserved by GCal', '2016-01-01 11:00:00', 'reserved' ), // Past and reserved by GCal
			array( 'Future and reserved by GCal', '2030-01-01 11:00:00', 'reserved' ), // Future and reserved by GCal
			array( 'Past and removed', '2016-01-01 11:00:00', 'removed' ), // Past and removed
			array( 'Future and removed', '2030-01-01 11:00:00', 'removed' ), // Future and removed
			array( 'Future and pending, created 2 days ago', '2030-01-01 11:00:00', 'pending', $current_time - ( 2 * 24 * 60 * 60 ) ), // Future and pending, created 2 days ago
			array( 'Future and pending, created 10 seconds ago', '2030-01-01 11:00:00', 'pending', $current_time - 10 ), // Future and pending, created 10 seconds ago
		);


		$app_ids = array();

		foreach ( $app_dates as $app_date ) {
			$app_args           = $this->factory->appointment->generate_args();
			$app_args['status'] = $app_date[2];
			$app_args['name'] = $app_date[0];
			$app_args['date']   = strtotime( $app_date[1] );
			if ( isset( $app_date[3] ) ) {
				$app_args['created'] = date( 'Y-m-d H:i:s', $app_date[3] );
			}
			$app_ids[ $this->factory->appointment->create_object( $app_args ) ] = $app_date;
		}

		$options               = appointments_get_options();
		$options['clear_time'] = 60;
		appointments_update_options( $options );

		// Should get expired + pendings that have been created more than 60 minutes ago
		$expired = appointments_get_expired_appointments( $options['clear_time'] * 60 );
		$this->assertCount( 5, $expired );

		// This names should not be on the list
		$shouldnt_be = array(
			'Future and pending',
			'Future and confirmed',
			'Future and paid',
			'Future and completed',
			'Past and completed',
			'Future and reserved by GCal',
			'Past and removed',
			'Future and removed',
			'Future and pending, created 10 seconds ago'
		);
		// This names should be on the list
		$should_be = array(
			'Past and pending',
			'Past and confirmed',
			'Past and paid',
			'Past and reserved by GCal',
			'Future and pending, created 2 days ago',
		);
		$names = wp_list_pluck( $expired, 'name' );
		foreach ( $expired as $exp ) {
			$this->assertFalse( in_array( $exp->name, $shouldnt_be ) );
			$this->asserttrue( in_array( $exp->name, $should_be ) );
		}

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

	function test_get_appointments_by_app_ids() {
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

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'pending',
		);
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'completed',
		);
		$app_id_3 = appointments_insert_appointment( $args );

		$args = array(
			'app_id' => array( $app_id_3 )
		);
		$apps = appointments_get_appointments( $args );
		$this->assertCount( 1, $apps );

		$args = array(
			'app_id' => array( $app_id_3, $app_id_2 )
		);
		$apps = appointments_get_appointments( $args );
		$this->assertCount( 2, $apps );

		$args = array(
			'app_id' => array( 100 )
		);
		$apps = appointments_get_appointments( $args );
		$this->assertCount( 0, $apps );
	}

	function test_get_appointments_by_status() {
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

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'pending',
		);
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'completed',
		);
		$app_id_3 = appointments_insert_appointment( $args );

		$args = array(
			'status' => 'completed'
		);
		$apps = appointments_get_appointments( $args );
		$this->assertCount( 1, $apps );

		$args = array(
			'status' => array( 'completed', 'paid' )
		);
		$apps = appointments_get_appointments( $args );
		$this->assertCount( 2, $apps );
	}

	function test_get_appointments_by_date_query() {
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
			'status' => 'pending',
			'created' => '2015-11-11 10:00:00'
		);
		$app_id_1 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'pending',
			'created' => '2015-12-30 11:00:00'
		);
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'pending',
			'created' => '2016-02-11 23:00:00'
		);
		$app_id_3 = appointments_insert_appointment( $args );

		$args = array(
			'date_query' => array(
				array(
					'field' => 'created',
					'compare' => '>',
					'value' => '2015-12-01 10:00:00'
				)
			)
		);
		$apps = appointments_get_appointments( $args );
		$this->assertCount( 2, $apps );

		$args = array(
			'date_query' => array(
				array(
					'field' => 'created',
					'compare' => '>',
					'value' => '2015-12-31 10:00:00'
				)
			)
		);
		$apps = appointments_get_appointments( $args );
		$this->assertCount( 1, $apps );

		$args = array(
			'date_query' => array(
				array(
					'field' => 'created',
					'compare' => '<',
					'value' => '2015-12-31 10:00:00'
				)
			)
		);
		$apps = appointments_get_appointments( $args );
		$this->assertCount( 2, $apps );

		$args = array(
			'date_query' => array(
				array(
					'field' => 'created',
					'compare' => '>=',
					'value' => '2015-11-11 10:00:00'
				)
			)
		);
		$apps = appointments_get_appointments( $args );
		$this->assertCount( 3, $apps );

		$args = array(
			'date_query' => array(
				array(
					'field' => 'created',
					'compare' => '>=',
					'value' => '2015-11-11 10:00:00'
				),
				array(
					'field' => 'created',
					'compare' => '<',
					'value' => '2016-02-11 23:00:00'
				),
			)
		);
		$apps = appointments_get_appointments( $args );
		$this->assertCount( 2, $apps );

		$args = array(
			'date_query' => array(
				array(
					'field' => 'created',
					'compare' => '>',
					'value' => '2015-11-11 10:00:00'
				),
				array(
					'field' => 'created',
					'compare' => '>=',
					'value' => '2016-02-11 23:00:00'
				),
				'condition' => 'OR'
			)
		);
		$apps = appointments_get_appointments( $args );
		$this->assertCount( 2, $apps );

		$args = array(
			'date_query' => array(
				array(
					'field' => 'created',
					'compare' => '<',
					'value' => '2017-11-11 10:00:00'
				),
				array(
					'field' => 'created',
					'compare' => '>',
					'value' => '2016-02-11 23:00:00'
				),
				'condition' => 'AND'
			)
		);
		$apps = appointments_get_appointments( $args );
		$this->assertCount( 0, $apps );
	}

	function test_get_appointments_pagination() {
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
			'status' => 'pending',
			'created' => '2015-11-11 10:00:00'
		);
		$app_id_1 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'pending',
			'created' => '2015-12-30 11:00:00'
		);
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'pending',
			'created' => '2016-02-11 23:00:00'
		);
		$app_id_3 = appointments_insert_appointment( $args );

		$apps = appointments_get_appointments( array( 'per_page' => 1 ) );
		$this->assertCount( 1, $apps );
		$this->assertEquals( wp_list_pluck( $apps, 'ID' ), array( $app_id_1 ) );

		$apps = appointments_get_appointments( array( 'per_page' => 2 ) );
		$this->assertCount( 2, $apps );
		$this->assertEquals( wp_list_pluck( $apps, 'ID' ), array( $app_id_1, $app_id_2 ) );

		$apps = appointments_get_appointments( array( 'per_page' => 2, 'page' => 2 ) );
		$this->assertCount( 1, $apps );
		$this->assertEquals( wp_list_pluck( $apps, 'ID' ), array( $app_id_3 ) );

		$apps = appointments_get_appointments( array( 'per_page' => 2, 'page' => 3 ) );
		$this->assertCount( 0, $apps );
	}

	function test_get_appointments_search() {
		$worker_id_1 = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id_2 = $this->factory->user->create_object( $this->factory->user->generate_args() );

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
			'status' => 'pending',
			'name' => 'Another name',
			'created' => '2015-11-11 10:00:00'
		);
		$app_id_1 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'pending',
			'name' => 'A name',
			'created' => '2015-12-30 11:00:00'
		);
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'user' => $user_id_2,
			'status' => 'pending',
			'created' => '2016-02-11 23:00:00'
		);
		$app_id_3 = appointments_insert_appointment( $args );


		$apps = appointments_get_appointments( array( 's' => 'another' ) );
		$this->assertCount( 1, $apps );
		$apps = appointments_get_appointments( array( 's' => 'name' ) );
		$this->assertCount( 2, $apps );

		// Search by user
		$user = get_userdata( $user_id_2 );
		$apps = appointments_get_appointments( array( 's' => $user->user_login ) );
		$this->assertCount( 1, $apps );
	}

	function test_get_appointments_order() {
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
			'status' => 'pending',
			'name' => 'Cname',
			'created' => '2015-11-11 10:00:00'
		);
		$app_id_1 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'pending',
			'name' => 'Aname',
			'created' => '2015-12-30 11:00:00'
		);
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'name' => 'Bname',
			'status' => 'pending',
			'created' => '2016-02-11 23:00:00'
		);
		$app_id_3 = appointments_insert_appointment( $args );


		$apps = appointments_get_appointments( array( 'orderby' => 'ID', 'order' => 'DESC' ) );
		$this->assertEquals( wp_list_pluck( $apps, 'ID' ), array( $app_id_3, $app_id_2, $app_id_1 ) );

		$apps = appointments_get_appointments( array( 'orderby' => 'name', 'order' => 'ASC' ) );
		$this->assertEquals( wp_list_pluck( $apps, 'ID' ), array( $app_id_2, $app_id_3, $app_id_1 ) );
	}

	function test_get_appointments_by_service() {
		$worker_id_1 = $this->factory->user->create_object( $this->factory->user->generate_args() );
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

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'pending',
			'name' => 'Cname',
			'created' => '2015-11-11 10:00:00'
		);
		$app_id_1 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_2,
			'worker' => $worker_id_1,
			'status' => 'pending',
			'name' => 'Aname',
			'created' => '2015-12-30 11:00:00'
		);
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_2,
			'worker' => $worker_id_1,
			'name' => 'Bname',
			'status' => 'pending',
			'created' => '2016-02-11 23:00:00'
		);
		$app_id_3 = appointments_insert_appointment( $args );

		$apps = appointments_get_appointments( array( 'service' => $service_id_1 ) );
		$this->assertEquals( wp_list_pluck( $apps, 'ID' ), array( $app_id_1 ) );

		$apps = appointments_get_appointments( array( 'service' => $service_id_2 ) );
		$this->assertEquals( wp_list_pluck( $apps, 'ID' ), array( $app_id_2, $app_id_3 ) );

		// Get by several services
		$apps = appointments_get_appointments( array( 'service' => array( $service_id_1, $service_id_2 ) ) );
		$this->assertEquals( wp_list_pluck( $apps, 'ID' ), array( $app_id_1, $app_id_2, $app_id_3 ) );
	}

	function test_get_appointments_count_rows() {
		$worker_id_1 = $this->factory->user->create_object( $this->factory->user->generate_args() );
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

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'pending',
			'name' => 'Cname',
			'created' => '2015-11-11 10:00:00'
		);
		$app_id_1 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_2,
			'worker' => $worker_id_1,
			'status' => 'pending',
			'name' => 'Aname',
			'created' => '2015-12-30 11:00:00'
		);
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_2,
			'worker' => $worker_id_1,
			'name' => 'Bname',
			'status' => 'pending',
			'created' => '2016-02-11 23:00:00'
		);
		$app_id_3 = appointments_insert_appointment( $args );

		$apps = appointments_get_appointments( array( 'count' => true ) );
		$this->assertEquals( 3, $apps );

		$apps = appointments_get_appointments( array( 'count' => true, 'per_page' => 1, 'page' => 2 ) );
		$this->assertEquals( 3, $apps );
	}

	function test_get_user_appointments() {
		$worker_id = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id_1 = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id_2 = $this->factory->user->create_object( $this->factory->user->generate_args() );

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
			'user' => $user_id_1,
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
			'user' => $user_id_1,
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
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'user' => $user_id_1,
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
		$app_id_3 = appointments_insert_appointment( $args );


		$args = array(
			'user' => $user_id_2,
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
		$app_id_4 = appointments_insert_appointment( $args );

		$apps = wp_list_pluck( appointments_get_user_appointments( $user_id_1 ), 'ID' );
		sort( $apps );
		$this->assertEquals( $apps, array( $app_id_1, $app_id_3 ) );

	}
}
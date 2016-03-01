<?php

/**
 * @group appointments
 * @group old_queries
 */
class App_Appointments_Old_Queries_Test extends App_UnitTestCase {
	function test_check_spam() {
		global $appointments;

		$appointments->options["spam_time"] = 20;

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
			'status' => 'pending',
		);
		$app_id_3 = appointments_insert_appointment( $args );

		$_COOKIE["wpmudev_appointments"] = maybe_serialize( array( $app_id_1, $app_id_2, $app_id_3 ) );

		$this->assertFalse( $appointments->check_spam() );

		appointments_delete_appointment( $app_id_1 );
		appointments_delete_appointment( $app_id_2 );
		appointments_delete_appointment( $app_id_3 );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'pending',
			'created' => '2015-10-11 10:00:00'
		);
		$app_id_1 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'pending',
			'created' => '2015-10-11 10:00:00'
		);
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'pending',
		);
		$app_id_3 = appointments_insert_appointment( $args );

		$this->assertTrue( $appointments->check_spam() );

	}


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

	function test_send_reminder_old() {
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
		);
		$args['status'] = 'confirmed';
		$app_id_1 = appointments_insert_appointment( $args );

		$args['status'] = 'paid';
		$app_id_2 = appointments_insert_appointment( $args );

		$args['status'] = 'paid';
		$app_id_3 = appointments_insert_appointment( $args );

		$args['status'] = 'completed';
		$app_id_4 = appointments_insert_appointment( $args );

		appointments_update_appointment( $app_id_1, array( 'sent' => array( 1, 3, 10 ), 'datetime' => current_time( 'timestamp' ) ) );
		appointments_update_appointment( $app_id_2, array( 'sent' => array( 1, 10 ) ) );
		// app 3: sent must be null
		appointments_update_appointment( $app_id_4, array( 'sent' => array( 10 ) ) );

		$hours = array( 4, 1, 10, 11, 0 );
		$current_time = current_time( 'timestamp' );
		$app_table = appointments_get_table( 'appointments' );

		foreach ( $hours as $hour ) {
			$rlike = (string) absint($hour);
			$old_query =  "SELECT * FROM " . $app_table . "
				WHERE (status='paid' OR status='confirmed')
				AND (sent NOT LIKE '%:{$rlike}:%' OR sent IS NULL)
				AND DATE_ADD('".date( 'Y-m-d H:i:s', $current_time )."', INTERVAL ".(int)$hour." HOUR) > start ";

			$old_results = $wpdb->get_results( $old_query );
			$results = appointments_get_unsent_appointments( $hour );

			$this->assertCount( count( $old_results ), $results );

			$old_ids = wp_list_pluck( $old_results, 'ID' );
			$ids = wp_list_pluck( $results, 'ID' );

			$this->assertEquals( $old_ids, $ids );
		}
	}

	function test_send_reminder_worker_old() {
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
		);
		$args['status'] = 'confirmed';
		$app_id_1 = appointments_insert_appointment( $args );

		$args['status'] = 'paid';
		$app_id_2 = appointments_insert_appointment( $args );

		$args['status'] = 'paid';
		$app_id_3 = appointments_insert_appointment( $args );

		$args['status'] = 'completed';
		$app_id_4 = appointments_insert_appointment( $args );

		appointments_update_appointment( $app_id_1, array( 'sent_worker' => array( 1, 3, 10 ), 'datetime' => current_time( 'timestamp' ) ) );
		appointments_update_appointment( $app_id_2, array( 'sent_worker' => array( 1, 10 ) ) );
		// app 3: sent must be null
		appointments_update_appointment( $app_id_4, array( 'sent_worker' => array( 10 ) ) );

		$hours = array( 4, 1, 10, 11, 0 );
		$current_time = current_time( 'timestamp' );
		$app_table = appointments_get_table( 'appointments' );

		foreach ( $hours as $hour ) {
			$rlike = (string) absint($hour);
			$old_query =  "SELECT * FROM " . $app_table . "
				WHERE (status='paid' OR status='confirmed')
				AND worker <> 0
				AND (sent_worker NOT LIKE '%:{$rlike}:%' OR sent_worker IS NULL)
				AND DATE_ADD('".date( 'Y-m-d H:i:s', $current_time )."', INTERVAL ".(int)$hour." HOUR) > start ";

			$old_results = $wpdb->get_results( $old_query );
			$results = appointments_get_unsent_appointments( $hour, 'worker' );

			$this->assertCount( count( $old_results ), $results );

			$old_ids = wp_list_pluck( $old_results, 'ID' );
			$ids = wp_list_pluck( $results, 'ID' );

			$this->assertEquals( $old_ids, $ids );
		}
	}

	function test_monthly_shortcode_get_worker_appointments() {
		// Old App_Shortcode_WorkerMonthlyCalendar::_get_worker_appointments

		global $wpdb;

		$worker_id_1 = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$worker_id_2 = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$worker_id_3 = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id_1 = appointments_insert_service( $service_args );

		$service_args = array(
			'name' => 'My other Service',
			'duration' => 90
		);
		$service_id_2 = appointments_insert_service( $service_args );


		$worker_args = array(
			'ID' => $worker_id_1,
			'services_provided' => array( $service_id_1, $service_id_2 )
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
			'service' => $service_id_1,
			'worker' => $worker_id_1,
			'status' => 'confirmed',
			'date' => strtotime( '2016-01-01 10:00:00' )
		);
		$app_id_1 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_2,
			'worker' => $worker_id_1,
			'status' => 'paid',
			'date' => strtotime( '2016-01-02 11:00:00' )
		);
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_2,
			'worker' => $worker_id_2,
			'status' => 'paid',
			'date' => strtotime( '2016-02-03 12:00:00' )
		);
		$app_id_3 = appointments_insert_appointment( $args );

		$args = array(
			'service' => $service_id_2,
			'worker' => $worker_id_3,
			'status' => 'confirmed',
			'date' => strtotime( '2016-01-04 13:00:00' )
		);
		$app_id_4 = appointments_insert_appointment( $args );

		// Status is wrong, it should be an array, it will return any status
		$start_at = strtotime( '2016-01-12 10:00:00' ); // It doesn't matter the day, the important thing is the month
		$status = 'confirmed';
		$old_apps = $this->_old_get_worker_appointments( $worker_id_1, $status, $start_at );
		$new_function_apps = appointments_get_month_appointments( array( 'worker' => $worker_id_1, 'start' => date( 'Y-m-d H:i:s', $start_at ) ) );

		$this->assertCount( 3, $old_apps );
		$this->assertEquals( wp_list_pluck( $old_apps, 'ID' ), wp_list_pluck( $new_function_apps, 'ID' ) );

		// Let's test the cache
		appointments_update_appointment( $app_id_2, array( 'datetime' => strtotime( '2016-02-03 12:00:00' ) ) ); // Set it to another month

		$old_apps = $this->_old_get_worker_appointments( $worker_id_1, $status, $start_at );
		$new_function_apps = appointments_get_month_appointments( array( 'worker' => $worker_id_1, 'start' => date( 'Y-m-d H:i:s', $start_at ) ) );

		$this->assertCount( 2, $old_apps );
		$this->assertEquals( wp_list_pluck( $old_apps, 'ID' ), wp_list_pluck( $new_function_apps, 'ID' ) );

		// Revert back the last update
		appointments_update_appointment( $app_id_2, array( 'datetime' => strtotime( '2016-01-03 12:00:00' ) ) );

		// Array status
		$status = array( 'confirmed' );
		$old_apps = $this->_old_get_worker_appointments( $worker_id_1, $status, $start_at );
		$new_function_apps = appointments_get_month_appointments( array( 'worker' => $worker_id_1, 'status' => $status, 'start' => date( 'Y-m-d H:i:s', $start_at ) ) );
		$this->assertCount( 2, $old_apps );
		$this->assertEquals( wp_list_pluck( $old_apps, 'ID' ), wp_list_pluck( $new_function_apps, 'ID' ) );

		$status = array( 'paid' );
		$old_apps = $this->_old_get_worker_appointments( $worker_id_1, $status, $start_at );
		$new_function_apps = appointments_get_month_appointments( array( 'worker' => $worker_id_1, 'status' => $status, 'start' => date( 'Y-m-d H:i:s', $start_at ) ) );
		$this->assertCount( 1, $old_apps );
		$this->assertEquals( wp_list_pluck( $old_apps, 'ID' ), wp_list_pluck( $new_function_apps, 'ID' ) );


	}


	function test_update_gcal_appointment() {
		global $wpdb;

		$args = array(
			'gcal_updated' => '2015-01-01 10:00:00'
		);
		$app_id_1 = appointments_insert_appointment( $args );
		$app_id_2 = appointments_insert_appointment( $args );

		$current_time = current_time( 'mysql' );
		$current_datetime = strtotime( $current_time );
		$table = appointments_get_table( 'appointments' );
		$wpdb->update( $table, array( 'gcal_updated' => date ("Y-m-d H:i:s", $current_datetime ) ), array( 'ID'=>$app_id_1 ) );
		appointments_clear_appointment_cache( $app_id_1 );
		appointments_update_appointment( $app_id_2, array( 'gcal_updated' => date ("Y-m-d H:i:s", $current_datetime ) ) );

		// Both should match
		$app_1 = appointments_get_appointment( $app_id_1 );
		$app_2 = appointments_get_appointment( $app_id_2 );
		$this->assertEquals( $app_1->gcal_updated, $current_time );
		$this->assertEquals( $app_1->gcal_updated, $app_2->gcal_updated );
	}

	function test_update_service_and_locations() {
		global $wpdb;

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id_1 = appointments_insert_service( $service_args );

		$service_args = array(
			'name' => 'My Other Service',
			'duration' => 90
		);
		$service_id_2 = appointments_insert_service( $service_args );

		$args = array(
			'gcal_updated' => '2015-01-01 10:00:00',
			'service' => $service_id_1,
			'location' => 1
		);
		$app_id_1 = appointments_insert_appointment( $args );
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'gcal_updated' => '2015-01-01 10:00:00',
			'service' => $service_id_1,
			'location' => 2
		);
		$app_id_3 = appointments_insert_appointment( $args );

		$args = array(
			'gcal_updated' => '2015-01-01 10:00:00',
			'service' => $service_id_2,
			'location' => 1
		);
		$app_id_4 = appointments_insert_appointment( $args );

		$new_location_id = 10;

		$table = appointments_get_table( 'appointments' );
		$result = $wpdb->update(
			$table,
			array('location' => $new_location_id),
			array(
				'location' => 1,
				'service' => $service_id_1,
			), '%s', '%s'
		);
		appointments_clear_appointment_cache();

		$app_1 = appointments_get_appointment( $app_id_1 );
		$app_2 = appointments_get_appointment( $app_id_2 );
		$app_3 = appointments_get_appointment( $app_id_3 );
		$app_4 = appointments_get_appointment( $app_id_4 );
		$this->assertEquals( $app_1->location, $new_location_id );
		$this->assertEquals( $app_2->location, $new_location_id );
		$this->assertEquals( $app_3->location, 2 );
		$this->assertEquals( $app_4->location, 1 );

		// Should be the same than:
		$apps = appointments_get_appointments( array( 'location' => $new_location_id, 'service' => $service_id_1 ) );
		$new_location_id = 15;
		foreach ( $apps as $app ) {
			appointments_update_appointment( $app->ID, array( 'location' => $new_location_id ) );
		}

		$app_1 = appointments_get_appointment( $app_id_1 );
		$app_2 = appointments_get_appointment( $app_id_2 );
		$app_3 = appointments_get_appointment( $app_id_3 );
		$app_4 = appointments_get_appointment( $app_id_4 );
		$this->assertEquals( $app_1->location, $new_location_id );
		$this->assertEquals( $app_2->location, $new_location_id );
		$this->assertEquals( $app_3->location, 2 );
		$this->assertEquals( $app_4->location, 1 );
	}

	function test_update_worker_and_locations() {
		global $wpdb;

		$worker_id_1 = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$worker_id_2 = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id_1 = appointments_insert_service( $service_args );

		$service_args = array(
			'name' => 'My Other Service',
			'duration' => 90
		);
		$service_id_2 = appointments_insert_service( $service_args );

		$worker_args = array(
			'ID' => $worker_id_1,
			'services_provided' => array( $service_id_1, $service_id_2 )
		);
		appointments_insert_worker( $worker_args );

		$worker_args = array(
			'ID' => $worker_id_2,
			'services_provided' => array( $service_id_1, $service_id_2 )
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'gcal_updated' => '2015-01-01 10:00:00',
			'worker' => $worker_id_1,
			'location' => 1
		);
		$app_id_1 = appointments_insert_appointment( $args );
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'gcal_updated' => '2015-01-01 10:00:00',
			'service' => $worker_id_1,
			'location' => 2
		);
		$app_id_3 = appointments_insert_appointment( $args );

		$args = array(
			'gcal_updated' => '2015-01-01 10:00:00',
			'service' => $worker_id_2,
			'location' => 1
		);
		$app_id_4 = appointments_insert_appointment( $args );

		$new_location_id = 10;

		$table = appointments_get_table( 'appointments' );
		$result = $wpdb->update(
			$table,
			array('location' => $new_location_id),
			array(
				'location' => 1,
				'worker' => $worker_id_1,
			), '%s', '%s'
		);
		appointments_clear_appointment_cache();

		$app_1 = appointments_get_appointment( $app_id_1 );
		$app_2 = appointments_get_appointment( $app_id_2 );
		$app_3 = appointments_get_appointment( $app_id_3 );
		$app_4 = appointments_get_appointment( $app_id_4 );
		$this->assertEquals( $app_1->location, $new_location_id );
		$this->assertEquals( $app_2->location, $new_location_id );
		$this->assertEquals( $app_3->location, 2 );
		$this->assertEquals( $app_4->location, 1 );

		// Should be the same than:
		$apps = appointments_get_appointments( array( 'location' => $new_location_id, 'worker' => $worker_id_1 ) );
		$new_location_id = 15;
		foreach ( $apps as $app ) {
			appointments_update_appointment( $app->ID, array( 'location' => $new_location_id ) );
		}

		$app_1 = appointments_get_appointment( $app_id_1 );
		$app_2 = appointments_get_appointment( $app_id_2 );
		$app_3 = appointments_get_appointment( $app_id_3 );
		$app_4 = appointments_get_appointment( $app_id_4 );
		$this->assertEquals( $app_1->location, $new_location_id );
		$this->assertEquals( $app_2->location, $new_location_id );
		$this->assertEquals( $app_3->location, 2 );
		$this->assertEquals( $app_4->location, 1 );
	}

	function test_select_distinct_locations() {
		global $wpdb;

		$user_id_1 = $this->factory->user->create_object( $this->factory->user->generate_args() );
		$user_id_2 = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$args = array(
			'user' => $user_id_1,
			'location' => 1,
			'status' => 'paid'
		);
		$app_id_1 = appointments_insert_appointment( $args );
		$app_id_2 = appointments_insert_appointment( $args );

		$args = array(
			'user' => $user_id_2,
			'location' => 5,
			'status' => 'pending'
		);
		$app_id_3 = appointments_insert_appointment( $args );

		$args = array(
			'user' => $user_id_1,
			'location' => 6,
			'status' => 'completed'
		);
		$app_id_4 = appointments_insert_appointment( $args );

		$args = array(
			'user' => $user_id_1,
			'location' => 7,
			'status' => 'paid'
		);
		$app_id_5 = appointments_insert_appointment( $args );


		$raw_status = array( 'pending', 'paid' );
		$status = "status IN('" . join("','", $raw_status) . "')";
		$user = (!empty($status) ? 'AND ' : '') . "user=" . (int)$user_id_1;
		$table = appointments_get_table( 'appointments' );
		$sql = "SELECT DISTINCT location FROM {$table} WHERE {$status} {$user}";
		$old_locations = $wpdb->get_col( $sql );
		$old_locations = array_values( $old_locations );

		$query_args = array();
		$query_args['status'] = $raw_status;
		$query_args['user'] = $user_id_1;
		$apps = appointments_get_appointments( $query_args );
		$locations = wp_list_pluck( $apps, 'location' );
		$locations = array_unique( array_values( $locations ) );

		$this->assertEquals( array_values( $old_locations ), array_values( $locations ) );


	}

	/**
	 * This is the old function for the previous test
	 *
	 * See App_Shortcode_WorkerMonthlyCalendar::_get_worker_appointments()
	 */
	private function _old_get_worker_appointments ($worker_id, $status, $start_at) {
		global $appointments, $wpdb;

		$services = appointments_get_worker_services($worker_id);
		$service_ids = !empty($services)
			? array_filter(array_map('intval', wp_list_pluck($services, 'ID')))
			: false
		;
		$worker_sql = !empty($service_ids)
			? $wpdb->prepare('(worker=%d OR service IN(' . join(',', $service_ids) . '))', $worker_id)
			: $wpdb->prepare('worker=%d', $worker_id)
		;

		$status = is_array($status) ? array_map( 'esc_sql', $status) : false;
		$status_sql = $status ? "AND status IN('" . join("','", $status) . "')" : '';

		$first = strtotime(date('Y-m-01', $start_at));
		$last = ($first + (date('t', $first) * 86400 )) - 1;

		$sql = $wpdb->prepare(
			"SELECT * FROM {$appointments->app_table} WHERE {$worker_sql} {$status_sql} AND UNIX_TIMESTAMP(start)>%d AND UNIX_TIMESTAMP(end)<%d ORDER BY start ASC",
			$first, $last
		);


		return $wpdb->get_results($sql);
	}

}
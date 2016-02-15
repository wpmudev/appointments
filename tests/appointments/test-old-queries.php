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

}
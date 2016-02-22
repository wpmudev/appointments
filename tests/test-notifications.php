<?php

/**
 * @group notifications
 */
class App_Notifications_Test extends App_UnitTestCase {

	function test_send_notification() {
		global $appointments;

		$appointments->options["send_notification"] = 'yes';
		$appointments->options["log_emails"] = 'yes';
		$appointments->options["allow_worker_confirm"] = 'yes';

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

		$this->app_id = appointments_insert_appointment( $args );

		// For the admin
		add_action( 'app_notification_sent', function( $body, $r, $sent_app_id ) {
			$this->admin_notification_sent = true;
			$this->assertEquals( $this->app_id, $sent_app_id );
			$this->assertContains( 'The new appointment has an ID ' . $this->app_id . ' and you can edit it clicking this link:', $body );
		}, 10, 3);

		// For the worker
		add_action( 'appointments_worker_notification_sent', function( $body, $r, $sent_app_id ) {
			global $appointments;
			$this->worker_notification_sent = true;
			$this->assertEquals( $this->app_id, $sent_app_id );
			$this->assertContains( 'The new appointment has an ID ' . $this->app_id . ' for ' . date_i18n($appointments->datetime_format, strtotime(appointments_get_appointment( $this->app_id )->start ) ) . ' and you can confirm it using your profile page.', $body );
		}, 10, 3);

		$appointments->send_notification( $this->app_id );

		$this->assertTrue( $this->admin_notification_sent );
		$this->assertTrue( $this->worker_notification_sent );

		$this->admin_notification_sent = false;
		$this->worker_notification_sent = false;
	}

	function test_send_cancel_notification() {
		global $appointments;

		$appointments->options["send_notification"] = 'yes';
		$appointments->options["log_emails"] = 'yes';

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

		$this->app_id = appointments_insert_appointment( $args );

		// Admin notification
		add_action( 'app_notification_sent', function( $body, $r, $sent_app_id ) {
			$this->admin_notification_sent = true;
			$this->assertEquals( $this->app_id, $sent_app_id );
			$this->assertContains( 'Appointment with ID ' . $this->app_id . ' has been cancelled by the client. You can see it clicking this link:', $body );
		}, 10, 3);

		// Worker notification
		add_action( 'appointments_worker_notification_sent', function( $body, $r, $sent_app_id ) {
			global $appointments;
			$this->worker_notification_sent = true;
			$this->assertEquals( $this->app_id, $sent_app_id );
			$this->assertContains( 'Cancelled appointment has an ID ' . $this->app_id . ' for ' . date_i18n($appointments->datetime_format, strtotime(appointments_get_appointment( $this->app_id )->start ) ), $body );
		}, 10, 3);

		$appointments->send_notification( $this->app_id, true );

		$this->assertTrue( $this->admin_notification_sent );
		$this->assertTrue( $this->worker_notification_sent );

		$this->admin_notification_sent = false;
		$this->worker_notification_sent = false;
	}
}
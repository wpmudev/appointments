<?php

/**
 * @group notifications
 */
class App_Notifications_Test extends App_UnitTestCase {

	public $sent_to;

	function setUp() {
		parent::setUp();
		$this->markTestSkipped(
			'Notification tests need some love first'
		);
	}

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

	/**
	 * @group notification-confirmation
	 */
	function test_send_confirmation_to_customer_admin_and_worker() {
		global $appointments;

		$options = appointments_get_options();
		$options["send_notification"] = 'yes';
		$options["log_emails"] = 'yes';
		$options["allow_worker_confirm"] = 'yes';
		appointments_update_options( $options );

		update_option( 'admin_email', 'admin@email.dev' );

		$worker_args = $this->factory->user->generate_args();
		$worker_args['user_email'] = 'worker@email.dev';
		$worker_id = $this->factory->user->create_object( $worker_args );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id = appointments_insert_service( $service_args );
		$service = appointments_get_service( $service_id );

		$worker_args = array(
			'ID' => $worker_id,
			'services_provided' => array( $service_id ),
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'user' => $user_id,
			'email' => 'customer@email.dev',
			'service' => $service_id,
			'worker' => $worker_id,
			'status' => 'paid',
		);

		$this->sent_to = array();

		add_action( 'app_confirmation_sent', function( $body, $r, $sent_app_id, $email ) {
			$this->sent_to[] = array( 'type' => 'customer', 'email' => $email );
		}, 10, 4);

		add_action( 'appointments_confirmation_admin_sent', function( $email, $app_id, $body, $subject ) {
			$this->sent_to[] = array( 'type' => 'admin', 'email' => $email );
		}, 10, 4);

		$this->app_id = appointments_insert_appointment( $args );

		$this->assertCount( 3, $this->sent_to );
		$emails = wp_list_pluck( $this->sent_to, 'email' );
		$types = wp_list_pluck( $this->sent_to, 'type' );
		$this->assertEquals( $emails, array( 'customer@email.dev', 'admin@email.dev', 'worker@email.dev' ) );
		$this->assertEquals( $types, array( 'customer', 'admin', 'admin' ) );
	}

	/**
	 * An appointment without an email should not send anything
	 * @group notification-confirmation
	 */
	function test_send_confirmation_to_no_customer() {
		$options = appointments_get_options();
		$options["send_notification"] = 'yes';
		$options["log_emails"] = 'yes';
		$options["allow_worker_confirm"] = 'yes';
		appointments_update_options( $options );

		update_option( 'admin_email', 'admin@email.dev' );

		$worker_args = $this->factory->user->generate_args();
		$worker_args['user_email'] = 'worker@email.dev';
		$worker_id = $this->factory->user->create_object( $worker_args );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id = appointments_insert_service( $service_args );
		$service = appointments_get_service( $service_id );

		$worker_args = array(
			'ID' => $worker_id,
			'services_provided' => array( $service_id ),
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'user' => 0,
			'email' => '',
			'phone' => '667788',
			'service' => $service_id,
			'worker' => $worker_id,
			'status' => 'paid',
		);

		$this->sent_to = array();

		add_action( 'app_confirmation_sent', function( $body, $r, $sent_app_id, $email ) {
			$this->sent_to[] = array( 'type' => 'customer', 'email' => $email );
		}, 10, 4);

		add_action( 'appointments_confirmation_admin_sent', function( $email, $app_id, $body, $subject ) {
			$this->sent_to[] = array( 'type' => 'admin', 'email' => $email );
		}, 10, 4);

		$this->app_id = appointments_insert_appointment( $args );

		$this->assertCount( 0, $this->sent_to );
	}

	/**
	 * Admin and worker have the same email. Just send one
	 *
	 * @group notification-confirmation
	 */
	function test_send_confirmation_to_worker_and_admin_with_same_email() {
		$options = appointments_get_options();
		$options["send_notification"] = 'yes';
		$options["log_emails"] = 'yes';
		$options["allow_worker_confirm"] = 'yes';
		appointments_update_options( $options );

		update_option( 'admin_email', 'admin@email.dev' );

		$worker_args = $this->factory->user->generate_args();
		$worker_args['user_email'] = 'admin@email.dev';
		$worker_id = $this->factory->user->create_object( $worker_args );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id = appointments_insert_service( $service_args );
		$service = appointments_get_service( $service_id );

		$worker_args = array(
			'ID' => $worker_id,
			'services_provided' => array( $service_id ),
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'user' => $user_id,
			'email' => 'customer@email.dev',
			'service' => $service_id,
			'worker' => $worker_id,
			'status' => 'paid',
		);

		$this->sent_to = array();

		add_action( 'app_confirmation_sent', function( $body, $r, $sent_app_id, $email ) {
			$this->sent_to[] = array( 'type' => 'customer', 'email' => $email );
		}, 10, 4);

		add_action( 'appointments_confirmation_admin_sent', function( $email, $app_id, $body, $subject ) {
			$this->sent_to[] = array( 'type' => 'admin', 'email' => $email );
		}, 10, 4);

		$this->app_id = appointments_insert_appointment( $args );

		$this->assertCount( 2, $this->sent_to );
		$emails = wp_list_pluck( $this->sent_to, 'email' );
		$types = wp_list_pluck( $this->sent_to, 'type' );
		$this->assertEquals( $emails, array( 'customer@email.dev', 'admin@email.dev' ) );
		$this->assertEquals( $types, array( 'customer', 'admin' ) );
	}

	/**
	 * If no email is set in the Appointment, the confirmation email should be send to the WP user email
	 *
	 * @group notification-confirmation
	 */
	function test_send_confirmation_to_wp_user() {
		$options = appointments_get_options();
		$options["send_notification"] = 'yes';
		$options["log_emails"] = 'yes';
		$options["allow_worker_confirm"] = 'yes';
		appointments_update_options( $options );

		update_option( 'admin_email', 'admin@email.dev' );

		$worker_args = $this->factory->user->generate_args();
		$worker_args['user_email'] = 'worker@email.dev';
		$worker_id = $this->factory->user->create_object( $worker_args );

		$user_args = $this->factory->user->generate_args();
		$user_args['user_email'] = 'customer@email.dev';
		$user_id = $this->factory->user->create_object( $user_args );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id = appointments_insert_service( $service_args );
		$service = appointments_get_service( $service_id );

		$worker_args = array(
			'ID' => $worker_id,
			'services_provided' => array( $service_id ),
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'user' => $user_id,
			'email' => '',
			'service' => $service_id,
			'worker' => $worker_id,
			'status' => 'paid',
		);

		$this->sent_to = array();

		add_action( 'app_confirmation_sent', function( $body, $r, $sent_app_id, $email ) {
			$this->sent_to[] = array( 'type' => 'customer', 'email' => $email );
		}, 10, 4);

		add_action( 'appointments_confirmation_admin_sent', function( $email, $app_id, $body, $subject ) {
			$this->sent_to[] = array( 'type' => 'admin', 'email' => $email );
		}, 10, 4);

		$this->app_id = appointments_insert_appointment( $args );

		$this->assertCount( 3, $this->sent_to );
		$emails = wp_list_pluck( $this->sent_to, 'email' );
		$types = wp_list_pluck( $this->sent_to, 'type' );
		$this->assertEquals( $emails, array( 'customer@email.dev', 'admin@email.dev', 'worker@email.dev' ) );
		$this->assertEquals( $types, array( 'customer', 'admin', 'admin' ) );

	}

	/**
	 * But the appointment email should overwrite the WP User email
	 *
	 * @group notification-confirmation
	 */
	function test_send_confirmation_overwrite_user_email() {

		$options = appointments_get_options();
		$options["send_notification"] = 'yes';
		$options["log_emails"] = 'yes';
		$options["allow_worker_confirm"] = 'yes';
		appointments_update_options( $options );

		update_option( 'admin_email', 'admin@email.dev' );

		$worker_args = $this->factory->user->generate_args();
		$worker_args['user_email'] = 'worker@email.dev';
		$worker_id = $this->factory->user->create_object( $worker_args );

		$user_args = $this->factory->user->generate_args();
		$user_args['user_email'] = 'customer@email.dev';
		$user_id = $this->factory->user->create_object( $user_args );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id = appointments_insert_service( $service_args );
		$service = appointments_get_service( $service_id );

		$worker_args = array(
			'ID' => $worker_id,
			'services_provided' => array( $service_id ),
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'user' => $user_id,
			'email' => 'overwrite@email.dev',
			'service' => $service_id,
			'worker' => $worker_id,
			'status' => 'paid',
		);

		$this->sent_to = array();

		add_action( 'app_confirmation_sent', function( $body, $r, $sent_app_id, $email ) {
			$this->sent_to[] = array( 'type' => 'customer', 'email' => $email );
		}, 10, 4);

		add_action( 'appointments_confirmation_admin_sent', function( $email, $app_id, $body, $subject ) {
			$this->sent_to[] = array( 'type' => 'admin', 'email' => $email );
		}, 10, 4);

		$this->app_id = appointments_insert_appointment( $args );

		$this->assertCount( 3, $this->sent_to );
		$emails = wp_list_pluck( $this->sent_to, 'email' );
		$types = wp_list_pluck( $this->sent_to, 'type' );
		$this->assertEquals( $emails, array( 'overwrite@email.dev', 'admin@email.dev', 'worker@email.dev' ) );
		$this->assertEquals( $types, array( 'customer', 'admin', 'admin' ) );
	}

	/**
	 * Updating status
	 *
	 * @group notification-confirmation
	 */
	function test_send_confirmation_on_update_status() {
		$options = appointments_get_options();
		$options["send_notification"] = 'yes';
		$options["log_emails"] = 'yes';
		$options["allow_worker_confirm"] = 'yes';
		appointments_update_options( $options );

		update_option( 'admin_email', 'admin@email.dev' );

		$worker_args = $this->factory->user->generate_args();
		$worker_args['user_email'] = 'worker@email.dev';
		$worker_id = $this->factory->user->create_object( $worker_args );
		$user_id = $this->factory->user->create_object( $this->factory->user->generate_args() );

		$service_args = array(
			'name' => 'My Service',
			'duration' => 90
		);
		$service_id = appointments_insert_service( $service_args );
		$service = appointments_get_service( $service_id );

		$worker_args = array(
			'ID' => $worker_id,
			'services_provided' => array( $service_id ),
		);
		appointments_insert_worker( $worker_args );

		$args = array(
			'user' => $user_id,
			'email' => 'customer@email.dev',
			'service' => $service_id,
			'worker' => $worker_id,
			'status' => 'pending',
		);

		$this->sent_to = array();

		add_action( 'app_confirmation_sent', function( $body, $r, $sent_app_id, $email ) {
			$this->sent_to[] = array( 'type' => 'customer', 'email' => $email );
		}, 10, 4);

		add_action( 'appointments_confirmation_admin_sent', function( $email, $app_id, $body, $subject ) {
			$this->sent_to[] = array( 'type' => 'admin', 'email' => $email );
		}, 10, 4);

		$this->app_id = appointments_insert_appointment( $args );

		// Status pending, do not send
		$this->assertCount( 0, $this->sent_to );

		appointments_update_appointment_status( $this->app_id, 'confirmed' );

		$this->assertCount( 3, $this->sent_to );
		$emails = wp_list_pluck( $this->sent_to, 'email' );
		$types = wp_list_pluck( $this->sent_to, 'type' );
		$this->assertEquals( $emails, array( 'customer@email.dev', 'admin@email.dev', 'worker@email.dev' ) );
		$this->assertEquals( $types, array( 'customer', 'admin', 'admin' ) );

		// Do not change if we set the same status again
		appointments_update_appointment_status( $this->app_id, 'confirmed' );
		$this->assertCount( 3, $this->sent_to );

	}
}
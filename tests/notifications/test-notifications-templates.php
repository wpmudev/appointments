<?php

/**
 * @group notifications-templates
 */
class App_Notifications_Templates_Test extends App_UnitTestCase {

	public $sent_to;

	function setUp() {
		parent::setUp();
	}

	function test_send_notification() {
		$appointments = appointments();
		$options = appointments_get_options();

		$options['confirmation_message'] = 'CLIENT||SITE_NAME||SERVICE||DATE_TIME||SERVICE_PROVIDER||CANCEL';
		appointments_update_options( $options );

		$service_id = $this->factory->service->create_object( $this->factory->service->generate_args() );

		$worker_args = $this->factory->worker->generate_args();
		$worker_args['services_provided'] = array( $service_id );
		$worker_id = $this->factory->worker->create_object( $worker_args );

		$app_args = $this->factory->appointment->generate_args();
		$app_args['worker'] = $worker_id;
		$app_args['service'] = $service_id;
		$app_id = $this->factory->appointment->create_object( $app_args );

		$notifications = $appointments->notifications;
		$confirmation_template = $notifications->confirmation->get_customer_template( $app_id, 'email@email.dev' );

		$app = appointments_get_appointment( $app_id );
		$expected = array(
			$app->name,
			wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
			appointments_get_service( $service_id )->name,
			mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $app->start ),
			appointments_get_worker_name( $worker_id ),
			appointments_get_cancel_link_url( $app_id )
		);

		$this->assertEquals( implode( '||', $expected ), $confirmation_template['body'] );
	}
}
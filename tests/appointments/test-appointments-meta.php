<?php

/**
 * @group appointments
 * @group appointments-meta
 */
class App_Appointments_Meta_Test extends App_UnitTestCase {

	function test_app_meta() {

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
		);
		$app_id = appointments_insert_appointment( $args );
		appointments_update_appointment_meta( $app_id, 'app-meta', 'meta-value' );

		$meta_value = appointments_get_appointment_meta( $app_id, 'app-meta' );
		$this->assertEquals( $meta_value, 'meta-value' );

		appointments_delete_appointment_meta( $app_id, 'app-meta' );
		$meta_value = appointments_get_appointment_meta( $app_id, 'app-meta' );
		$this->assertEmpty( $meta_value );
	}

}
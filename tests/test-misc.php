<?php


/**
 * Class App_Activate_Test
 * @group misc
 */
class App_Misc_Test extends App_UnitTestCase {


	public function test_get_price() {
		$appointments = appointments();
		$service_id = $this->factory->service->create_object( array( 'name' => 'A service', 'price' => 11 ) );
		$worker_id = $this->factory->worker->create_object( array( 'price' => 5, 'services_provided' => array( $service_id ), 'user_email' => 'test@email.dev' ) );
		$_REQUEST["app_service_id"] = $service_id;
		$_REQUEST["app_worker_id"] = $worker_id;

		$this->assertEquals( 16.00, $appointments->get_price() );

		$_REQUEST["app_service_id"] = 12; // Service doesn't exist
		$_REQUEST["app_worker_id"] = $worker_id;

		$this->assertEquals( 0, $appointments->get_price() );

		$_REQUEST["app_service_id"] = $service_id;
		$_REQUEST["app_worker_id"] = 123123; // Worker does not exist

		$this->assertEquals( 11.00, $appointments->get_price() );


		$_REQUEST["app_service_id"] = 123123123;
		$_REQUEST["app_worker_id"] = 123123;
		$this->assertEquals( 0, $appointments->get_price() );
	}
}
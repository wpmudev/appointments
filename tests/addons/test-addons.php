<?php

/**
 * @group addons_main
 */
class Appointments_Addons_Test extends App_UnitTestCase {

	function test_activate_addon() {
		$appointments = appointments();

		Appointments_Addon::activate_addon( 'app-users-limit_services_login' );

		$this->assertContains( 'app-users-limit_services_login', $appointments->addons_loader->get_active_addons() );
	}

	function test_deactivate_addon() {
		$appointments = appointments();
		$loaded = $appointments->addons_loader->get_loaded_addons();
		$this->assertEmpty( $loaded );

		Appointments_Addon::activate_addon( 'app-users-limit_services_login' );
		Appointments_Addon::deactivate_addon( 'app-users-limit_services_login' );

		$this->assertNotContains( 'app-users-limit_services_login', $appointments->addons_loader->get_active_addons() );
	}
}
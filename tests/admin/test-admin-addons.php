<?php

/**
 * Class App_Admin_Addons_Test
 *
 * @group admin
 * @group addons
 */
class App_Admin_Addons_Test extends App_UnitTestCase {

	function test_activate_addon() {
		set_current_screen( 'appointments_page_app_settings' );
		$screen = get_current_screen();

		$appointments = appointments();
		$appointments->load_admin();
		$appointments->admin->admin_init();
		$settings_page = $appointments->admin->pages['settings'];


		$_REQUEST['_wpnonce'] = wp_create_nonce( 'activate-addon' );
		$_REQUEST['_wp_http_referer'] = wp_unslash( $_SERVER['REQUEST_URI'] );
		$_REQUEST['addon'] = 'app-users-limit_services_login';

		/** @var $settings_page Appointments_Admin_Settings_Page */
		$settings_page->_save_addons( 'activate' );

		$this->assertContains( 'app-users-limit_services_login', $appointments->addons_loader->get_active_addons() );


		$_REQUEST['_wpnonce'] = wp_create_nonce( 'bulk-addons' );
		$_REQUEST['_wp_http_referer'] = wp_unslash( $_SERVER['REQUEST_URI'] );
		$_REQUEST['addon'] = array( 'app-admin-admin_permissions', 'app-admin-export_date_range' );

		/** @var $settings_page Appointments_Admin_Settings_Page */
		$settings_page->_save_addons( 'activate' );

		$this->assertContains( 'app-users-limit_services_login', $appointments->addons_loader->get_active_addons() );
		$this->assertContains( 'app-admin-export_date_range', $appointments->addons_loader->get_active_addons() );
		$this->assertContains( 'app-admin-admin_permissions', $appointments->addons_loader->get_active_addons() );

		global $current_screen;
		$current_screen = null;
	}
}

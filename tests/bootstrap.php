<?php

$_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/appointments.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';


class App_UnitTestCase extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();

		appointments_activate();
	}

	function tearDown() {
		appointments_uninstall();
		parent::tearDown();
	}
}
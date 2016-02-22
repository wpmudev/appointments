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

	protected function remove_deprecated_filters() {
		remove_action( 'deprecated_function_run', array( $this, 'deprecated_function_run' ) );
		remove_action( 'deprecated_argument_run', array( $this, 'deprecated_function_run' ) );
		remove_action( 'doing_it_wrong_run', array( $this, 'doing_it_wrong_run' ) );
	}

	protected function add_deprecated_filters() {
		add_action( 'deprecated_function_run', array( $this, 'deprecated_function_run' ) );
		add_action( 'deprecated_argument_run', array( $this, 'deprecated_function_run' ) );
		add_action( 'doing_it_wrong_run', array( $this, 'doing_it_wrong_run' ) );
	}
}
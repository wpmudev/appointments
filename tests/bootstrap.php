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
		/** @var WP_UnitTest_Factory_For_Appointment appointment */
		$this->factory->appointment = new WP_UnitTest_Factory_For_Appointment( $this->factory );
		/** @var WP_UnitTest_Factory_For_Worker worker */
		$this->factory->worker = new WP_UnitTest_Factory_For_Worker( $this->factory );
		/** @var WP_UnitTest_Factory_For_Service service */
		$this->factory->service = new WP_UnitTest_Factory_For_Service( $this->factory );
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

	protected function load_addon( $name ) {
		$addons_loaded = get_option( 'app_activated_plugins', array() );
		$addons_loaded[] = $name;
		update_option( 'app_activated_plugins', array_unique( $addons_loaded ) );
		appointments()->addons_loader->load_active_addons();
	}
}


class WP_UnitTest_Factory_For_Appointment extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'name' => new WP_UnitTest_Generator_Sequence( 'Name %s' ),
			'note' => new WP_UnitTest_Generator_Sequence( 'Note %s' ),
			'phone' => new WP_UnitTest_Generator_Sequence( 'Phone %s' ),
			'address' => new WP_UnitTest_Generator_Sequence( 'Address %s' ),
			'city' => new WP_UnitTest_Generator_Sequence( 'City %s' ),
		);
	}

	function create_object( $args ) {
		return appointments_insert_appointment( $args );
	}

	function update_object( $worker_id, $fields ) {
		return appointments_update_appointment( $worker_id, $fields );
	}

	function get_object_by_id( $worker_id ) {
		return appointments_get_appointment( $worker_id );
	}
}

class WP_UnitTest_Factory_For_Worker extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'user_email' => new WP_UnitTest_Generator_Sequence( 'worker_%s@email.dev' ),
		);
	}

	function create_object( $args ) {
		$user_args = $this->factory->user->generate_args();
		$user_args['user_email'] = $args['user_email'];
		$worker_id = $this->factory->user->create_object( $user_args );
		$args['ID'] = $worker_id;
		appointments_insert_worker( $args );
		return $worker_id;
	}

	function update_object( $worker_id, $fields ) {
		return appointments_update_worker( $worker_id, $fields );
	}

	function get_object_by_id( $worker_id ) {
		return appointments_get_worker( $worker_id );
	}
}

class WP_UnitTest_Factory_For_Service extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'name' => new WP_UnitTest_Generator_Sequence( 'Service %s' ),
		);
	}

	function create_object( $args ) {
		return appointments_insert_service( $args );
	}

	function update_object( $worker_id, $fields ) {
		return appointments_update_worker( $worker_id, $fields );
	}

	function get_object_by_id( $worker_id ) {
		return appointments_get_worker( $worker_id );
	}
}


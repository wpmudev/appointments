<?php

/**
 * Class App_Addons_Locations_Test
 *
 * @group addons
 * @group addons_additional_fields
 */
class App_Addons_Additional_Fields_Locations_Test extends App_UnitTestCase {
	function setUp() {
		parent::setUp();
		$this->load_addon( 'app-users-additional_fields' );
		$this->__init_addon();
	}

	private function __init_addon() {
		$addon = App_Users_AdditionalFields::serve();
		$addon->initialize();
		$this->addon = $addon;
	}

	function test_init() {
		$this->assertTrue( class_exists( 'App_Users_AdditionalFields' ) );
	}

	/**
	 * Admin edit option is an old retired option for this addon
	 * The addon should keep the setting if it has been set before.
	 * Otherwise it should be always true
	 */
	function test_deprecated_unset_admin_edit_option() {
		$options = appointments_get_options();
		if ( isset( $options['additional_fields-admin_edit'] ) ) {
			unset( $options['additional_fields-admin_edit'] );
			appointments_update_options( $options );
		}

		$this->assertTrue( $this->addon->_are_editable() );
	}

	function test_deprecated_set_to_true_admin_edit_option() {
		$options = appointments_get_options();
		$options['additional_fields-admin_edit'] = 1;
		appointments_update_options( $options );
		$this->assertTrue( $this->addon->_are_editable() );

		$options['additional_fields-admin_edit'] = true;
		appointments_update_options( $options );
		$this->assertTrue( $this->addon->_are_editable() );
	}

	function test_deprecated_set_to_false_admin_edit_option() {
		$options = appointments_get_options();
		$options['additional_fields-admin_edit'] = 0;
		appointments_update_options( $options );
		$this->assertFalse( $this->addon->_are_editable() );

		$options['additional_fields-admin_edit'] = false;
		appointments_update_options( $options );
		$this->assertFalse( $this->addon->_are_editable() );
	}
}
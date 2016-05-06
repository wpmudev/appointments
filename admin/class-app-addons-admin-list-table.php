<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Appointments_Addons_Admin_List_Table extends WP_List_Table {


	function __construct(){
		parent::__construct( array(
			'singular'  => 'addon',
			'plural'    => 'addons',
			'ajax'      => false
		) );
	}

	function column_cb($item){
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'addon',
			$item->addon_file
		);
	}

	function column_name( $item ) {
		return $item->PluginName;
	}

	function column_description( $item ) {
		return $item->Description;
	}

	function get_columns(){
		$columns = array(
			'cb'                    => '<input type="checkbox" />', //Render a checkbox instead of text
			'name' => __( 'Name', 'appointments' ),
			'description' => __( 'Description', 'appointments' )
		);
		return $columns;
	}

	public function prepare_items() {
		$all = glob( APP_PLUGIN_ADDONS_DIR . '/*.php' );
		$addons = array();
		foreach ( $all as $addon_file ) {
			$addon = new Appointments_Addon( $addon_file );
			if ( ! $addon->error ) {
				$addons[ $addon_file ] = $addon;
			}

		}
		$this->items = $addons;

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array($columns, $hidden, $sortable);
	}
}
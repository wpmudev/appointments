<?php

class Appointments_Locations_List_Table extends WP_List_Table {

	function __construct(){
		parent::__construct( array(
			'singular'  => 'location',
			'plural'    => 'locations',
			'ajax'      => false
		) );
	}

	function column_location_address( $item ) {
		$delete_link = add_query_arg( 'action_app', 'save_delete_locations' );
		$delete_link = wp_nonce_url( $delete_link, 'update_app_settings', 'app_nonce' );
		$delete_link = add_query_arg( 'location_id', $item->get_id(), $delete_link );
		$actions = array(
			'edit' => '<a class="edit-location" data-location-name="' . esc_attr( $item->get_admin_label() ) . '" data-location-id="' . $item->get_id() . '" href="#edit-location">' . __( 'Edit', 'appointments' ) . '</a>',
			'delete' => '<span class="trash"><a class="trash delete-location" href="' . $delete_link . '">' . __( 'Delete', 'appointments' ) . '</a></span>'
		);

		return $item->get_admin_label() . $this->row_actions( $actions, true );
	}

	function column_id( $item ) {
		return $item->get_id();
	}

	protected function display_tablenav( $which ) {}

	function get_columns(){
		return array(
			'id' => 'ID',
			'location_address'                 => __( 'Location', 'appointments' ),
		);
	}

	function prepare_items() {
		if ( empty( $this->items ) ) {
			$this->items = array();
		}

		$columns = $this->get_columns();
		$hidden = array( 'id' );
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array($columns, $hidden, $sortable);

		$this->set_pagination_args( array(
			'total_items' => count( $this->items ),
			'per_page'    => 0,
			'total_pages' => 0
		) );
	}

}
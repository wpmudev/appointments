<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Appointments_WP_List_Table_Workers extends WP_List_Table {

	private $currency;
	private $services = array();

	public function __construct() {
		//Set parent defaults
		parent::__construct( array(
			'singular'  => 'worker',
			'plural'    => 'workers',
			'ajax'      => false,
		) );
		$this->currency = appointments_get_option( 'currency' );
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'dummy':
				$is_dummy = $item->is_dummy();
			return sprintf(
				'<span data-state="%d">%s</span>',
				esc_attr( $is_dummy ),
				$is_dummy? esc_html_x( 'Yes', 'dummy worker', 'appointments' ):esc_html_x( 'No', 'dummy worker', 'appointments' )
			);
			case 'price':
			return intval( $item->$column_name );
		}
	}

	public function column_services_provided( $item ) {
		if ( empty( $item->services_provided ) ) {
			return __( 'No Service Providers selected.', 'appointments' );
		}
		$content = '';
		$ids = array();

		foreach ( $item->services_provided as $id ) {
			$value = $this->services[ $id ];
			$name = sprintf( __( 'Missing service: %d.', 'appointments' ), $id );
			if ( is_a( $value, 'Appointments_Service' ) ) {
				$name = $value->name;
				$ids[] = $id;
			}
			$content .= sprintf( '<li>%s</li>', $name );
		}
		return sprintf(
			'<ul data-services="%s">%s</ul>',
			implode( ',', $ids ),
			$content
		);
	}

	public function column_name( $item ) {
		$user = get_user_by( 'id', $item->ID );
		$edit_link = sprintf(
			'<a href="#section-edit-worker" data-id="%s" data-nonce="%s" class="edit">%%s</a>',
			esc_attr( $item->ID ),
			esc_attr( wp_create_nonce( 'worker-'.$item->ID ) )
		);
		$actions = array(
			'ID' => $item->ID,
			'edit'    => sprintf( $edit_link, __( 'Edit', 'appointments' ) ),
			'delete'    => sprintf(
				'<a href="#" data-id="%s" data-nonce="%s" class="delete">%s</a>',
				esc_attr( $item->ID ),
				esc_attr( wp_create_nonce( 'worker-'.$item->ID ) ),
				__( 'Delete', 'appointments' )
			),
		);
		if ( false !== $user && current_user_can( 'edit_users', $user->ID ) ) {
			$actions['user_profile'] = sprintf(
				'<a href="%s">%s</a>',
				get_edit_user_link( $user->ID ),
				esc_html_x( 'Profile', 'user pfofile link on Service Providers screen', 'appointments' )
			);
		}
		$page = $this->get_worker_page_link( $item, false );
		if ( false !== $page ) {
			$actions['page_view'] = $page;
		}
		$value = sprintf( $edit_link, esc_html( false === $user? __( '[wrong user]', 'appointments' ):$user->display_name ) );
		return sprintf( '<strong>%s</strong>%s', $value, $this->row_actions( $actions ) );
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],
			/*$2%s*/ $item->ID
		);
	}

	private function get_worker_page_link( $item, $rich = true ) {
		if ( empty( $item->page ) ) {
			return false;
		}
		$page = get_page( $item->page );
		if ( empty( $page ) ) {
			return false;
		}
		if ( $rich ) {
			return sprintf(
				'%s<div class="row-actions"><a href="%s">%s</a></div>',
				$page->post_title,
				get_page_link( $page->ID ),
				__( 'View page', 'appointments' )
			);
		}
		return sprintf(
			'<a href="%s">%s</a>',
			get_page_link( $page->ID ),
			__( 'View page', 'appointments' )
		);
	}

	public function column_page( $item ) {
		$page = $this->get_worker_page_link( $item );
		if ( empty( $page ) ) {
			return '<span aria-hidden="true">&#8212;</span>';
		}
		return sprintf( '<span data-id="%d">%s</span>', $item->page, $page );
	}

	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
			'name' => __( 'Service Provider', 'appointments' ),
			'dummy' => __( 'Dummy', 'appointments' ),
			'price' => sprintf( __( 'Additional Price (%s)', 'appointments' ), $this->currency ),
			'services_provided' => __( 'Services Provided*', 'appointments' ),
			'page' => __( 'Description page', 'appointments' ),
		);
		return $columns;
	}

	public function get_bulk_actions() {
		$actions = array(
			'delete'    => 'Delete',
		);
		return $actions;
	}

	public function process_bulk_action() {
		$action = $this->current_action();
		$singular = $this->_args['singular'];
		if (
			'delete' === $action
			&& isset( $_POST['_wpnonce'] )
			&& isset( $_POST[ $singular ] )
			&& ! empty( $_POST[ $singular ] )
			&& is_array( $_POST[ $singular ] )
			&& wp_verify_nonce( $_POST['_wpnonce'], 'bulk-'.$this->_args['plural'] )
		) {
			foreach ( $_POST[ $singular ] as $ID ) {
				appointments_delete_worker( $ID );
			}
		}
	}

	public function prepare_items() {
		$per_page = 5;
		$columns = $this->get_columns();
		$hidden = array();

		/**
		 * services
		 */
		$data = appointments_get_services();
		foreach ( $data as $service ) {
			$this->services[ $service->ID ] = $service;
		}
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();
		$data = appointments_get_workers();
		$current_page = $this->get_pagenum();
		$total_items = count( $data );
		$data = appointments_get_workers( array( 'orderby' => 'name' ) );
		$this->items = $data;
		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}
}


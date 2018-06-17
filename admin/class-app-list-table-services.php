<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Appointments_WP_List_Table_Services extends WP_List_Table {

	private $currency;

	public function __construct() {
		//Set parent defaults
		parent::__construct( array(
			'singular'  => 'service',
			'plural'    => 'services',
			'ajax'      => false,
		) );
		$this->currency = appointments_get_option( 'currency' );
	}

	/**
	 * Handle default column
	 *
	 * @since 2.4.0
	 */
	public function column_default( $item, $column_name ) {
		return apply_filters( 'appointments_list_column_'.$column_name, '', $item );
	}

	/**
	 * Column price.
	 *
	 * @since 2.3.1
	 */
	public function column_price( $item ) {
		$value = intval( $item->price );
		if ( empty( $value ) ) {
			return __( 'Free', 'appointments' );
		}
		return $value;
	}

	/**
	 * Column capacity.
	 *
	 * @since 2.3.0
	 */
	public function column_capacity( $item ) {
		if ( 0 === $item->capacity ) {
			return __( 'Limited by number of Service Providers', 'appointments' );
		}
		return $item->capacity;
	}

	/**
	 * Column duration.
	 *
	 * @since 2.3.0
	 */
	public function column_duration( $item ) {
		$label = appointment_convert_minutes_to_human_format( $item->duration );
		return sprintf(
			'<span data-duration="%d">%s</span>',
			esc_attr( $item->duration ),
			esc_html( $label )
		);
	}

	public function column_name( $item ) {
		$edit_link = sprintf(
			'<a href="#section-edit-service" data-id="%s" data-nonce="%s" class="edit">%%s</a>',
			esc_attr( $item->ID ),
			esc_attr( wp_create_nonce( 'service-'.$item->ID ) )
		);
		$actions = array(
			'ID' => $item->ID,
			'edit'    => sprintf( $edit_link, __( 'Edit', 'appointments' ) ),
			'delete'    => sprintf(
				'<a href="#" data-id="%s" data-nonce="%s" class="delete">%s</a>',
				esc_attr( $item->ID ),
				esc_attr( wp_create_nonce( 'service-'.$item->ID ) ),
				__( 'Delete', 'appointments' )
			),
		);
		$page = $this->get_service_page_link( $item, false );
		if ( false !== $page ) {
			$actions['page_view'] = $page;
		}
		$value = sprintf( $edit_link, esc_html( $item->name ) );
		return sprintf( '<strong>%s</strong>%s', $value, $this->row_actions( $actions ) );
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],
			/*$2%s*/ $item->ID
		);
	}

	private function get_service_page_link( $item, $rich = true ) {
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
		$page = $this->get_service_page_link( $item );
		if ( empty( $page ) ) {
			return '<span aria-hidden="true">&#8212;</span>';
		}
		return sprintf( '<span data-id="%d">%s</span>', $item->page, $page );
	}

	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
			'name' => __( 'Name', 'appointments' ),
			'capacity' => __( 'Capacity', 'appointments' ),
			'duration' => __( 'Duration', 'appointments' ),
			'price' => sprintf( __( 'Price (%s)', 'appointments' ), $this->currency ),
			'page' => __( 'Description page', 'appointments' ),
        );
        /**
         * Allow to filter columns
         *
         * @since 2.4.0
         */
		return apply_filters( 'manage_appointments_service_columns', $columns );
	}

	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'appointments' ),
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
				appointments_delete_service( $ID );
			}
		}
	}

	public function prepare_items() {
		$per_page = $this->get_items_per_page( 'app_services_per_page', 20 );;
		$columns = $this->get_columns();
		$hidden = get_hidden_columns( $this->screen );
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();
		$current_page = $this->get_pagenum();
		$total_items = appointments_get_services( array( 'count' => true ) );
		$args = array(
			'orderby' => 'name',
			'paged' => $this->get_pagenum() - 1,
			'limit' => $per_page,
		);
		$data = appointments_get_services( $args );
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


<?php

include_once( 'class-app-wp-list-table.php' );

class Appointments_Admin_Appointments_List_Table extends Appointments_WP_List_Table {

	public $filter_service_id = '';
	public $filter_worker_id = '';
	public $filter_status = '';
	public $filter_type = '';

	public function __construct( $args ) {
		parent::__construct( $args );

		add_filter( 'manage_toplevel_page_appointments_columns', array( $this, 'add_columns' ) );
		add_filter( 'manage_toplevel_page_appointments_sortable_columns', array( $this, 'get_sortable_columns' ) );
	}

	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			$service_id = $this->get_filter( 'service_id' );
			$worker_id = $this->get_filter( 'worker_id' );
			$appointments = appointments();
			$services = appointments_get_services();
			$workers = appointments_get_workers();
			?>
			<div class="alignleft action">
				<label for="app_service_id" class="screen-reader-text"><?php _e( 'Filter by service', 'appointments' ); ?></label>
				<select id="app_service_id" name="app_service_id">
					<option value=""><?php _e('Filter by service','appointments'); ?></option>
					<?php foreach ( $services as $service ): ?>
						<option value="<?php echo esc_attr( $service->ID ); ?>" <?php selected( $service_id, $service->ID ); ?>><?php echo $appointments->get_service_name( $service->ID ); ?></option>
					<?php endforeach; ?>
				</select>

				<label for="app_provider_id" class="screen-reader-text"><?php _e( 'Filter by service provider', 'appointments' ); ?></label>
				<select id="app_provider_id" name="app_provider_id">
					<option value=""><?php _e('Filter by service provider','appointments'); ?></option>
					<?php foreach ( $workers as $worker ): ?>
						<option value="<?php echo esc_attr( $worker->ID ); ?>" <?php selected( $worker_id, $worker->ID ); ?>><?php echo appointments_get_worker_name( $worker->ID ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( __( 'Filter' ), 'button', 'filter_action', false, array( 'id' => 'app-submit' ) ); ?>
			</div>
			<?php
		}

	}

	public function get_bulk_actions() {
		// Falta bulk action change
		/**<label class="screen-reader-text" for="app_new_status"><?php _e('Bulk status change','appointments'); ?></label>
		<select name="app_new_status" id="app_new_status">
			<option value=""><?php _e('Bulk status change','appointments'); ?></option>
			<?php foreach ( appointments_get_statuses() as $value=>$name ) {
				echo '<option value="' . esc_attr($value) . '" class="hide-if-no-js">'.$name.'</option>';
			} ?>
		</select>
		<input type="submit" class="button app-change-status-btn" value="<?php _e('Change Status','appointments'); ?>" />**/
		return array(
			'remove' => __( 'Remove')
		);
	}

	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'app_id' => __('ID','appointments'),
			'date' => __('Date/Time','appointments'),
			'user' => __('Client','appointments'),
			'service' => __('Service','appointments'),
			'worker' => __('Provider','appointments'),
			'status' => __('Status','appointments')
		);
		$columns = apply_filters( 'appointments_my_appointments_list_columns', $columns );
		return $columns;
	}

	protected function get_column_info() {
		$columns = $this->get_columns();
		$hidden = get_hidden_columns( $this->screen );

		$sortable_columns = $this->get_sortable_columns();
		/**
		 * Filter the list table sortable columns for a specific screen.
		 *
		 * The dynamic portion of the hook name, `$this->screen->id`, refers
		 * to the ID of the current screen, usually a string.
		 *
		 * @since 3.5.0
		 *
		 * @param array $sortable_columns An array of sortable columns.
		 */
		$_sortable = apply_filters( "manage_{$this->screen->id}_sortable_columns", $sortable_columns );

		$sortable = array();
		foreach ( $_sortable as $id => $data ) {
			if ( empty( $data ) )
				continue;

			$data = (array) $data;
			if ( !isset( $data[1] ) )
				$data[1] = false;

			$sortable[$id] = $data;
		}

		$primary = $this->get_primary_column_name();
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );

		return $this->_column_headers;
	}

	public function get_sortable_columns() {
		return array(
			'app_id' => array( 'ID', false ),
			'date' => array( 'start', false )
		);
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="appointments[]" value="%d" />',
			$item->ID
		);
	}

	function column_app_id( $item ) {
		return $item->ID;
	}

	function column_user( $item ) {
		$appointments = appointments();
		return stripslashes( $appointments->get_client_name( $item->ID ) );
	}

	function column_date( $item ) {
		return mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item->start );
	}

	function column_service( $item ) {
		$appointments = appointments();
		return $appointments->get_service_name( $item->service );
	}

	function column_worker( $item ) {
		return appointments_get_worker_name( $item->worker );
	}

	function column_status( $item ) {
		$status = __('None yet','appointments');
		if ( ! empty( $item->status ) ) {
			$statuses = appointments_get_statuses();
			if ( isset( $statuses[ $item->status ] ) ) {
				$status = $statuses[ $item->status ];
			}
		}

		return $status;
	}

	function column_default( $item, $column_name ) {
		$content = apply_filters( 'appointments_list_column_' . $column_name, '', $item );
		return $content;
	}

	public function set_filter( $filter, $value ) {
		$key = 'filter_' . $filter;
		if ( isset( $this->$key ) ) {
			$this->$key = $value;
		}
	}

	public function get_filter( $filter ) {
		$key = 'filter_' . $filter;
		if ( isset( $this->$key ) ) {
			return $this->$key;
		}

		return '';
	}

	public function search_box( $text, $input_id ) {
		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) )
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		if ( ! empty( $_REQUEST['order'] ) )
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		?>
		<p class="search-box">
			<input type="hidden" value="<?php echo $this->get_filter( 'type' ); ?>" name="type" />
			<input type="hidden" value="<?php echo $this->get_filter( 'service_id' ); ?>" name="app_service_id" />
			<input type="hidden" value="<?php echo $this->get_filter( 'worker_id' ); ?>" name="app_provider_id" />
			<label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
			<input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>" />
			<?php submit_button( $text, 'button', '', false, array('id' => 'search-submit') ); ?>
		</p>
		<?php
	}



	public function prepare_items() {

		$per_page     = $this->get_items_per_page( 'appointments_per_page', 50 );
		$current_page = $this->get_pagenum();

		$args = array(
			'per_page' => $per_page,
			'page' => $current_page,
		);

		if ( isset( $_GET['orderby'] ) ) {
 			$args['orderby'] = $_GET['orderby'];
		} else {
			$args['orderby'] = 'ID';
		}

		if ( isset( $_GET['order'] ) && 'asc' === $_GET['order'] ) {
			 $args['order'] = 'ASC';
		} else {
			 $args['order'] = 'DESC';
		}


		$status = $this->get_filter( 'status' );
		if ( $status ) {
			$args['status'] = $status;
		}

		if ( $this->get_filter( 'worker_id' ) ) {
			$args['worker'] = $this->get_filter( 'worker_id' );
		}

		if ( $this->get_filter( 'service_id' ) ) {
			$args['service'] = $this->get_filter( 'service_id' );
		}

		$apps = appointments_get_appointments( $args );
		$args['count'] = true;
		$total_items = appointments_get_appointments( $args );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page
		));

		$this->items = $apps;
	}
}
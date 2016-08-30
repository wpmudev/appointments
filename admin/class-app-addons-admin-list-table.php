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
			$item->slug
		);
	}

	function column_name( $item ) {
		$actions = array();
		if ( strtolower( $item->Free ) === 'true' ) {
			$actions['activate'] = '<a href="http://premium.wpmudev.org/project/appointments-plus/">' . __( 'Upgrade to Appointments+ to activate', 'appointments' ) . '</a>';
		}
		else {
			if ( ! $item->active ) {
				$link = add_query_arg( array( 'action' => 'activate', 'addon' => $item->slug ) );
				$link = wp_nonce_url( $link, 'activate-addon' );
				$actions['activate'] = '<a href="' . $link . '">' . __( 'Activate', 'appointments' ) . '</a>';
			}
			else {
				$link = add_query_arg( array( 'action' => 'deactivate', 'addon' => $item->slug ) );
				$link = wp_nonce_url( $link, 'deactivate-addon' );
				$actions['deactivate'] = '<a href="' . $link . '">' . __( 'Deactivate', 'appointments' ) . '</a>';
			}
		}

		$name = $item->active ? '<strong>' . $item->PluginName . '</strong>' : $item->PluginName;
		return $name . $this->row_actions( $actions );

	}

	function column_description( $item ) {
		ob_start();
		?>
			<p><?php echo $item->Description; ?></p>
			<div>
				<?php printf( __( 'Version: %s', 'appointments'), $item->Version ); ?> |
				<?php printf( __( 'by %s', 'appointments' ), '<a href="' . esc_url( $item->PluginURI ) . '">' . $item->Author . '</a>' ); ?>
				<?php if ( $item->Requires ): ?>
					<p><?php printf( __( '<strong>Requires</strong>: %s', 'appointments' ), implode( ', ', $item->Requires ) ); ?></p>
				<?php endif; ?>
			</div>
		<?php
		return ob_get_clean();
	}

	function get_columns(){
		$columns = array(
			'cb'                    => '<input type="checkbox" />', //Render a checkbox instead of text
			'name' => __( 'Name', 'appointments' ),
			'description' => __( 'Description', 'appointments' )
		);
		return $columns;
	}

	public function single_row( $item ) {
		$class = $item->active ? 'active' : 'inactive';
		echo '<tr class="' . $class . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	function get_bulk_actions() {
		$actions = array(
			'activate'    => __( 'Activate', 'appointments' ),
			'deactivate'    => __( 'Deactivate', 'appointments' )
		);
		return $actions;
	}

	public function prepare_items() {
		$appointments = appointments();
		$addons = $appointments->addons_loader->get_addons();
		uasort( $addons, array( $this, '_sort_addons' ) );
		$this->items = $addons;

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array($columns, $hidden, $sortable);
	}

	private function _sort_addons( $a, $b ) {
		if ( $a->PluginName === $b->PluginName ) {
			return 0;
		}
		return ( $a->PluginName < $b->PluginName ) ? -1 : 1;
	}
}
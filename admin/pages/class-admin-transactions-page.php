<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Appointments_Admin_Transactions_Page' ) ) {
	class Appointments_Admin_Transactions_Page {

		public function __construct() {
			$this->page_id = add_submenu_page(
				'appointments',
				__('Transactions','appointments'),
				__('Transactions','appointments'),
				App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_TRANSACTIONS),
				"app_transactions",
				array( $this, 'transactions' )
			);
		}

		public function transactions () {
			global $type;

			$appointments = appointments();

			wp_reset_vars( array( 'type' ) );

			if ( empty( $type ) ) {
				$type = 'past';
			}

			$paged = empty( $_GET['paged'] ) ? 1 : absint( $_GET['paged'] );

			$startat = ( $paged - 1 ) * 50;

			$transactions = appointments_get_transactions( array( 'type' => $type, 'offset' => $startat, 'per_page' => 50 ) );
			$total        = appointments_get_transactions(
				array(
					'type'     => $type,
					'offset'   => $startat,
					'per_page' => 50,
					'count'    => true
				)
			);

			$columns = array();

			$columns['subscription'] = __( 'App ID', 'appointments' );
			$columns['user']         = __( 'User', 'appointments' );
			$columns['date']         = __( 'Date/Time', 'appointments' );
			$columns['service']      = __( 'Service', 'appointments' );
			$columns['amount']       = __( 'Amount', 'appointments' );
			$columns['transid']      = __( 'Transaction id', 'appointments' );
			$columns['status']       = __( 'Status', 'appointments' );

			$trans_navigation = paginate_links( array(
				'base'    => add_query_arg( 'paged', '%#%' ),
				'format'  => '',
				'total'   => ceil( $total / 50 ),
				'current' => $paged
			) );

			$file = _appointments_get_view_path( 'page-transactions' );
			if ( $file ) {
				include( $file );
			}
		}

	}
}

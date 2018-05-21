<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'App_Shortcode_All_Appointments' ) ) {
	/**
	 * All appointments list.
	 */
	class App_Shortcode_All_Appointments extends App_Shortcode {
		public function __construct() {
			$this->name      = __( 'All Appointments', 'appointments' );
		}

		public function get_defaults() {
			return array(
				'title'        => array(
					'type'  => 'text',
					'name'  => __( 'Title', 'appointments' ),
					'value' => sprintf( '<h3>%s</h3>', esc_html__( 'All Appointments', 'appointments' ) ),
					'help'  => __( 'Title text.', 'appointments' ),
				),
				'status'       => array(
					'type'  => 'text',
					'name'  => __( 'Status', 'appointments' ),
					'value' => 'paid,confirmed',
					'help'  => __( 'Which status(es) will be included. Possible values: paid, confirmed, completed, pending, removed, reserved or combinations of them separated with comma.', 'appointments' ),
				),
				'order_by'     => array(
					'type'  => 'text',
					'name'  => __( 'Order by', 'appointments' ),
					'value' => 'start',
					'help'  => __( 'Sort order of the appointments. Possible values: ID, start. Optionally DESC (descending) can be used, e.g. "start DESC" will reverse the order. Default: "start". Note: This is the sort order as page loads. Table can be dynamically sorted by any field from front end (Some date formats may not be sorted correctly).', 'appointments' ),
				),
				'_tablesorter' => array(
					'value' => 1,
				),
				'public' => array(
					'value' => 0,
					'help' => __('Allow visitors to view list, default is 0, only logged in users can view list.', 'appointments'),
					'example' => '1',
				)
			);
		}

		public function get_usage_info() {
			return __( 'Inserts a table that displays all upcoming appointments.', 'appointments' );
		}

		public function process_shortcode( $args = array(), $content = '' ) {
			global $appointments;
			extract( wp_parse_args( $args, $this->_defaults_to_args() ) );

			if ( ! $public && ! apply_filters( 'app_all_appointments_shortcode_public', is_user_logged_in() ) ) {
				return '';
			}

			$statuses = explode( ',', $status );

			if ( ! is_array( $statuses ) || empty( $statuses ) ) {
				return '';
			}

			if ( ! trim( $order_by ) ) {
				$order_by = 'start';
			}


			$query_args = array(
				'status'  => $statuses,
				'orderby' => $order_by
			);
			$results    = appointments_get_appointments( $query_args );

			$ret = '';
			$ret .= '<div class="appointments-all-appointments">';
			$ret .= $title;
			$ret = apply_filters( 'app_all_appointments_before_table', $ret );
			$ret .= '<table class="all-appointments tablesorter"><thead>';
			$ret .= apply_filters( 'app_all_appointments_column_name',
				'<th class="all-appointments-service">' . __( 'Service', 'appointments' )
				. '</th><th class="all-appointments-provider">' . __( 'Provider', 'appointments' )
				. '</th><th class="all-appointments-client">' . __( 'Client', 'appointments' )
				. '</th><th class="all-appointments-date">' . __( 'Date/time', 'appointments' )
				. '</th><th class="all-appointments-status">' . __( 'Status', 'appointments' ) . '</th>'
			);
			$colspan = substr_count($ret, '<th');

			$ret .= '</thead><tbody>';

			if ( $results ) {
				foreach ( $results as $r ) {
					$ret .= '<tr><td>';
					$ret .= $appointments->get_service_name( $r->service ) . '</td>';
					$ret .= apply_filters( 'app-shortcode-all_appointments-after_service', '', $r );

					$ret .= '<td>';
					$ret .= appointments_get_worker_name( $r->worker ) . '</td>';
					$ret .= apply_filters( 'app-shortcode-all_appointments-after_provider', '', $r );

					$ret .= '<td>';
					$ret .= $appointments->get_client_name( $r->ID ) . '</td>';
					$ret .= apply_filters( 'app-shortcode-all_appointments-after_client', '', $r );

					$ret .= '<td>';
					$ret .= date_i18n( $appointments->datetime_format, strtotime( $r->start ) ) . '</td>';
					$ret .= apply_filters( 'app-shortcode-all_appointments-after_date', '', $r );

					$ret .= '<td>';
					$ret .= App_Template::get_status_name( $r->status );
					$ret .= '</td>';
					$ret .= apply_filters( 'app-shortcode-all_appointments-after_status', '', $r );

					$ret .= apply_filters( 'app_all_appointments_add_cell', '', $r );
					$ret .= '</tr>';
				}
			} else {
				$ret .= '<tr><td colspan="' . $colspan . '">' . __( 'No appointments', 'appointments' ) . '</td></tr>';
			}

			$ret .= '</tbody></table>';
			$ret = apply_filters( 'app_all_appointments_after_table', $ret, $results );

			$ret .= '</div>';

			$sorter     = 'usLongDate';
			$dateformat = 'us';
			// Search for formats where day is at the beginning
			if ( stripos( str_replace( array( '/', '-' ), '', $appointments->date_format ), 'dmY' ) !== false ) {
				$sorter     = 'shortDate';
				$dateformat = 'uk';
			}

			// Sort table from front end
			if ( $_tablesorter && file_exists( appointments_plugin_dir() . 'js/jquery.tablesorter.min.js' ) ) {
				$appointments->add2footer( '
				$(".all-appointments").tablesorter({
					dateFormat: "' . $dateformat . '",
					headers: {
						2: {
							sorter:"' . $sorter . '"
						}
					}
				});
				$("th.all-appointments-gcal,th.all-appointments-confirm,th.all-appointments-cancel").removeClass("header");'
				);
			}

			return $ret;
		}
	}
}

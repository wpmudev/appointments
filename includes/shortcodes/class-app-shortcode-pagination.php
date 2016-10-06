<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'App_Shortcode_Pagination' ) ) {
	/**
	 * Pagination shortcode.
	 */
	class App_Shortcode_Pagination extends App_Shortcode {

		public function __construct() {
			$this->name = __( 'Appointments Pagination', 'appointments' );
			$this->_defaults = array(
				'step'    => array(
					'type' => 'text',
					'name' => __( 'Weeks/Months No.', 'appointments' ),
					'value'   => 1,
					'help'    => __( 'Number of weeks or months that selected time will increase or decrease with each next or previous link click. You may consider entering 4 if you have 4 schedule tables on the page.', 'appointments' ),
				),
				'month'   => array(
					'type' => 'checkbox',
					'name' => __( 'Month', 'appointments' ),
					'value'   => 0,
					'help'    => __( 'If checked, step parameter will mean month, otherwise week. In short, check for monthly schedule.', 'appointments' ),
				),
				'date'    => array(
					'type' => 'checkbox',
					'name' => __( 'Date', 'appointments' ),
					'value'   => 0,
					'help'    => __( 'This is only required if this shortcode resides above any schedule shortcodes. Otherwise it will follow date settings of the schedule shortcodes. Default: Current week or month', 'appointments' ),
				),
				'anchors' => array(
					'type' => 'checkbox',
					'name' => __( 'Anchors', 'appointments' ),
					'value'   => 1,
					'help'    => __( 'Unchecking this argument to will prevent pagination links from adding schedule hash anchors.', 'appointments' ),
				),
			);
		}

		public function get_usage_info() {
			return __( 'Inserts pagination codes (previous, next week or month links) and Legend area.', 'appointments' );
		}

		public function process_shortcode( $args = array(), $content = '' ) {
			global $appointments;
			extract( wp_parse_args( $args, $this->_defaults_to_args() ) );

			// Force a date
			if ( $date && ! isset( $_GET["wcalendar"] ) ) {
				$time              = strtotime( $date, $appointments->local_time );
				$_GET["wcalendar"] = $time;
			} else {
				if ( isset( $_GET["wcalendar"] ) && (int) $_GET['wcalendar'] ) {
					$time = (int) $_GET["wcalendar"];
				} else {
					$time = $appointments->local_time;
				}
			}

			$c      = '';
			$script = '';
			// Legends
			if ( isset( $appointments->options['show_legend'] ) && 'yes' == $appointments->options['show_legend'] ) {
				$c .= '<div class="appointments-legend">';
				$c .= '<table class="appointments-legend-table">';
				$n = 0;
				$c .= '<tr>';
				foreach ( $appointments->get_classes() as $class => $name ) {
					$c .= '<td class="class-name">' . $name . '</td>';
					$c .= '<td class="' . $class . '">&nbsp;</td>';
					$n ++;
					if ( 3 == $n ) {
						$c .= '</tr><tr>';
					}
				}
				$c .= '</tr>';
				$c .= '</table>';
				$c .= '</div>';
				// Do not let clicking box inside legend area
				$script .= '$("table.appointments-legend-table td.free").click(false);';
			}

			// Pagination
			$c .= '<div class="appointments-pagination">';
			if ( ! $month ) {
				$prev                = $time - ( $step * 7 * 86400 );
				$next                = $time + ( $step * 7 * 86400 );
				$prev_min            = $appointments->local_time - $step * 7 * 86400;
				$next_max            = $appointments->local_time + ( $appointments->get_app_limit() + 7 * $step ) * 86400;
				$month_week_next     = __( 'Next Week', 'appointments' );
				$month_week_previous = __( 'Previous Week', 'appointments' );
			} else {
				$prev                = $appointments->first_of_month( $time, - 1 * $step );
				$next                = $appointments->first_of_month( $time, $step );
				$prev_min            = $appointments->first_of_month( $appointments->local_time, - 1 * $step );
				$next_max            = $appointments->first_of_month( $appointments->local_time, $step ) + $appointments->get_app_limit() * 86400;
				$month_week_next     = __( 'Next Month', 'appointments' );
				$month_week_previous = __( 'Previous Month', 'appointments' );
			}

			$hash = ! empty( $anchors ) && (int) $anchors
				? '#app_schedule'
				: '';

			if ( $prev > $prev_min ) {
				$c .= '<div class="previous">';
				$c .= '<a href="' . add_query_arg( "wcalendar", $prev ) . $hash . '">&laquo; ' . $month_week_previous . '</a>';
				$c .= '</div>';
			}
			if ( $next < $next_max ) {
				$c .= '<div class="next">';
				$c .= '<a href="' . add_query_arg( "wcalendar", $next ) . $hash . '">' . $month_week_next . ' &raquo;</a>';
				$c .= '</div>';
			}
			$c .= '<div style="clear:both"></div>';
			$c .= '</div>';

			$appointments->add2footer( $script );

			return $c;
		}
	}
}

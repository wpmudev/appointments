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
		}

		public function get_defaults() {
			return array(
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
					'type' => 'datepicker',
					'name' => __( 'Date (YYYY-MM-DD format)', 'appointments' ),
					'value'   => '',
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

			$options      = appointments_get_options();
			$current_time = current_time( 'timestamp' );
			$args         = wp_parse_args( $args, $this->_defaults_to_args() );

			// Force a date
			if ( $args['date'] && ! isset( $_GET["wcalendar"] ) ) {
				$time              = strtotime( $args['date'], $current_time );
				$_GET["wcalendar"] = $time;
			} else {
				if ( isset( $_GET["wcalendar"] ) && (int) $_GET['wcalendar'] ) {
					$time = (int) $_GET["wcalendar"];
				} else {
					$time = $current_time;
				}
			}

			if ( ! $args['month'] ) {
				$prev                = $time - ( $args['step'] * 7 * 86400 );
				$next                = $time + ( $args['step'] * 7 * 86400 );
				$prev_min            = $current_time - $args['step'] * 7 * 86400;
				$next_max            = $current_time + ( $appointments->get_app_limit() + 7 * $args['step'] ) * 86400;
				$month_week_next     = __( 'Next Week', 'appointments' );
				$month_week_previous = __( 'Previous Week', 'appointments' );
			} else {
				$prev                = $appointments->first_of_month( $time, - 1 * $args['step'] );
				$next                = $appointments->first_of_month( $time, $args['step'] );
				$prev_min            = $appointments->first_of_month( $current_time, - 1 * $args['step'] );
				$next_max            = $appointments->first_of_month( $current_time, $args['step'] ) + $appointments->get_app_limit() * 86400;
				$month_week_next     = __( 'Next Month', 'appointments' );
				$month_week_previous = __( 'Previous Month', 'appointments' );
			}

			$hash = ! empty( $args['anchors'] ) && (int) $args['anchors']
				? '#app_schedule'
				: '';

			ob_start();

			// Legends
			if ( 'yes' == $options['show_legend'] ) {
				$n = 0;
				?>
				<div class="appointments-legend">
					<table class="appointments-legend-table">
						<tr>
							<?php foreach ( $appointments->get_classes() as $class => $name ): ?>
								<td class="class-name"><?php echo esc_html( $name ); ?></td>
								<td class="<?php echo esc_attr( $class ); ?>">&nbsp;</td>
								<?php $n ++; ?>
								<?php if ( 0 === $n % 3 ): ?>
									</tr>
									<tr>
								<?php endif; ?>
							<?php endforeach; ?>
						</tr>
					</table>
				</div>

				<?php
				// Do not let clicking box inside legend area
				$appointments->add2footer( '$("table.appointments-legend-table td.free").click(false);' );
			}

			// Pagination
			?>
			<div class="appointments-pagination">
				<?php if ( $prev > $prev_min ): ?>
					<div class="<?php echo apply_filters( 'appointments_pagination_shortcode_previous_class', 'previous' ); ?>">
						<a href="<?php echo esc_url( add_query_arg( "wcalendar", $prev ) . $hash ); ?>">&laquo; <?php echo $month_week_previous; ?></a>
					</div>
				<?php endif; ?>
				<?php if ( $next < $next_max ): ?>
					<div class="<?php echo apply_filters( 'appointments_pagination_shortcode_next_class', 'next' ); ?>">
						<a href="<?php echo esc_url( add_query_arg( "wcalendar", $next ) . $hash ); ?>"><?php echo $month_week_next; ?> &raquo;</a>
					</div>
				<?php endif; ?>
				<div style="clear:both"></div>
			</div>
			<?php



			return ob_get_clean();
		}
	}
}

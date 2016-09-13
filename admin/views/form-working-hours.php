<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$_required_format = "H:i";
$appointments = appointments();
$appointments->get_lsw();
$options = appointments_get_options();

if ( $options["admin_min_time"] ) {
	$min_time = $options["admin_min_time"];
} else {
	$min_time = $appointments->get_min_time();
}
$min_secs = 60 * apply_filters( 'app_admin_min_time', $min_time );

/** @var string $status */
$wb = appointments_get_worker_working_hours( $status, $appointments->worker, $appointments->location );
if ( ! $wb ) {
	$whours = array();
}
else {
	$whours = $wb->hours;
}
?>

<table class="app-working_hours-workhour_form">
	<tr>
		<th><?php _e('Day', 'appointments'); ?></th>
		<?php if ( 'open' == $status ): ?>
			<th><?php _e('Work?', 'appointments' ); ?></th>
		<?php else: ?>
			<th><?php _e('Give break?','appointments'); ?></th>
		<?php endif; ?>
		<th><?php _e('Start', 'appointments'); ?></th>
		<th><?php _e('End', 'appointments'); ?></th>
	</tr>

	<?php foreach ( $appointments->weekdays() as $day_label => $day ): ?>
		<?php if ( ! empty( $whours[ $day ]['active'] ) && is_array( $whours[ $day ]['active'] ) ): ?>
			<!--  multiple breaks for today. -->
			<?php foreach ($whours[$day]['active'] as $idx => $active): ?>
				<tr class="<?php echo $idx > 0 ? 'app-repeated' : ''; ?>">
					<td>
						<?php echo 0 == $idx ? $day_label : ''; ?>
					</td>
					<td>
						<label for="<?php echo "{$status}[{$day}][active][{$idx}]"; ?>" class="screen-reader-text"><?php printf( __( 'Work on %s?', 'appointments' ), $day_label ); ?></label>
						<select id="<?php echo "{$status}[{$day}][active][{$idx}]"; ?>" name="<?php echo "{$status}[{$day}][active][{$idx}]"; ?>" autocomplete="off">
							<option value="no"><?php _e('No', 'appointments'); ?></option>
							<option value="yes" <?php selected( 'yes' == $active ); ?>><?php _e('Yes', 'appointments'); ?></option>
						</select>
					</td>
					<td>
						<?php
							$selected = isset( $whours[ $day ]['start'][ $idx ] ) ? strtotime( $whours[ $day ]['start'][ $idx ] ) : '';
							echo $appointments->_time_selector( "{$status}[{$day}][start][{$idx}]", $min_secs, $selected );
						?>
					</td>
					<td>
						<?php
						$selected = isset( $whours[ $day ]['end'][ $idx ] ) ? strtotime( $whours[ $day ]['end'][ $idx ] ) : '';
						echo $appointments->_time_selector( "{$status}[{$day}][end][{$idx}]", $min_secs, $selected );
						?>
						<?php if ( 'closed' == $status && $idx == 0 && 'yes' == $active ): ?>
							&nbsp;
							<a href="#add_break" class="app-add_break" title="<?php echo esc_attr( __( 'Add break', 'appointments' ) ); ?>">
								<span><?php _e( 'Add break', 'appointments' ); ?></span>
							</a>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php else: ?>
			<tr>
				<td><?php echo $day_label; ?></td>
				<td>
					<label for="<?php echo "{$status}[{$day}][active]"; ?>" class="screen-reader-text"><?php printf( __( 'Work on %s?', 'appointments' ), $day_label ); ?></label>
					<select id="<?php echo "{$status}[{$day}][active]"; ?>" name="<?php echo "{$status}[{$day}][active]"; ?>" autocomplete="off">
						<option value="no"><?php _e('No', 'appointments'); ?></option>
						<option value="yes" <?php selected( isset( $whours[ $day ]['active'] ) && 'yes' == $whours[ $day ]['active'] ); ?>><?php _e('Yes', 'appointments'); ?></option>
					</select>
				</td>
				<td>
					<?php
						$selected = isset( $whours[ $day ]['start'] ) ? strtotime( $whours[ $day ]['start'] ) : '';
						echo $appointments->_time_selector( "{$status}[{$day}][start]", $min_secs, $selected );
					?>
				</td>
				<td>
					<?php
						$selected = isset( $whours[ $day ]['end'] ) ? strtotime( $whours[ $day ]['end'] ) : '';
						echo $appointments->_time_selector( "{$status}[{$day}][end]", $min_secs, $selected );
					?>
					<?php if ( 'closed' == $status && isset( $whours[ $day ]['active'] ) && 'yes' == $whours[ $day ]['active'] ): ?>
						&nbsp;
						<a href="#add_break" class="app-add_break" title="<?php echo esc_attr( __( 'Add break', 'appointments' ) ); ?>">
							<span><?php _e( 'Add break', 'appointments' ); ?></span>
						</a>
					<?php endif; ?>
				</td>
			</tr>
		<?php endif; ?>
	<?php endforeach; ?>
</table>
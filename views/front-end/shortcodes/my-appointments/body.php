<div class="appointments-my-appointments">
<?php if ( $_can_display_editable ) { ?>
    <form method="post">
<?php } ?>
<?php
echo $title;
echo apply_filters( 'app_my_appointments_before_table', '' );
?>
<table class="my-appointments tablesorter">
    <thead>
<?php
$colspan = 4;
echo apply_filters( 'app_my_appointments_column_name',
	'<th class="my-appointments-service">'. __( 'Service', 'appointments' )
			. '</th><th class="my-appointments-worker">' . $provider_or_client
			. '</th><th class="my-appointments-date">' . __( 'Date/time', 'appointments' )
. '</th><th class="my-appointments-status">' . __( 'Status', 'appointments' ) . '</th>' );
if ( $allow_confirm ) { ?>
    <th class="my-appointments-confirm"><?php _e( 'Confirm', 'appointments' ); ?></th>
<?php } ?>
<?php if ( $a_cancel ) {
	$colspan++;
?>
    <th class="my-appointments-cancel">'. _x( 'Cancel', 'Discard existing info', 'appointments' ) . '</th>';
<?php } ?>
<?php if ( $gcal && 'yes' == $options['gcal'] ) {
	$colspan++;
?>
<th class="my-appointments-gcal">&nbsp;</th>
<?php } ?>
    </thead>
    <tbody>
<?php
if ( $results ) {
	$show_submit_confirm_button = false;
	foreach ( $results as $r ) { ?>
<tr>
    <td><?php echo $appointments->get_service_name( $r->service ); ?></td>
    <?php echo apply_filters( 'app-shortcode-my_appointments-after_service', '', $r ); ?>
    <td><?php
	if ( $provider ) {
			echo $appointments->get_client_name( $r->ID );
	} else {
		echo appointments_get_worker_name( $r->worker );
	}
?></td>
<?php echo apply_filters( 'app-shortcode-my_appointments-after_worker', '', $r ); ?>
<td><?php echo date_i18n( $appointments->datetime_format, strtotime( $r->start ) ); ?></td>
<?php echo apply_filters( 'app-shortcode-my_appointments-after_date', '', $r ); ?>
<td><?php echo App_Template::get_status_name( $r->status );
if ( 'pending' == $r->status ) {
			$r->service_name = $appointments->get_service_name( $r->service );
	if ( $payment_required ) {
		?><input type="submit" data-appointment="<?php echo htmlspecialchars( json_encode( $r ), ENT_QUOTES ); ?>" value="<?php esc_attr_e( 'Pay', 'appointments' ); ?>" class="appointments-paid-button"><?php
	}
}
?></td>
<?php echo apply_filters( 'app-shortcode-my_appointments-after_status', '', $r ); ?>
<?php
// If allowed so, a worker can confirm an appointment himself
if ( $allow_confirm ) {
?>
<td>
<?php
$is_readonly = '';
if ( 'pending' != $r->status ) {
	$is_readonly = ' readonly="readonly" disabled="disabled"';
}
if ( 'confirmed' === $r->status ) {
	echo '-';
} else {
			?><input class="app-my-appointments-confirm" type="checkbox" name="app_confirm[<?php echo esc_attr( $r->ID ); ?>]" '<?php echo $is_readonly; ?> /><?php
	$show_submit_confirm_button = true;
} ?></td><?php
}
// If allowed so, a client can cancel an appointment
if ( $a_cancel ) {
	// We don't want completed appointments to be cancelled
	$stat = $r->status;
	$in_allowed_stat = apply_filters( 'app_cancel_allowed_status', ('pending' == $stat || 'confirmed' == $stat || 'paid' == $stat), $stat, $r->ID );
	if ( $in_allowed_stat ) {
		$is_readonly = '';
	} else {
		$is_readonly = ' readonly="readonly"';
	}
?>
<td><input id="cancel-'<?php echo esc_attr( $r->ID ); ?>" data-app-id="<?php echo esc_attr( $r->ID ); ?>" class="app-my-appointments-cancel" type="checkbox" name="app_cancel[<?php echo esc_attr( $r->ID ); ?>]" <?php echo $is_readonly; ?> /></td>
<?php
}

if ( $gcal && 'yes' == $appointments->options['gcal'] ) {
	if ( isset( $appointments->options['gcal_same_window'] ) && $appointments->options['gcal_same_window'] ) {
		$target = '_self';
	} else {
		$target = '_blank';
	}
?>
<td><a title="<?php esc_attr_e( 'Click to submit this appointment to your Google Calendar account','appointments' ); ?>" href="<?php echo esc_url( $appointments->gcal( $r->service, strtotime( $r->start, $appointments->local_time ), strtotime( $r->end, $appointments->local_time ), true, $r->address, $r->city ) ); ?>" target="<?php echo esc_attr( $target ); ?>"><?php echo $appointments->gcal_image; ?></a></td>
<?php } ?>
<?php echo apply_filters( 'app_my_appointments_add_cell', '', $r ); ?>
</tr>
<?php
	}
} else { ?>
            <tr><td colspan="<?php echo esc_attr( $colspan ); ?>"><?php _e( 'No appointments','appointments' ); ?></td></tr>
<?php } ?>
    </tbody>
</table>
<?php
if ( $_can_display_editable ) {
	if ( $show_submit_confirm_button && $_can_display_editable_confirm ) {
?>
<div class="submit">
<input type="submit" name="app_bp_settings_submit" value="<?php esc_attr_e( 'Submit Confirm', 'appointments' ); ?> " class="auto">
<input type="hidden" name="app_bp_settings_user" value="<?php echo esc_attr( $bp->displayed_user->id ); ?>">
<?php wp_nonce_field( 'app_bp_settings_submit', 'app_bp_settings_submit', true ); ?>
</div>
<?php } ?>
</form>
<?php } ?>
</div>


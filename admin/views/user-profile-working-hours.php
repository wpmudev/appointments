<h3><?php esc_html_e( 'Appointments +: My Working Hours', 'appointments' ); ?></h3>
<table class="form-table">
	<tr>
		<th><label><?php _e( "Working Hours", 'appointments' ); ?></label></th>
		<td>
			<?php echo $appointments->working_hour_form('open') ?>
		</td>
	</tr>
	<tr>
		<th><label><?php _e("Break Hours", 'appointments'); ?></label></th>
		<td>
			<?php echo $appointments->working_hour_form('closed') ?>
		</td>
	</tr>
	<tr>
		<th><label for="open_datepick"><?php _e("Exceptional Working Days", 'appointments'); ?></label></th>
		<td>
			<input class="datepick widefat" id="open_datepick" type="text" name="open[exceptional_days]" value="<?php echo $result['open']; ?>" />
		</td>
	</tr>
	<tr>
		<th><label for="closed_datepick"><?php _e("Holidays", 'appointments'); ?></label></th>
		<td>
			<input class="datepick widefat" id="closed_datepick" type="text" name="closed[exceptional_days]" value="<?php echo $result['closed']; ?>" />
		</td>
	</tr>
</table>
<?php wp_nonce_field( 'app_exceptions-' . $worker_id, 'app_exceptions_nonce' ); ?>
<input type="hidden" name="worker_id" value="<?php echo $worker_id; ?>">
<script type="text/javascript">
	jQuery(document).ready(function($){
		$("#open_datepick").datepick({dateFormat: 'yyyy-mm-dd',multiSelect: 999, monthsToShow: 2});
		$("#closed_datepick").datepick({dateFormat: 'yyyy-mm-dd',multiSelect: 999, monthsToShow: 2});
	});
</script>
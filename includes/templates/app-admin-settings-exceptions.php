<?php global $appointments, $wpdb; ?>

<?php _e( '<i>Here you can define exceptional working or non working days for your business and for your service providers. You should enter holidays here. You can also define a normally non working week day (e.g. a specific Sunday) as a working day. When you add new service providers, their expections will be set to the default schedule.</i>', 'appointments'); ?>
<br />
<br />
<?php
$result = array();
foreach ( array( 'open', 'closed' ) as $stat ) {
	$result[$stat] = $wpdb->get_var($wpdb->prepare("SELECT days FROM {$appointments->exceptions_table} WHERE status=%s AND worker=%d", $stat, $appointments->worker));
}
$workers = $appointments->get_workers();
?>
<?php _e('List for:', 'appointments')?>
&nbsp;
<select id="app_provider_id" name="app_provider_id">
<option value="0"><?php _e('No specific provider', 'appointments')?></option>
<?php
if ( $workers ) {
	foreach ( $workers as $worker ) {
		if ( $appointments->worker == $worker->ID )
			$s = " selected='selected'";
		else
			$s = '';
		echo '<option value="'.$worker->ID.'"'.$s.'>' . $appointments->get_worker_name( $worker->ID, false ) . '</option>';
	}
}
?>
</select>
<br /><br />
<form method="post" action="" >
	<table class="widefat fixed">
	<tr>
	<td>
	<?php _e( 'Exceptional working days, e.g. a specific Sunday you decided to work:', 'appointments') ?>
	</td>
	</tr>
	<tr>
	<td>
	<input class="datepick" id="open_datepick" type="text" style="width:100%" name="open[exceptional_days]" value="<?php if (isset($result["open"])) echo $result["open"]?>" />
	</td>
	</tr>

	<tr>
	<td>
	<?php _e( 'Exceptional NON working days, e.g. holidays:', 'appointments') ?>
	</td>
	</tr>
	<tr>
	<td>
	<input class="datepick" id="closed_datepick" type="text" style="width:100%" name="closed[exceptional_days]" value="<?php if (isset($result["closed"])) echo $result["closed"]?>" />
	</td>
	</tr>

	<tr>
	<td>
	<span class="description"><?php _e('Please enter each date using YYYY-MM-DD format (e.g. 2012-08-13) and separate each day with a comma. Datepick will allow entering multiple dates. ', 'appointments')?></span>
	</td>
	</tr>
	</table>

	<input type="hidden" name="location" value="0" />
	<input type="hidden" name="action_app" value="save_exceptions" />
	<?php wp_nonce_field( 'update_app_settings', 'app_nonce' ); ?>
	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save Exceptional Days', 'appointments') ?>" />
	</p>

</form>
<script type="text/javascript">
jQuery(document).ready(function($){
	$('#app_provider_id').change(function(){
		var app_provider_id = $('#app_provider_id option:selected').val();
		window.location.href = "<?php echo admin_url('admin.php?page=app_settings&tab=exceptions')?>" + "&app_provider_id=" + app_provider_id;
	});
	$("#open_datepick").datepick({dateFormat: 'yyyy-mm-dd',multiSelect: 999, monthsToShow: 2});
	$("#closed_datepick").datepick({dateFormat: 'yyyy-mm-dd',multiSelect: 999, monthsToShow: 2});
});
</script>
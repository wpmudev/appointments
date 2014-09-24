<?php global $appointments, $wpdb; ?>

<?php _e( '<i>Here you can define working hours and breaks for your business. When you add new service providers, their working and break hours will be set to the default schedule. Then you can edit their schedule by selecting their names from the dropdown menu below.</i>', 'appointments'); ?>
<br />
<br />
<?php
$workers = $wpdb->get_results( "SELECT * FROM " . $appointments->workers_table . " " );
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
<script type="text/javascript">
jQuery(document).ready(function($){
	$('#app_provider_id').change(function(){
		var app_provider_id = $('#app_provider_id option:selected').val();
		window.location.href = "<?php echo admin_url('admin.php?page=app_settings&tab=working_hours')?>" + "&app_provider_id=" + app_provider_id;
	});
});
</script>
<form method="post" action="" >
	<table class="widefat fixed">
	<tr>
	<th style="width:40%"><?php _e( 'Working Hours', 'appointments' ) ?></th>
	<th style="width:40%"><?php _e( 'Break Hours', 'appointments' ) ?></th>
	<tr>
	<td>
	<?php echo $appointments->working_hour_form( 'open' ); ?>
	</td>
	<td>
	<?php echo $appointments->working_hour_form( 'closed' ); ?>
	</td>
	</tr>
	</table>

	<input type="hidden" name="worker" value="0" />
	<input type="hidden" name="location" value="0" />
	<input type="hidden" name="action_app" value="save_working_hours" />
	<?php wp_nonce_field( 'update_app_settings', 'app_nonce' ); ?>
	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save Working Hours', 'appointments') ?>" />
	</p>

</form>
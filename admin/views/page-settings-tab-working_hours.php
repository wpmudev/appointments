<?php global $appointments, $wpdb; ?>

<p class="description"><?php _e( 'Here you can define working hours and breaks for your business. When you add new service providers, their working and break hours will be set to the default schedule. Then you can edit their schedule by selecting their names from the dropdown menu below.', 'appointments' ); ?></p>
<?php
$workers = appointments_get_workers();
_e( 'List for:', 'appointments' );
?>
&nbsp;
<select id="app_provider_id" name="app_provider_id">
<option value="0"><?php _e( 'No specific provider', 'appointments' )?></option>
<?php
if ( $workers ) {
	foreach ( $workers as $worker ) {
		if ( $appointments->worker == $worker->ID ) {
			$s = " selected='selected'"; } else { 			$s = ''; }
		echo '<option value="'.$worker->ID.'"'.$s.'>' . appointments_get_worker_name( $worker->ID, false ) . '</option>';
	}
}
?>
</select>
<br /><br />
<script type="text/javascript">
jQuery(document).ready(function($){
	$('#app_provider_id').change(function(){
		var app_provider_id = $('#app_provider_id option:selected').val();
		window.location.href = "<?php echo admin_url( 'admin.php?page=app_settings&tab=working_hours' )?>" + "&app_provider_id=" + app_provider_id;
	});
});
</script>
<form method="post" action="">
    <div class="app-flex">
        <div>
            <h2><?php esc_html_e( 'Working Hours', 'appointments' ) ?></h2>
            <?php echo $appointments->working_hour_form( 'open' ); ?>
        </div>
        <div>
            <h2><?php esc_html_e( 'Break Hours', 'appointments' ) ?></h2>
            <?php echo $appointments->working_hour_form( 'closed' ); ?>
        </div>
    </div>
	<input type="hidden" name="worker" value="0" />
	<input type="hidden" name="location" value="0" />
	<?php _appointments_settings_submit_block( $tab ); ?>
</form>

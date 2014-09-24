<?php global $appointments, $wpdb; ?>

<?php _e( '<i>Here you should define your services for which your client will be making appointments. <b>There must be at least one service defined.</b> Capacity is the number of customers that can take the service at the same time. Enter 0 for no specific limit (Limited to number of service providers, or to 1 if no service provider is defined for that service). Price is only required if you request payment to accept appointments. You can define a description page for the service you are providing.</i>', 'appointments') ?>
<div class='submit'>
<input type="button" id="add_service" class='button-secondary' value='<?php _e( 'Add New Service', 'appointments' ) ?>' />
</div>

<form method="post" action="" >

	<table class="widefat fixed" id="services-table" >
	<tr>
	<th style="width:5%"><?php _e( 'ID', 'appointments') ?></th>
	<th style="width:35%"><?php _e( 'Name', 'appointments') ?></th>
	<th style="width:10%"><?php _e( 'Capacity', 'appointments') ?></th>
	<th style="width:15%"><?php _e( 'Duration (mins)', 'appointments') ?></th>
	<th style="width:10%"><?php echo __( 'Price', 'appointments') . ' ('. $appointments->options['currency']. ')' ?></th>
	<th style="width:25%"><?php _e( 'Description page', 'appointments') ?></th>
	</tr>
	<?php
	$services = $appointments->get_services();
	$max_id = null;
	if ( is_array( $services ) && $nos = count( $services ) ) {
		foreach ( $services as $service ) {
			echo $appointments->add_service( true, $service );
			if ( $service->ID > $max_id )
				$max_id = $service->ID;
		}
	}
	else {
		echo '<tr class="no_services_defined"><td colspan="4">'. __( 'No services defined', 'appointments' ) . '</td></tr>';
	}
	?>

	</table>

	<div class='submit' id='div_save_services' <?php if ($max_id==null) echo 'style="display:none"' ?>>
	<input type="hidden" name="number_of_services" id="number_of_services" value="<?php echo $max_id;?>" />
	<input type="hidden" name="action_app" value="save_services" />
	<?php wp_nonce_field( 'update_app_settings', 'app_nonce' ); ?>
	<input class='button-primary' type='submit' value='<?php _e( 'Save Services', 'appointments' ) ?>' />
	&nbsp;&nbsp;
	<?php _e( '<i>Tip: To delete a service, just clear its name and save.</i>', 'appointments' ); ?>
	</div>

</form>

<script type="text/javascript">
(function ($) {
$(function () {
	$('#add_service').click(function(){
		$('.add-new-waiting').show();
		var n = 1;
		if ( $('#number_of_services').val() > 0 ) {
			n = parseInt( $('#number_of_services').val() ) + 1;
		}
		$('#services-table').append('<?php echo $appointments->esc_rn( $appointments->add_service()); ?>');
		$('#number_of_services').val(n);
		$('#div_save_services').show();
		$('.no_services_defined').hide();
		$('.add-new-waiting').hide();
	});
});
})(jQuery);
</script>
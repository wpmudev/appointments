<?php global $appointments, $wpdb; ?>

<?php printf( __( '<i>Here you can optionally select your service providers, i.e. workers, and assign them to certain services. Your service providers must be users of the website. To add a new user %s. You can define additional price for them. This will be added to the price of the service. You can define a bio page for the service provider. <br />A dummy service provider is a user whose emails are redirected to another user that is set on the General tab.</i>', 'appointments'), '<a href="'.admin_url('user-new.php').'">' . __('Click here', 'appointments') . '</a>'); ?>
<div class='submit'>
<input type="button" id="add_worker" class='button-secondary' value='<?php _e( 'Add New Service Provider', 'appointments' ) ?>' />
<img class="add-new-waiting" style="display:none;" src="<?php echo admin_url('images/wpspin_light.gif')?>" alt="">
</div>

<form method="post" action="" >

	<table class="widefat fixed" id="workers-table" >
	<tr>
	<th style="width:5%"><?php _e( 'ID', 'appointments') ?></th>
	<th style="width:25%"><?php _e( 'Service Provider', 'appointments') ?></th>
	<th style="width:10%"><?php _e( 'Dummy?', 'appointments') ?></th>
	<th style="width:10%"><?php  echo __( 'Additional Price', 'appointments') . ' ('. $appointments->options['currency']. ')' ?></th>
	<th style="width:25%"><?php _e( 'Services Provided*', 'appointments') ?></th>
	<th style="width:25%"><?php _e( 'Bio page', 'appointments') ?></th>
	</tr>
	<tr>
	<td colspan="6"><span class="description" style="font-size:11px"><?php _e('* <b>You must select at least one service, otherwise provider will not be saved!</b>', 'appointments') ?></span>
	</td>
	</tr>
	<?php
	$workers = appointments_get_workers();
	$max_id = 0;
	if ( is_array( $workers ) && $nos = count( $workers ) ) {
		foreach ( $workers as $worker ) {
			echo $appointments->add_worker( true, $worker );
			if ( $worker->ID > $max_id )
				$max_id = $worker->ID;
		}
	}
	else {
		echo '<tr class="no_workers_defined"><td colspan="6">'. __( 'No service providers defined', 'appointments' ) . '</td></tr>';
	}
	?>

	</table>

	<div class='submit' id='div_save_workers' <?php if (!$max_id) echo 'style="display:none"' ?>>
	<input type="hidden" name="number_of_workers" id="number_of_workers" value="<?php echo $max_id;?>" />
	<input type="hidden" name="action_app" value="save_workers" />
	<?php wp_nonce_field( 'update_app_settings', 'app_nonce' ); ?>
	<input class='button-primary' type='submit' value='<?php _e( 'Save Service Providers', 'appointments' ) ?>' />
	&nbsp;&nbsp;
	<?php _e( '<i>Tip: To remove a service provider, uncheck all "Services Provided" selections and save.</i>', 'appointments' ); ?>
	</div>

</form>
<script type="text/javascript">
var multiselect_options = {
	noneSelectedText:'<?php echo esc_js( __('Select services', 'appointments' )) ?>',
	checkAllText:'<?php echo esc_js( __('Check all', 'appointments' )) ?>',
	uncheckAllText:'<?php echo esc_js( __('Uncheck all', 'appointments' )) ?>',
	selectedText:'<?php echo esc_js( __('# selected', 'appointments' )) ?>',
	selectedList:5,
	position: {
	  my: 'left bottom',
	  at: 'left top'
   }
};
jQuery(document).ready(function($){
	$(".add_worker_multiple").multiselect(multiselect_options);
});
</script>

<script type="text/javascript">
(function ($) {
$(function () {
	$('#add_worker').click(function(){
		$('.add-new-waiting').show();
		var k = parseInt( $('#number_of_workers').val() ) + 1;
		$('#workers-table').append('<?php echo $appointments->esc_rn( $appointments->add_worker() )?>');
		$('#number_of_workers').val(k);
		$('#div_save_workers').show();
		$('.no_workers_defined').hide();
		$(".add_worker_multiple").multiselect( multiselect_options );
		$('.add-new-waiting').hide();
	});
});
})(jQuery);
</script>
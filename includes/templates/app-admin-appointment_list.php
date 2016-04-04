<?php
global $page, $action, $type, $appointments;

wp_reset_vars( array('type') );

if(empty($type)) $type = 'active';

$filter = array();
$args = array();

if(isset($_GET['s'])) {
	$s = stripslashes($_GET['s']);
	$filter['s'] = $s;
	$args['s'] = $s;
} else {
	$s = '';
}

if ( isset( $_GET['app_service_id'] ) ) {
	$service_id = $_GET['app_service_id'];
	$args['service'] = $service_id;
} else {
	$service_id = '';
}

if ( isset( $_GET['app_provider_id'] ) ) {
	$worker_id = $_GET['app_provider_id'];
	if ( appointments_is_worker( $worker_id ) ) {
		$args['worker'] = $worker_id;
	}

} else {
	$worker_id = '';
}

if ( isset( $_GET['app_order_by'] ) ) {
	$order_by = $_GET['app_order_by'];
} else {
	$order_by = '';
}



$status_count = appointments_count_appointments( $args );
?>
<div id="wpbody-content">
<div class='wrap'>
	<div class="icon32" style="margin:8px 0 0 0"><img src="<?php echo $appointments->plugin_url . '/images/appointments.png'; ?>" /></div>
	<h2><?php echo __('Appointments','appointments'); ?><a href="javascript:void(0)" class="add-new-h2"><?php _e('Add New', 'appointments')?></a>
	<img class="add-new-waiting" style="display:none;" src="<?php echo admin_url('images/wpspin_light.gif')?>" alt="">
	</h2>

	<ul class="subsubsub">
		<li><a href="<?php echo add_query_arg('type', 'active'); ?>" class="rbutton <?php if($type == 'active') echo 'current'; ?>"><?php  _e('Active appointments', 'appointments'); ?></a> (<?php echo $status_count['paid'] + $status_count['confirmed']; ?>) | </li>
		<li><a href="<?php echo add_query_arg('type', 'pending'); ?>" class="rbutton <?php if($type == 'pending') echo 'current'; ?>"><?php  _e('Pending appointments', 'appointments'); ?></a> (<?php echo $status_count['pending']; ?>) | </li>
		<li><a href="<?php echo add_query_arg('type', 'completed'); ?>" class="rbutton <?php if($type == 'completed') echo 'current'; ?>"><?php  _e('Completed appointments', 'appointments'); ?></a> (<?php echo $status_count['completed']; ?>) | </li>
		<li><a href="<?php echo add_query_arg('type', 'reserved'); ?>" class="rbutton <?php if($type == 'reserved') echo 'current'; ?>"><?php  _e('Reserved by GCal', 'appointments'); ?></a> (<?php echo $status_count['reserved']; ?>) | </li>
		<li><a href="<?php echo add_query_arg('type', 'removed'); ?>" class="rbutton <?php if($type == 'removed') echo 'current'; ?>"><?php  _e('Removed appointments', 'appointments'); ?></a> (<?php echo $status_count['removed']; ?>)</li>
		<li><a href="javascript:void(0)" class="info-button" title="<?php _e('Click to toggle information about statuses', 'appointments')?>">
				<img src="<?php echo $appointments->plugin_url . '/images/information.png'?>" alt="" />
			</a></li>
	</ul>
<br /><br />
<span class="description status-description" style="display:none;">
<ul>
<li><?php _e('<b>Completed:</b> Appointment became overdue after it is confirmed or paid', 'appointments') ?></li>
<li><?php _e('<b>Removed:</b> Appointment was not paid for or was not confirmed manually in the allowed time', 'appointments') ?></li>
<li><?php _e('<b>Reserved by GCal:</b> If you import appointments from Google Calender using Google Calendar API, that is, synchronize your calendar with Appointments+, events in your Google Calendar will be regarded as appointments and they will be shown here. These records cannot be edited here. Use your Google Calendar instead. They will be automatically updated in A+ too.', 'appointments') ?></li>
<li><?php _e('If you require payment:', 'appointments') ?></li>
<li><?php _e('<b>Active/Paid:</b> Paid and confirmed by Paypal', 'appointments') ?></li>
<li><?php _e('<b>Pending:</b> Client applied for the appointment, but not yet paid.', 'appointments') ?></li>
</ul>
<ul>
<li><?php _e('If you do not require payment:', 'appointments') ?></li>
<li><?php _e('<b>Active/Confirmed:</b> Manually confirmed', 'appointments') ?></li>
<li><?php _e('<b>Pending:</b> Client applied for the appointment, but it is not manually confirmed.', 'appointments') ?></li>
</ul>
</span>

<form method="get" action="<?php echo add_query_arg('page', 'appointments'); ?>" class="search-form">
<p class="search-box">
	<label for="app-search-input" class="screen-reader-text"><?php _e('Search Client','appointments'); ?>:</label>
	<input type="hidden" value="appointments" name="page" />
	<input type="hidden" value="<?php echo $type?>" name="type" />
	<input type="hidden" value="<?php echo $service_id?>" name="app_service_id" />
	<input type="hidden" value="<?php echo $worker_id?>" name="app_provider_id" />
	<input type="text" value="<?php echo esc_attr($s); ?>" name="s" />
	<input type="submit" class="button" value="<?php _e('Search Client','appointments'); ?>" />
</p>
</form>

<br class='clear' />

<div class="tablenav top">

	<div class="alignleft actions">
		<form id="app-bulk-change-form" method="post" action="<?php echo add_query_arg('page', 'appointments'); ?>" >
			<input type="hidden" value="appointments" name="page" />
			<input type="hidden" value="1" name="app_status_change" />
			<select name="app_new_status" style='float:none;'>
				<option value=""><?php _e('Bulk status change','appointments'); ?></option>
				<?php foreach ( appointments_get_statuses() as $value=>$name ) {
					echo '<option value="' . esc_attr($value) . '" class="hide-if-no-js">'.$name.'</option>';
				} ?>
			</select>
			<input type="submit" class="button app-change-status-btn" value="<?php _e('Change Status','appointments'); ?>" />
		</form>
	</div>
	<script type="text/javascript">
	jQuery(document).ready(function($){
		$(".app-change-status-btn").click(function(e){
			var button = $(this);
			e.preventDefault();
			// var data = { 'app[]' : []};
			$("td.app-check-column input:checkbox:checked").each(function() {
			  // data['app[]'].push($(this).val());
			    button.after('<input type="hidden" name="app[]" value="'+$(this).val()+'"/>');
			});

				$('#app-bulk-change-form').submit();

		});
	});
	</script>

	<div class="alignleft">

		<form method="get" class="alignleft actions" action="<?php echo add_query_arg('page', 'appointments'); ?>" >
			<div class="alignleft">

				<input type="hidden" value="appointments" name="page" />
				<input type="hidden" value="<?php echo $type?>" name="type" />
				<input type="hidden" value="<?php echo $service_id?>" name="app_service_id" />
				<input type="hidden" value="<?php echo $worker_id?>" name="app_provider_id" />
				<select name="app_order_by" style='float:none;'>
					<option value=""><?php _e('Sort by','appointments'); ?></option>
					<option value="ID" <?php selected( $order_by, 'ID' ); ?>><?php _e('Creation date (Oldest to newest)','appointments'); ?></option>
					<option value="ID_DESC" <?php selected( $order_by, 'ID_DESC' ); ?>><?php _e('Creation date (Newest to oldest)','appointments'); ?></option>
					<option value="start" <?php selected( $order_by, 'start' ); ?>><?php _e('Appointment date (Closest first)','appointments'); ?></option>
					<option value="start_DESC" <?php selected( $order_by, 'start_DESC' ); ?>><?php _e('Appointment date (Closest last)','appointments'); ?></option>
				</select>
				<input type="submit" class="button" value="<?php _e('Sort','appointments'); ?>" />

			</div>
		</form>

		<form method="get" class="alignleft actions" action="<?php echo add_query_arg('page', 'appointments'); ?>" >
			<input type="hidden" value="appointments" name="page" />
			<input type="hidden" value="<?php echo $type?>" name="type" />
			<input type="hidden" value="<?php echo $worker_id?>" name="app_provider_id" />
			<input type="hidden" value="<?php echo $service_id?>" name="app_service_id" />

			<div class="alignleft">

				<select name="app_service_id" style='float:none;'>
					<option value=""><?php _e('Filter by service','appointments'); ?></option>
					<?php
					$services = appointments_get_services();
					if ( $services ) {
						foreach ( $services as $service ) {
							if ( $service_id == $service->ID )
								$selected = " selected='selected' ";
							else
								$selected = "";
							echo '<option '.$selected.' value="' . esc_attr($service->ID) . '">'. $appointments->get_service_name( $service->ID ) .'</option>';
						}
					}
					?>
				</select>
			</div>
			<div class="alignleft">
				<select name="app_provider_id" style='float:none;'>
					<option value=""><?php _e('Filter by service provider','appointments'); ?></option>
					<?php
					$workers = appointments_get_workers();
					if ( $workers ) {
						foreach ( $workers as $worker ) {
							if ( $worker_id == $worker->ID )
								$selected = " selected='selected' ";
							else
								$selected = "";
							echo '<option '.$selected.' value="' . esc_attr($worker->ID) . '">'. appointments_get_worker_name( $worker->ID ) .'</option>';
						}
					}
					?>
				</select>
				<input type="submit" class="button" value="<?php _e('Filter','appointments'); ?>" />
			</div>
		</form>

		<form method="get" class="alignright actions" action="<?php echo add_query_arg('page', 'appointments'); ?>" >
			<div class="alignright">

				<input type="hidden" value="appointments" name="page" />
				<input type="hidden" value="<?php echo $type?>" name="type" />
				<input type="hidden" value="" name="app_service_id" />
				<input type="hidden" value="" name="app_provider_id" />
				<input type="hidden" value="" name="app_order_by" />
				<input type="hidden" value="" name="s" />
				<input type="submit" class="button" value="<?php _e('Reset filters','appointments'); ?>" />
			</div>
		</form>
	</div>
</div>

	<?php
		$appointments->myapps($type);

	?>
	<br />
	<br />
	<form action="<?php echo admin_url('admin-ajax.php?action=app_export'); ?>" method="post">
		<input type="hidden" name="action" value="app_export" />
		<input type="hidden" name="export_type" id="app-export_type" value="type" />
		<input type="submit" id="app-export-selected" class="app-export_trigger button-secondary" value="<?php esc_attr_e(__('Export selected Appointments','appointments')); ?>" />
		<input type="submit" id="app-export-type" class="app-export_trigger button-primary" value="<?php esc_attr_e(sprintf(__('Export %s Appointments','appointments'), App_Template::get_status_name($type))); ?>" data-type="<?php esc_attr_e($type); ?>" />
		<input type="submit" id="app-export-all" class="app-export_trigger button-secondary" value="<?php _e('Export all Appointments','appointments') ?>" title="<?php _e('If you click this button a CSV file containing ALL appointment records will be saved on your PC','appointments') ?>" />
		<script>
		(function ($) {
		function toggle_selected_export () {
			var $sel = $(".column-delete.app-check-column :checked");
			if ($sel.length) $("#app-export-selected").show();
			else $("#app-export-selected").hide();
		}
		$(document).on("click", ".app-export_trigger", function () {
			var $me = $(this),
				$form = $me.closest("form"),
				$sel = $(".column-delete.app-check-column :checked"),
				$type = $form.find("#app-export_type")
			;
			if ($me.is("#app-export-selected") && $sel.length) {
				$sel.each(function () {
					$form.append("<input type='hidden' name='app[]' value='" + $(this).val() + "' />");
				});
				$type.val("selected");
				return true;
			} else if ($me.is("#app-export-type")) {
				$form.append("<input type='hidden' name='status' value='" + $me.attr("data-type") + "' />");
				$type.val("type");
				return true;
			} else if ($me.is("#app-export-all")) {
				$type.val("all");
				return true;
			}
			return false;
		});
		$(document).on("change", ".column-delete.app-check-column input, .app-column-delete input", toggle_selected_export);
		$(toggle_selected_export);
		})(jQuery);
		</script>
		<?php do_action('app-export-export_form_end'); ?>
	</form>

</div> <!-- wrap -->
</div>
<script type="text/javascript">
jQuery(document).ready(function($){
	$(".info-button").click(function(){
		$(".status-description").toggle('fast');
	});
});
</script>
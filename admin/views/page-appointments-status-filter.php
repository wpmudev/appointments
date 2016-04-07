<h2 class="screen-reader-text"><?php _e( 'Filter Appointments List', 'appointments' ); ?></h2>
<ul class="subsubsub">
	<li><a href="<?php echo add_query_arg('type', 'active'); ?>" class="rbutton <?php if($type == 'active') echo 'current'; ?>"><?php  _e('Active', 'appointments'); ?></a> (<?php echo $status_count['paid'] + $status_count['confirmed']; ?>) | </li>
	<li><a href="<?php echo add_query_arg('type', 'pending'); ?>" class="rbutton <?php if($type == 'pending') echo 'current'; ?>"><?php  _e('Pending', 'appointments'); ?></a> (<?php echo $status_count['pending']; ?>) | </li>
	<li><a href="<?php echo add_query_arg('type', 'completed'); ?>" class="rbutton <?php if($type == 'completed') echo 'current'; ?>"><?php  _e('Completed', 'appointments'); ?></a> (<?php echo $status_count['completed']; ?>) | </li>
	<li><a href="<?php echo add_query_arg('type', 'reserved'); ?>" class="rbutton <?php if($type == 'reserved') echo 'current'; ?>"><?php  _e('Reserved by GCal', 'appointments'); ?></a> (<?php echo $status_count['reserved']; ?>) | </li>
	<li><a href="<?php echo add_query_arg('type', 'removed'); ?>" class="rbutton <?php if($type == 'removed') echo 'current'; ?>"><?php  _e('Removed', 'appointments'); ?></a> (<?php echo $status_count['removed']; ?>)</li>
	<li><a href="javascript:void(0)" class="dashicons dashicons-info info-button" title="<?php _e('Click to toggle information about statuses', 'appointments')?>"></a></li>
</ul>
<div class="description status-description" style="display:none;">
	<br class="clear">
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
</div>
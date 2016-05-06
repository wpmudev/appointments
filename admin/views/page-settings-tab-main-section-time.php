<table class="form-table">
	<tr>
		<th scope="row"><label for="min_time"><?php _e( 'Time base (minutes)', 'appointments' ) ?></label></th>
		<td colspan="2">
			<select name="min_time" id="min_time">
				<?php foreach ( $base_times as $min_time ): ?>
					<option value="<?php echo esc_attr( $min_time ); ?>" <?php selected( $min_time, $min_time_setting ); ?>><?php echo esc_html( $min_time ); ?></option>
				<?php endforeach; ?>
			</select>
			<br>
			<p class="description"><?php _e( 'Minimum time that will be effective for durations, appointment and schedule intervals. Service durations can only be set as multiples of this value. Default: 30.', 'appointments' ) ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row"><label for="additional_min_time"><?php _e( 'Additional time base (minutes)', 'appointments' ) ?></label></th>
		<td>
			<input type="text" class="small-text" name="additional_min_time" id="additional_min_time" value="<?php if ( isset( $options["additional_min_time"] ) ) echo $options["additional_min_time"] ?>"/>
			<br>
			<p class="description"><?php _e( 'If the above time bases do not fit your business, you can add a new one, e.g. 240. Note: After you save this additional time base, you must select it using the above setting. Note: Minimum allowed time base setting is 10 minutes.', 'appointments' ) ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row"><label for="admin_min_time"><?php _e( 'Admin side time base (minutes)', 'appointments' ) ?></label></th>
		<td>
			<input type="text" class="small-text" name="admin_min_time" id="admin_min_time" value="<?php if ( isset( $options["admin_min_time"] ) ) echo $options["admin_min_time"] ?>"/>
			<br>
			<p class="description"><?php _e( 'This setting may be used to provide flexibility while manually setting and editing the appointments. For example, if you enter here 15, you can reschedule an appointment for 15 minutes intervals even selected time base is 45 minutes. If you leave this empty, then the above selected time base will be applied on the admin side.', 'appointments' ) ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row"><label for="app_lower_limit"><?php _e( 'Appointments lower limit (hours)', 'appointments' ) ?></label></th>
		<td>
			<input type="text" style="width:50px" name="app_lower_limit" id="app_lower_limit" value="<?php if ( isset( $options["app_lower_limit"] ) ) echo $options["app_lower_limit"] ?>"/>
			<br>
			<p class="description"><?php _e( 'This will block time slots to be booked with the set value starting from current time. For example, if you need 2 days to evaluate and accept an appointment, enter 48 here. Default: 0 (no blocking - appointments can be made if end time has not been passed)', 'appointments' ) ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row"><label for="app_limit"><?php _e( 'Appointments upper limit (days)', 'appointments' ) ?></label></th>
		<td>
			<input type="text" class="small-text" name="app_limit" id="app_limit" value="<?php if ( isset( $options["app_limit"] ) ) echo $options["app_limit"] ?>"/>
			<br>
			<p class="description"><?php _e( 'Maximum number of days from today that a client can book an appointment. Default: 365', 'appointments' ) ?></p>
	</tr>

	<tr>
		<th scope="row"><label for="clear_time"><?php _e( 'Disable pending appointments after (mins)', 'appointments' ) ?></label></th>
		<td>
			<input type="text" class="small-text" name="clear_time" id="clear_time" value="<?php if ( isset( $options["clear_time"] ) ) echo $options["clear_time"] ?>"/>
			<br>
			<p class="description"><?php _e( 'Pending appointments will be automatically removed (not deleted - deletion is only possible manually) after this set time and that appointment time will be freed. Enter 0 to disable. Default: 60. Please note that pending and GCal reserved appointments whose starting time have been passed will always be removed, regardless of any other setting.', 'appointments' ) ?></p>
	</tr>

	<tr>
		<th scope="row"><label for="spam_time"><?php _e( 'Minimum time to pass for new appointment (secs)', 'appointments' ) ?></label></th>
		<td>
			<input type="text" class="small-text" name="spam_time" id="spam_time" value="<?php if ( isset( $options["spam_time"] ) ) echo $options["spam_time"] ?>"/>
			<br>
			<p class="description"><?php _e( 'You can limit appointment application frequency to prevent spammers who can block your appointments. This is only applied to pending appointments. Enter 0 to disable. Tip: To prevent any further appointment applications of a client before a payment or manual confirmation, enter a huge number here.', 'appointments' ) ?></p>
	</tr>
	<?php do_action( 'app-settings-time_settings' ); ?>
</table>
<table class="form-table">

	<tr valign="top">
		<th scope="row"><?php _e( 'Use Built-in Cache', 'appointments' ) ?></th>
		<td colspan="2">
			<select name="use_cache">
				<option value="no" <?php if ( @$options['use_cache'] <> 'yes' )
					echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
				<option value="yes" <?php if ( @$options['use_cache'] == 'yes' )
					echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
			</select>
					<span
						class="description"><?php _e( 'Appointments+ has a built-in cache to increase performance. If you are making changes in the styling of your appointment pages, modifying shortcode parameters or having some issues while using it, disable it by selecting No.', 'appointments' ) ?></span>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><?php _e( 'Clear Cache', 'appointments' ) ?></th>
		<td colspan="2">
			<input type="checkbox"
			       name="force_cache" <?php if ( isset( $options["force_cache"] ) && $options["force_cache"] )
				echo 'checked="checked"' ?> />
					<span
						class="description"><?php _e( 'Cache is automatically cleared at regular intervals (Default: 10 minutes) or when you change a setting. To clear it manually check this checkbox.', 'appointments' ) ?></span>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><?php _e( 'Allow Overwork (end of day)', 'appointments' ) ?></th>
		<td colspan="2">
			<select name="allow_overwork">
				<option value="no" <?php if ( @$options['allow_overwork'] <> 'yes' )
					echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
				<option value="yes" <?php if ( @$options['allow_overwork'] == 'yes' )
					echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
			</select>
					<span
						class="description"><?php _e( 'Whether you accept appointments exceeding working hours for the end of day. For example, if you are working until 6pm, and a client asks an appointment for a 60 minutes service at 5:30pm, to allow such an appointment you should select this setting as Yes. Please note that this is only practical if the selected service lasts longer than the base time. Such time slots are marked as "not possible" in the schedule.', 'appointments' ) ?></span>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><?php _e( 'Allow Overwork (break hours)', 'appointments' ) ?></th>
		<td colspan="2">
			<select name="allow_overwork_break">
				<option value="no" <?php if ( @$options['allow_overwork_break'] <> 'yes' )
					echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
				<option
					value="yes" <?php if ( @$options['allow_overwork_break'] == 'yes' )
					echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
			</select>
					<span
						class="description"><?php _e( 'Same as above, but valid for break hours. If you want to allow appointments exceeding break times, then select this as Yes.', 'appointments' ) ?></span>
		</td>
	</tr>

	
	<?php do_action( 'app-settings-advanced_settings' ); ?>
</table>
<table class="form-table">

	<tr valign="top">
		<th scope="row"><label for="force_cache"><?php _e( 'Clear Cache', 'appointments' ) ?></label></th>
		<td colspan="2">
			<input type="checkbox" name="force_cache" id="force_cache" />
            <span class="description">
                <?php _e( 'Cache is automatically cleared at regular intervals (Default: 10 minutes) or when you change a setting. To clear it manually check this checkbox.', 'appointments' ) ?>
            </span>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><label for="allow_overwork"><?php _e( 'Allow Overwork (end of day)', 'appointments' ) ?></label></th>
		<td colspan="2">
			<select name="allow_overwork" id="allow_overwork">
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
		<th scope="row"><label for="allow_overwork_break"><?php _e( 'Allow Overwork (break hours)', 'appointments' ) ?></label></th>
		<td colspan="2">
			<select name="allow_overwork_break" id="allow_overwork_break">
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
    
    <tr valign="top">
		<th scope="row"><label for="keep_options_on_uninstall"><?php _e( 'Keep options on uninstall', 'appointments' ) ?></label></th>
		<td colspan="2">		
			<input type="checkbox" name="keep_options_on_uninstall" id="keep_options_on_uninstall" <?php checked( $options['keep_options_on_uninstall'] ); ?> />
            <span class="description">
                <?php _e( 'By enabling this option you can keep your appointments and settings when deleting plugin.', 'appointments' ); ?>
            </span>
		</td>
	</tr>

	
	<?php do_action( 'app-settings-advanced_settings' ); ?>
</table>

<table class="form-table">
	<tr valign="top">
		<th scope="row"><label for="disable_logging"><?php _e( 'Disable loggings', 'appointments' ) ?></label></th>
		<td colspan="2">
			<?php _appointments_html_chceckbox( $options, 'disable_logging' ); ?>
			<p class="description"><?php _e( 'Disable all loggings.', 'appointments' ) ?></p>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="force_cache"><?php _e( 'Clear Cache', 'appointments' ) ?></label></th>
		<td colspan="2">
			<?php _appointments_html_chceckbox( $options, 'force_cache' ); ?>
			<p class="description"><?php _e( 'Cache is automatically cleared at regular intervals (Default: 10 minutes) or when you change a setting. To clear it manually check this checkbox.', 'appointments' ) ?></p>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="always_load_scripts"><?php _e( 'Always load scripts', 'appointments' ) ?></label></th>
		<td colspan="2">
			<?php _appointments_html_chceckbox( $options, 'always_load_scripts' ); ?>
			<p class="description"><?php _e( 'By default some scrtips are loaded only if booking shortcodes are detected. With some themes and if booking form is in popup it might be necessary to enable this option to force loading of these assets.', 'appointments' ) ?></p>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="allow_overwork"><?php _e( 'Allow Overwork (end of day)', 'appointments' ) ?></label></th>
		<td colspan="2">
			<?php _appointments_html_chceckbox( $options, 'allow_overwork' ) ?>
			<p class="description"><?php _e( 'Whether you accept appointments exceeding working hours for the end of day. For example, if you are working until 6pm, and a client asks an appointment for a 60 minutes service at 5:30pm, to allow such an appointment you should select this setting as Yes. Please note that this is only practical if the selected service lasts longer than the base time. Such time slots are marked as "not possible" in the schedule.', 'appointments' ) ?></p>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="allow_overwork_break"><?php _e( 'Allow Overwork (break hours)', 'appointments' ) ?></label></th>
		<td colspan="2">
			<?php _appointments_html_chceckbox( $options, 'allow_overwork_break' ) ?>
			<p class="description"><?php _e( 'Same as above, but valid for break hours. If you want to allow appointments exceeding break times, then select this as Yes.', 'appointments' ) ?></p>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="keep_options_on_uninstall"><?php _e( 'Keep options on uninstall', 'appointments' ) ?></label></th>
		<td colspan="2">
			<?php _appointments_html_chceckbox( $options, 'keep_options_on_uninstall' ); ?>
			<p class="description"><?php _e( 'By enabling this option you can keep your appointments and settings when deleting plugin.', 'appointments' ); ?></p>
		</td>
	</tr>
	<?php do_action( 'app-settings-advanced_settings' ); ?>
</table>

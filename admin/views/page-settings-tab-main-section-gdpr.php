<p class="description"><?php _e( 'The General Data Protection Regulation (GDPR) (EU) 2016/679 is a regulation in EU law on data protection and privacy for all individuals within the European Union. It also addresses the export of personal data outside the EU. The GDPR aims primarily to give control to citizens and residents over their personal data and to simplify the regulatory environment for international business by unifying the regulation within the EU.', 'appointments' ); ?></p>
<?php
global $wp_version;
$is_less_496 = version_compare( $wp_version, '4.9.6', '<' );
if ( $is_less_496 ) {
	echo '<div class="notice notice-error inline notice-app-wp-version">';
	echo wpautop( __( 'GDPR settings are not available for WordPress version lower than 4.9.6. Please update your WordPress first.', 'appointments' ) );
	echo '</div>';
	return;
}
?>
<table class="form-table">
	<tr>
		<th scope="row"><?php _e( 'User can erase after', 'appointments' ) ?></th>
		<td>
        <input value="<?php echo esc_attr( $options['gdpr_number_of_days_user_erease'] ); ?>" name="gdpr_number_of_days_user_erease" type="number" min="1" /> <?php esc_html_e( 'days', 'appointments' ); ?>
			<p class="description"><?php _e( 'Completed appointments will be allowed to erase by user after selected number of days after an appointment date.', 'appointments' ) ?></p>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="gdpr_delete"><?php _e( 'Erase Appointments', 'appointments' ) ?></label></th>
		<td>
			<?php _appointments_html_chceckbox( $options, 'gdpr_delete', 'appointments_gdpr_delete' ); ?>
			<p class="description"><?php _e( 'You can force to delete completed appointments.', 'appointments' ) ?></p>
		</td>
	</tr>
	<tr class="appointments_gdpr_delete">
		<th scope="row"><?php _e( 'Auto erase after', 'appointments' ) ?></th>
		<td>
			<input value="<?php echo esc_attr( $options['gdpr_number_of_days'] ); ?>" name="gdpr_number_of_days" type="number" min="1" /> <?php esc_html_e( 'days', 'appointments' ); ?>
			<p class="description"><?php _e( 'Completed appointments will be deleted after.', 'appointments' ) ?></p>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="gdpr_delete"><?php _e( 'Agreement', 'appointments' ) ?></label></th>
		<td>
			<?php _appointments_html_chceckbox( $options, 'gdpr_checkbox_show', 'appointments_gdpr_checkbox_show' ); ?>
			<p class="description"><?php _e( 'Add a checkbox specifically asking the user of the form if they consent to you storing and using their personal information to get back in touch with them.', 'appointments' ) ?></p>
		</td>
	</tr>
	<tr class="appointments_gdpr_checkbox_show">
		<th scope="row"><?php _e( 'Checkbox text', 'appointments' ) ?></th>
		<td><input type="text" class="large-text" value="<?php echo esc_attr( $options['gdpr_checkbox_text'] ); ?>" name="gdpr_checkbox_text" /></td>
	</tr>
	<tr class="appointments_gdpr_checkbox_show">
		<th scope="row"><?php _e( 'Error message', 'appointments' ) ?></th>
		<td><input type="text" class="large-text" value="<?php echo esc_attr( $options['gdpr_checkbox_alert'] ); ?>" name="gdpr_checkbox_alert" /></td>
	</tr>
	<?php do_action( 'app-settings-gdpr_settings' ); ?>
</table>

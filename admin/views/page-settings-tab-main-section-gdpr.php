<p class="description"><?php _e( 'The General Data Protection Regulation (GDPR) (EU) 2016/679 is a regulation in EU law on data protection and privacy for all individuals within the European Union. It also addresses the export of personal data outside the EU. The GDPR aims primarily to give control to citizens and residents over their personal data and to simplify the regulatory environment for international business by unifying the regulation within the EU.'. 'appointments' ); ?></p>
<table class="form-table">
	<tr valign="top">
		<th scope="row"><label for="gdpr_delete"><?php _e( 'Delete Appointments', 'appointments' ) ?></label></th>
		<td>
            <?php _appointments_html_chceckbox( $options, 'gdpr_delete', 'appointments_gdpr_delete' ); ?>
            <p class="description"><?php _e( 'You can force to delete completed appointments.', 'appointments' ) ?></p>
		</td>
	</tr>
	<tr class="appointments_gdpr_delete">
		<th scope="row"><?php _e( 'Nuber of days', 'appointments' ) ?></th>
		<td>
			<input value="<?php echo esc_attr( $options['gdpr_number_of_days'] ); ?>" name="gdpr_number_of_days" type="number" min="1" />
            <p class="description"><?php _e( 'Completed appointments will be deleted after .', 'appointments' ) ?></p>
		</td>
	</tr>
	<?php do_action( 'app-settings-gdpr_settings' ); ?>
</table>

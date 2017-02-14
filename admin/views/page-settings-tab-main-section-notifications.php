<table class="form-table">

	<tr valign="top">
		<th scope="row"><?php _e( 'Send Confirmation email', 'appointments' ) ?></th>
		<td colspan="2">
			<select name="send_confirmation">
				<option value="no" <?php if ( @$options['send_confirmation'] <> 'yes' )
					echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
				<option value="yes" <?php if ( @$options['send_confirmation'] == 'yes' )
					echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
			</select>
						<span
							class="description"><?php _e( 'Whether to send an email after confirmation of the appointment. Note: Admin and service provider will also get a copy as separate emails.', 'appointments' ) ?></span>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><?php _e( 'Send Notification to admin if confirmation is required', 'appointments' ) ?></th>
		<td colspan="2">
			<select name="send_notification">
				<option value="no" <?php if ( @$options['send_notification'] <> 'yes' )
					echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
				<option value="yes" <?php if ( @$options['send_notification'] == 'yes' )
					echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
			</select>
						<span
							class="description"><?php _e( 'You may want to receive a notification email whenever a new appointment is made from front end in pending status. This email is only sent if you do not require a payment, that is, if your approval is required. Note: Notification email is also sent to the service provider, if a provider is namely selected by the client, and "Allow Service Provider Confirm Own Appointments" is set as Yes.', 'appointments' ) ?></span>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php _e( 'Confirmation Email Subject', 'appointments' ) ?></th>
		<td>
			<input value="<?php echo esc_attr( $options['confirmation_subject'] ); ?>"
			       size="90" name="confirmation_subject" type="text"/>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php _e( 'Confirmation email Message', 'appointments' ) ?></th>
		<td>
						<textarea cols="90" rows="6"
						          name="confirmation_message"><?php echo esc_textarea( $options['confirmation_message'] ); ?></textarea>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><?php _e( 'Send Reminder email to the Client', 'appointments' ) ?></th>
		<td colspan="2">
			<select name="send_reminder">
				<option value="no" <?php if ( @$options['send_reminder'] <> 'yes' )
					echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
				<option value="yes" <?php if ( @$options['send_reminder'] == 'yes' )
					echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
			</select>
						<span
							class="description"><?php _e( 'Whether to send reminder email(s) to the client before the appointment.', 'appointments' ) ?></span>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php _e( 'Reminder email Sending Time for the Client (hours)', 'appointments' ) ?></th>
		<td>
			<input value="<?php echo esc_attr( $options['reminder_time'] ); ?>"
			       name="reminder_time" type="text"/>
			<br/>
						<span
							class="description"><?php _e( 'Defines how many hours reminder will be sent to the client before the appointment will take place. Multiple reminders are possible. To do so, enter reminding hours separated with a comma, e.g. 48,24.', 'appointments' ) ?></span>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><?php _e( 'Send Reminder email to the Provider', 'appointments' ) ?></th>
		<td colspan="2">
			<select name="send_reminder_worker">
				<option value="no" <?php if ( @$options['send_reminder_worker'] <> 'yes' )
					echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
				<option
					value="yes" <?php if ( @$options['send_reminder_worker'] == 'yes' )
					echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
			</select>
						<span
							class="description"><?php _e( 'Whether to send reminder email(s) to the service provider before the appointment.', 'appointments' ) ?></span>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php _e( 'Reminder email Sending Time for the Provider (hours)', 'appointments' ) ?></th>
		<td>
			<input value="<?php echo esc_attr( $options['reminder_time_worker'] ); ?>"
			       size="90" name="reminder_time_worker" type="text"/>
			<br/>
						<span
							class="description"><?php _e( 'Same as above, but defines the time for service provider.', 'appointments' ) ?></span>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php _e( 'Reminder email Subject', 'appointments' ) ?></th>
		<td>
			<input value="<?php echo esc_attr( $options['reminder_subject'] ); ?>"
			       size="90" name="reminder_subject" type="text"/>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php _e( 'Reminder email Message', 'appointments' ) ?></th>
		<td>
						<textarea cols="90" rows="6"
						          name="reminder_message"><?php echo esc_textarea( $options['reminder_message'] ); ?></textarea>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php _e( 'Send notification email on appointment removal', 'appointments' ) ?></th>
		<td colspan="2">
			<select name="send_removal_notification">
				<option
					value="no" <?php if ( @$options['send_removal_notification'] <> 'yes' )
					echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
				<option
					value="yes" <?php if ( @$options['send_removal_notification'] == 'yes' )
					echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
			</select>
			<span class="description">
				<?php _e( 'Send out an email to appropriate clients and providers when an appointment has been removed.', 'appointments' ) ?>
				<br/>
				<?php _e( '<b>Note:</b> This email will only be sent for explicitly removed appointments only. The appointments that get removed due to expiration will not be affected.', 'appointments' ) ?>
			</span>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php _e( 'Removal Notification Email Subject', 'appointments' ) ?></th>
		<td>
			<?php
			$rn_subject = ! empty( $options['removal_notification_subject'] )
				? $options['removal_notification_subject']
				: App_Template::get_default_removal_notification_subject();
			?>
			<input value="<?php echo esc_attr( $rn_subject ); ?>" size="90"
			       name="removal_notification_subject" type="text"/>
		</td>
	</tr>

	<tr>
		<th scope="row"><?php _e( 'Removal Notification Email Message', 'appointments' ) ?></th>
		<td>
			<?php
			$rn_msg = ! empty( $options['removal_notification_message'] )
				? $options['removal_notification_message']
				: App_Template::get_default_removal_notification_message();
			?>
			<textarea cols="90" rows="6"
			          name="removal_notification_message"><?php echo esc_textarea( $rn_msg ); ?></textarea>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><?php _e( 'Log Sent email Records', 'appointments' ) ?></th>
		<td colspan="2">
			<select name="log_emails">
				<option value="no" <?php if ( @$options['log_emails'] <> 'yes' )
					echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
				<option value="yes" <?php if ( @$options['log_emails'] == 'yes' )
					echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
			</select>
						<span
							class="description"><?php _e( 'Whether to log confirmation and reminder email records (Not the emails themselves).', 'appointments' ) ?></span>
		</td>
	</tr>
	<tr>

	<tr>
		<th scope="row">&nbsp;</th>
		<td>
	<span class="description">
	<?php _e( 'For the above email subject and message contents, you can use the following placeholders which will be replaced by their real values:', 'appointments' ) ?>
		&nbsp;SITE_NAME, CLIENT, SERVICE, SERVICE_PROVIDER, DATE_TIME, PRICE, DEPOSIT, <span
			class="app-has_explanation"
			title="(PRICE - DEPOSIT)">BALANCE</span>, PHONE, NOTE, ADDRESS, CITY, EMAIL <?php _e( "(Client's email)", "appointments" ) ?>
		<span style="display: none;">, CANCEL <?php _e( "(Adds a cancellation link to the email body)", "appointments" ) ?></span>
	</span>
		</td>
	</tr>

</table>

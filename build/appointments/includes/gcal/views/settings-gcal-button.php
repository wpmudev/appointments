<h3><?php _e( 'Google Calendar Button Settings', 'appointments' ); ?></h3>
<table class="form-table">
	<tr valign="top">
		<th scope="row" ><label for="gcal"><?php _e('Add Google Calendar Button', 'appointments')?></label></th>
		<td colspan="2">
			<select name="gcal" id="gcal">
				<option value="no" <?php selected( $gcal, false ); ?>><?php _e('No', 'appointments')?></option>
				<option value="yes" <?php selected( $gcal, true ); ?>><?php _e('Yes', 'appointments')?></option>
			</select>
			<br />
			<span class="description"><?php _e('Whether to let client access his Google Calendar account using Google Calendar button. Button is inserted in the confirmation area, as well as My Appointments shortcode and user page/tab if applicable.', 'appointments') ?></span>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row" ><label for="gcal_same_window"><?php _e('Open Google Calendar in the Same Window', 'appointments')?></label></th>
		<td colspan="2">
			<input type="checkbox" name="gcal_same_window" id="gcal_same_window" <?php checked( $gcal_same_window ); ?>/>
			<span class="description"><?php _e('As default, Google Calendar is opened in a new tab or window. If you check this option, user will be redirected to Google Calendar from the appointment page, without opening a new tab or window. Note: While applying for the appointment, this is effective if payment is not required, or price is zero (Otherwise payment button/form would be lost).', 'appointments') ?></span>
		</td>
	</tr>
</table>
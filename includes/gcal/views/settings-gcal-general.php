<h3><?php _e( 'Google Calendar General Settings', 'appointments' ); ?></h3>
<table class="form-table">

	<tr valign="top">
		<th scope="row" ><label for="gcal_location"><?php _e('Google Calendar Location','appointments')?></label></th>
		<td colspan="2">
			<input type="text" class="widefat" name="gcal_location" id="gcal_location" value="<?php echo esc_attr( $gcal_location ); ?>" />
			<br /><span class="description"><?php _e('Enter the text that will be used as location field in Google Calendar. If left empty, your website description is sent instead. Note: You can use ADDRESS and CITY placeholders which will be replaced by their real values.', 'appointments')?></span>
		</td>
	</tr>
</table>
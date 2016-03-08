<p><?php _e( 'Authorize access to your Google Application', 'appointments' ); ?></p>
<ol>
	<li><a href="<?php echo esc_url( $auth_url ); ?>" target="_blank"><?php _e( 'Generate your access code', 'appointments' ); ?></a></li>
	<li><?php _e( 'Fill the form below', 'appointments' ); ?></li>
	<li><?php _e( 'After Settings are saved, Calendar options will appear here.', 'appointments' ); ?></li>
</ol>
<table class="form-table">
	<tr>
		<th scope="row">
			<label for="app-access-code"><?php _e( 'Access code', 'appointments' ); ?></label>
		</th>
		<td>
			<input type="text" class="widefat" name="access_code" id="app-access-code" value="">
		</td>
	</tr>
</table>
<input type="hidden" name="gcal_action" value="access-code">

<h3><?php _e( 'Google Calendar API: Authorize access to your Google Application', 'appointments' ); ?></h3>
<ol>
	<li><a href="<?php echo esc_url( $auth_url ); ?>" target="_blank"><?php _e( 'Generate your access code', 'appointments' ); ?></a></li>
	<li><?php _e( 'Fill the form below', 'appointments' ); ?></li>
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

<?php wp_nonce_field( 'app-submit-gcalendar' ); ?>
<input type="hidden" name="action" value="step-2">
<p class="submit">
	<?php submit_button( __( 'Submit', 'appointments' ), 'primary', 'app-submit-gcalendar', false ); ?>
	<?php submit_button( __( 'Reset', 'appointments' ), 'secondary', 'app-reset-gcalendar', false ); ?>
</p>
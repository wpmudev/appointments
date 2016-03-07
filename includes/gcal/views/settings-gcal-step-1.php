<form name="input" action="" method="post">
	<h3><?php _e( 'Create a new Google Application', 'appointments' ); ?></h3>
	<ol>
		<li><?php printf( __( 'Go to %s and create a new project. i.e. "Appointments APP"', 'appointments' ), sprintf( '<a target=_blank" href="https://console.developers.google.com/project">%s</a>', __( 'Google Developer Console Projects', 'appointments' )) ); ?>; ?></li>
		<li><?php _e( 'Once in Dashboard, click on Enable and manage APIs, click on Calendar API and then, enable.', 'appointments' ); ?></li>
		<li><?php _e( 'On the left side, click on Credentials and then OAuth consent screen tab', 'appointments' ); ?></li>
		<li><?php _e( 'Choose a product name shown to users, i.e. "Appointments +"', 'appointments' ); ?></li>
		<li><?php _e( 'click on Credentials tab > Create Credentials > OAuth Client ID', 'appointments' ); ?></li>
		<li><?php _e( 'Select "Other" Application type with any name', 'appointments' ); ?></li>
		<li><?php _e( 'Take note of the client ID and client secret and fill the form below', 'appointments' ); ?></li>
	</ol>

	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="app-client-id"><?php _e( 'Client ID', 'appointments' ); ?></label>
			</th>
			<td>
				<input type="text" class="widefat" name="client_id" id="app-client-id" value="">
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="app-client-secret"><?php _e( 'Client Secret', 'appointments' ); ?></label>
			</th>
			<td>
				<input type="text" name="client_secret" class="widefat" id="app-client-secret" value="">
			</td>
		</tr>
	</table>

	<?php wp_nonce_field( 'app-submit-gcalendar' ); ?>
	<input type="hidden" name="action" value="step-1">
	<?php submit_button( __( 'Submit', 'appointments' ), 'primary', 'app-submit-gcalendar' ); ?>
</form>
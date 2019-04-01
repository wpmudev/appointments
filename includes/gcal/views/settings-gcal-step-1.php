<h3><?php _e( 'Google Calendar API: Create a new Google Application', 'appointments' ); ?></h3>
<?php if ( ini_get( 'allow_url_fopen' ) ) { ?>
<h4><?php esc_html_e( 'Instructions:', 'appointments' ); ?></h4>
<?php
$base_url = appointments_plugin_url() .'assets/images/google-calendar-instructions/';
?>
<div class="gcal-slider">
	<ul>
		<li>
			<img src="<?php echo $base_url . 'gcal-instructions-01.png'; ?>" alt="">
			<p>1. <?php printf( __( 'Go to %s and create a new project. i.e. "Appointments APP", then click "Create".', 'appointments' ), sprintf( '<a target=_blank" href="https://console.developers.google.com/project">%s</a>', __( 'Google Developer Console Projects', 'appointments' ) ) ); ?></p>
		</li>
		<li>
			<img src="<?php echo $base_url . 'gcal-instructions-02.png'; ?>" alt="">
			<p>2. <?php _e( 'Once in Dashboard, click on "Enable and manage APIs".', 'appointments' ); ?></p>
		</li>
		<li>
			<img src="<?php echo $base_url . 'gcal-instructions-03.png'; ?>" alt="">
			<p>3. <?php _e( 'Click on "Calendar API".', 'appointments' ); ?></p>
		</li>
		<li>
			<img src="<?php echo $base_url . 'gcal-instructions-04.png'; ?>" alt="">
			<p>4. <?php _e( 'Enable the "Calendar API".', 'appointments' ); ?></p>
		</li>
		<li>
			<img src="<?php echo $base_url . 'gcal-instructions-05.png'; ?>" alt="">
			<p>5. <?php _e( 'On the left side, click on "Credentials"...', 'appointments' ); ?></p>
		</li>
		<li>
			<img src="<?php echo $base_url . 'gcal-instructions-06.png'; ?>" alt="">
			<p>6. <?php _e( '... and then "OAuth consent screen" tab. Choose a product name shown to users, i.e. "Appointments+"', 'appointments' ); ?></p>
		</li>
		<li>
			<img src="<?php echo $base_url . 'gcal-instructions-07.png'; ?>" alt="">
			<p>7. <?php _e( 'Click again on "Credentials" tab and then Create Credentials.', 'appointments' ); ?></p>
		</li>
		<li>
			<img src="<?php echo $base_url . 'gcal-instructions-08.png'; ?>" alt="">
			<p>8. <?php _e( 'Select the "Oauth client ID" option.', 'appointments' ); ?></p>
		</li>
		<li>
			<img src="<?php echo $base_url . 'gcal-instructions-09.png'; ?>" alt="">
			<p>9. <?php _e( 'Select "Other" Application type with any name, the name is not important.', 'appointments' ); ?></p>
		</li>
		<li>
			<img src="<?php echo $base_url . 'gcal-instructions-10.png'; ?>" alt="">
			<p>10. <?php _e( 'Take note of the client ID and client secret and fill the form below.', 'appointments' ); ?></p>
		</li>
	</ul>
</div>

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
<?php } else { ?>
<p><?php _e( 'PHP option <b>allow_url_fopen</b> is off. Please turn it on before you can turn on Google Calendar integration.', 'appointments' ); ?></p>
<?php } ?>

<?php wp_nonce_field( 'app-submit-gcalendar' ); ?>
<input type="hidden" name="action" value="step-1">
<?php submit_button( __( 'Submit', 'appointments' ), 'primary', 'app-submit-gcalendar' ); ?>

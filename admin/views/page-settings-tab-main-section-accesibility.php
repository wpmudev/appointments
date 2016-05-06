<table class="form-table">

	<tr>
		<th scope="row"><label for="auto_confirm"><?php _e( 'Auto confirm', 'appointments' ) ?></label></th>
		<td>
			<select name="auto_confirm" id="auto_confirm">
				<option value="no" <?php if ( @$options['auto_confirm'] <> 'yes' )
					echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
				<option value="yes" <?php if ( @$options['auto_confirm'] == 'yes' )
					echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
			</select>
			<br>
			<p class="description"><?php _e( 'Setting this as Yes will automatically confirm all appointment applications for no payment required case. Note: "Payment required" case will still require a payment.', 'appointments' ) ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row"><label
				for="allow_cancel"><?php _e( 'Allow client cancel own appointments', 'appointments' ) ?></label></th>
		<td>
			<select name="allow_cancel" id="allow_cancel">
				<option value="no" <?php if ( @$options['allow_cancel'] <> 'yes' )
					echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
				<option value="yes" <?php if ( @$options['allow_cancel'] == 'yes' )
					echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
			</select>
			<br>
			<p class="description"><?php _e( 'Whether to allow clients cancel their appointments using the link in confirmation and reminder emails or using my appointments table or for logged in users, using check boxes in their profile pages. For the email case, you will also need to add CANCEL placeholder to the email message settings below. For my appointments table, you will need to add parameter allow_cancel="1" to the shortcode. Note: Admin and service provider will always get a notification email.', 'appointments' ) ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row"><label for="cancel_page"><?php _e( 'Appointment cancelled page', 'appointments' ) ?></label>
		</th>
		<td>
			<?php wp_dropdown_pages( array(
				"show_option_none"   => __( 'Home page', 'appointments' ),
				"option_none_value " => 0,
				"name"               => "cancel_page",
				"selected"           => @$options["cancel_page"]
			) ) ?>
			<br>
			<p class="description"><?php _e( 'In case he is cancelling using the email link, the page that client will be redirected after he cancelled his appointment.', 'appointments' ) ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row"><label for="thank_page"><?php _e( 'Appointment thank you page', 'appointments' ) ?></label></th>
		<td>
			<?php wp_dropdown_pages( array(
				"show_option_none"   => __( 'Do not redirect', 'appointments' ),
				"option_none_value " => 0,
				"name"               => "thank_page",
				"selected"           => @$options["thank_page"]
			) ) ?>
			<br>
			<p class="description"><?php _e( 'Page where user will be redirected to after processing an appointment.', 'appointments' ) ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row"><label
				for="allow_worker_wh"><?php _e( 'Allow service provider set working hours', 'appointments' ) ?></label>
		</th>
		<td>
			<select name="allow_worker_wh" id="allow_worker_wh">
				<option value="no" <?php if ( @$options['allow_worker_wh'] <> 'yes' )
					echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
				<option value="yes" <?php if ( @$options['allow_worker_wh'] == 'yes' )
					echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
			</select>
			<br>
			<p class="description"><?php _e( 'Whether you let service providers to set their working/break hours, exceptional days using their profile page or their navigation tab in BuddyPress.', 'appointments' ) ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row"><label
				for="allow_worker_confirm"><?php _e( 'Allow service provider confirm own appointments', 'appointments' ) ?></label>
		</th>
		<td colspan="2">
			<select name="allow_worker_confirm" id="allow_worker_confirm">
				<option value="no" <?php if ( @$options['allow_worker_confirm'] <> 'yes' )
					echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
				<option
					value="yes" <?php if ( @$options['allow_worker_confirm'] == 'yes' )
					echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
			</select>
			<br>
			<p class="description"><?php _e( 'Whether you let service providers to confirm pending appointments assigned to them using their profile page.', 'appointments' ) ?></p>
		</td>
	</tr>


	<tr>
		<th scope="row"><label
				for="dummy_assigned_to"><?php _e( 'Assign dummy service providers to', 'appointments' ) ?></label></th>
		<td colspan="2">
			<?php
			wp_dropdown_users( array(
				'show_option_all' => __( 'None', 'appointments' ),
				'show'            => 'user_login',
				'selected'        => isset( $options["dummy_assigned_to"] ) ? $options["dummy_assigned_to"] : 0,
				'name'            => 'dummy_assigned_to'
			) );
			?>
			<span
				class="description"><?php _e( 'You can define "Dummy" service providers to enrich your service provider alternatives and variate your working schedules. They will behave exactly like ordinary users except the emails they are supposed to receive will be forwarded to the user you select here. Note: You cannot select another dummy user. It must be a user which is not set as dummy.', 'appointments' ) ?></span>
		</td>
	</tr>

	<tr>
		<th scope="row"><label for="login_required"><?php _e( 'Login required', 'appointments' ) ?></label></th>
		<td colspan="2">
			<select name="login_required" id="login_required">
				<option value="no" <?php if ( @$options['login_required'] != 'yes' )
					echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
				<option value="yes" <?php if ( @$options['login_required'] == 'yes' )
					echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
			</select>
			<br>
			<p class="description"><?php _e( 'Whether you require the client to login to the website to apply for an appointment. Plugin lets front end logins, without the need for leaving the front end appointment page.', 'appointments' ) ?></p>
		</td>
	</tr>

	<?php
	if ( 'yes' != $options["login_required"] ) {
		$style = 'style="display:none"';
	} else {
		$style = '';
	}
	?>

	<tr class="api_detail" <?php echo $style ?>>
		<th scope="row"><label
				for="accept_api_logins"><?php _e( 'Accept login from front end', 'appointments' ) ?></label></th>
		<td>
			<input type="checkbox" id="accept_api_logins" name="accept_api_logins"
			       value="true" <?php if ( isset( $options["accept_api_logins"] ) && $options["accept_api_logins"] )
				echo "checked='checked'" ?>>
			<p class="description"><?php _e( 'Enables login to website from front end using Facebook, Twitter, Google+ or Wordpress.', 'appointments' ) ?></p>
		</td>
	</tr>

	<tr class="api_detail" <?php echo $style ?>>
		<th scope="row"><label
				for="facebook-no_init"><?php _e( 'My website already uses Facebook', 'appointments' ) ?></label></th>
		<td>
			<input type="checkbox" name="facebook-no_init" id="facebook-no_init"
			       value="true" <?php if ( isset( $options["facebook-no_init"] ) && $options["facebook-no_init"] )
				echo "checked='checked'" ?>>
			<p class="description"><?php _e( 'By default, Facebook script will be loaded by the plugin. If you are already running Facebook scripts, to prevent any conflict, check this option.', 'appointments' ) ?></p>
		</td>
	</tr>

	<tr class="api_detail" <?php echo $style ?>>
		<th scope="row"><label for="facebook-app_id"><?php _e( 'Facebook App ID', 'appointments' ) ?></label></th>
		<td>
			<input type="text" class="widefat" name="facebook-app_id" id="facebook-app_id"
			       value="<?php if ( isset( $options["facebook-app_id"] ) ) {
				       echo esc_attr( $options["facebook-app_id"] );
			       } ?>"/>
			<p class="description"><?php printf( __( "Enter your App ID number here. If you don't have a Facebook App yet, you will need to create one <a href='%s'>here</a>", 'appointments' ), 'https://developers.facebook.com/apps' ) ?></p>
		</td>
	</tr>

	<tr class="api_detail" <?php echo $style ?>>
		<th scope="row"><label for="twitter-app_id"><?php _e( 'Twitter Consumer Key', 'appointments' ) ?></label></th>
		<td>
			<input type="text" class="widefat" name="twitter-app_id" id="twitter-app_id"
			       value="<?php if ( isset( $options["twitter-app_id"] ) ) {
				       echo esc_attr( $options["twitter-app_id"] );
			       } ?>"/>
			<p class="description"><?php printf( __( 'Enter your Twitter App ID number here. If you don\'t have a Twitter App yet, you will need to create one <a href="%s">here</a>', 'appointments' ), 'https://dev.twitter.com/apps/new' ) ?></p>
		</td>
	</tr>

	<tr class="api_detail" <?php echo $style ?>>
		<th scope="row"><label for="twitter-app_secret"><?php _e( 'Twitter Consumer Secret', 'appointments' ) ?></label>
		</th>
		<td>
			<input type="text" class="widefat" name="twitter-app_secret" id="twitter-app_secret"
			       value="<?php if ( isset( $options["twitter-app_secret"] ) ) {
				       echo esc_attr( $options["twitter-app_secret"] );
			       } ?>"/>
			<p class="description"><?php _e( 'Enter your Twitter App ID Secret here.', 'appointments' ) ?></p>
		</td>
	</tr>
	<tr class="api_detail" <?php echo $style ?>>
		<th scope="row"><label for="google-client_id"><?php _e( 'Google Client ID', 'appointments' ) ?></label></th>
		<td>
			<input type="text" class="widefat" name="google-client_id" id="google-client_id"
			       value="<?php if ( isset( $options["google-client_id"] ) ) {
				       echo esc_attr( $options["google-client_id"] );
			       } ?>"/>
			<p class="description">
				<?php printf( __( 'Enter your Google App Client ID here. If you don\'t have a Google App yet, you will need to create one <a href="%s">here</a>', 'appointments' ), 'https://console.developers.google.com/' ); ?>
				.
				<a class="app-info_trigger" data-target="gauth-instructions"
				   href="#gauth-instructions"><?php _e( 'Show me how', 'appointments' ); ?></a>
			</p>
			<p class="description">
				<?php _e( 'If you leave this field empty, Google Auth will revert to legacy OpenID.', 'appointments' ); ?>
				<b><?php _e( 'The legacy OpenID has been deprecated by Google, and will not work if the domain for your site wasn\'t set up to use it before May 2014.', 'appointments' ); ?></b>
			</p>
			<div class="description app-info_target gauth-instructions">
				<h4><?php _e( 'Creating and setting up a Google Application to work with Appointments Plus authentication', 'appointments' ); ?></h4>
				<p><?php _e( 'Before we begin, you need to <a target="_blank" href="https://console.developers.google.com/">create a Google Application', 'appointments' ); ?></a>
					.</p>
				<p><?php _e( 'To do so, follow these steps:', 'appointments' ); ?></p>
				<ol>
					<li><a target="_blank"
					       href="https://console.developers.google.com/"><?php _e( 'Create your application', 'appointments' ); ?></a>
					</li>
					<li><?php _e( 'Click <em>Create Project</em> button', 'appointments' ); ?></li>
					<li><?php _e( 'In the left sidebar, select <em>APIs & auth</em>.', 'appointments' ); ?></li>
					<li><?php _e( 'Find the <em>Google+ API</em> service and set its status to <em>ON</em>.', 'appointments' ); ?></li>
					<li><?php _e( 'In the sidebar, select <em>Credentials</em>, then in the <em>OAuth</em> section of the page, select <em>Create New Client ID</em>.', 'appointments' ); ?></li>
					<li><?php _e( 'In the <em>Application type</em> section of the dialog, select <em>Web application</em>.', 'appointments' ); ?></li>
					<li><?php _e( 'In the <em>Authorized JavaScript origins</em> field, enter the origin for your app. You can enter multiple origins to allow for your app to run on different protocols, domains, or subdomains.', 'appointments' ); ?></li>
					<li><?php _e( 'In the <em>Authorized redirect URI</em> field, delete the default value.', 'appointments' ); ?></li>
					<li><?php _e( 'Select <em>Create Client ID</em>.', 'appointments' ); ?></li>
					<li><?php _e( 'Copy the value of the field labeled <em>Client ID</em>, and enter it in the text field in plugin settings labeled <strong>Google Client ID</strong>', 'appointments' ); ?></li>
				</ol>
			</div>

		</td>
	</tr>
	<?php do_action( 'app-settings-accessibility_settings', $style ); ?>
</table>
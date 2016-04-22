<?php
global $appointments, $wpdb;

$options = appointments_get_options();
$base_times = $appointments->time_base();
$min_time_setting = isset( $options["min_time"] ) ? $options["min_time"] : '';
?>


<form method="post" action="">

	<div class="app-settings-section" id="app-settings-section-time">
		<h3><?php _e( 'Time Settings', 'appointments' ) ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="min_time"><?php _e( 'Time base (minutes)', 'appointments' ) ?></label></th>
				<td colspan="2">
					<select name="min_time" id="min_time">
						<?php foreach ( $base_times as $min_time ): ?>
							<option value="<?php echo esc_attr( $min_time ); ?>" <?php selected( $min_time, $min_time_setting ); ?>><?php echo esc_html( $min_time ); ?></option>
						<?php endforeach; ?>
					</select>
					<br>
					<p class="description"><?php _e( 'Minimum time that will be effective for durations, appointment and schedule intervals. Service durations can only be set as multiples of this value. Default: 30.', 'appointments' ) ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="additional_min_time"><?php _e( 'Additional time base (minutes)', 'appointments' ) ?></label></th>
				<td>
					<input type="text" class="small-text" name="additional_min_time" id="additional_min_time" value="<?php if ( isset( $options["additional_min_time"] ) ) echo $options["additional_min_time"] ?>"/>
					<br>
					<p class="description"><?php _e( 'If the above time bases do not fit your business, you can add a new one, e.g. 240. Note: After you save this additional time base, you must select it using the above setting. Note: Minimum allowed time base setting is 10 minutes.', 'appointments' ) ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="admin_min_time"><?php _e( 'Admin side time base (minutes)', 'appointments' ) ?></label></th>
				<td>
					<input type="text" class="small-text" name="admin_min_time" id="admin_min_time" value="<?php if ( isset( $options["admin_min_time"] ) ) echo $options["admin_min_time"] ?>"/>
					<br>
					<p class="description"><?php _e( 'This setting may be used to provide flexibility while manually setting and editing the appointments. For example, if you enter here 15, you can reschedule an appointment for 15 minutes intervals even selected time base is 45 minutes. If you leave this empty, then the above selected time base will be applied on the admin side.', 'appointments' ) ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="app_lower_limit"><?php _e( 'Appointments lower limit (hours)', 'appointments' ) ?></label></th>
				<td>
					<input type="text" style="width:50px" name="app_lower_limit" id="app_lower_limit" value="<?php if ( isset( $options["app_lower_limit"] ) ) echo $options["app_lower_limit"] ?>"/>
					<br>
					<p class="description"><?php _e( 'This will block time slots to be booked with the set value starting from current time. For example, if you need 2 days to evaluate and accept an appointment, enter 48 here. Default: 0 (no blocking - appointments can be made if end time has not been passed)', 'appointments' ) ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="app_limit"><?php _e( 'Appointments upper limit (days)', 'appointments' ) ?></label></th>
				<td>
					<input type="text" class="small-text" name="app_limit" id="app_limit" value="<?php if ( isset( $options["app_limit"] ) ) echo $options["app_limit"] ?>"/>
					<br>
					<p class="description"><?php _e( 'Maximum number of days from today that a client can book an appointment. Default: 365', 'appointments' ) ?></p>
			</tr>

			<tr>
				<th scope="row"><label for="clear_time"><?php _e( 'Disable pending appointments after (mins)', 'appointments' ) ?></label></th>
				<td>
					<input type="text" class="small-text" name="clear_time" id="clear_time" value="<?php if ( isset( $options["clear_time"] ) ) echo $options["clear_time"] ?>"/>
					<br>
					<p class="description"><?php _e( 'Pending appointments will be automatically removed (not deleted - deletion is only possible manually) after this set time and that appointment time will be freed. Enter 0 to disable. Default: 60. Please note that pending and GCal reserved appointments whose starting time have been passed will always be removed, regardless of any other setting.', 'appointments' ) ?></p>
			</tr>

			<tr>
				<th scope="row"><label for="spam_time"><?php _e( 'Minimum time to pass for new appointment (secs)', 'appointments' ) ?></label></th>
				<td>
					<input type="text" class="small-text" name="spam_time" id="spam_time" value="<?php if ( isset( $options["spam_time"] ) ) echo $options["spam_time"] ?>"/>
					<br>
					<p class="description"><?php _e( 'You can limit appointment application frequency to prevent spammers who can block your appointments. This is only applied to pending appointments. Enter 0 to disable. Tip: To prevent any further appointment applications of a client before a payment or manual confirmation, enter a huge number here.', 'appointments' ) ?></p>
			</tr>
			<?php do_action( 'app-settings-time_settings' ); ?>
		</table>
	</div>


	<div class="app-settings-section" id="app-settings-section-accesibility">
		<h3><?php _e( 'Accessibility Settings', 'appointments' ); ?></h3>
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
			<th scope="row"><label for="allow_cancel"><?php _e( 'Allow client cancel own appointments', 'appointments' ) ?></label></th>
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
			<th scope="row"><label for="cancel_page"><?php _e( 'Appointment cancelled page', 'appointments' ) ?></label></th>
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
			<th scope="row"><label for="allow_worker_wh"><?php _e( 'Allow service provider set working hours', 'appointments' ) ?></label></th>
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
			<th scope="row"><label for="allow_worker_confirm"><?php _e( 'Allow service provider confirm own appointments', 'appointments' ) ?></label></th>
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
			<th scope="row"><label for="dummy_assigned_to"><?php _e( 'Assign dummy service providers to', 'appointments' ) ?></label></th>
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
			<th scope="row"><label for="accept_api_logins"><?php _e( 'Accept login from front end', 'appointments' ) ?></label></th>
			<td>
				<input type="checkbox" id="accept_api_logins" name="accept_api_logins" value="true" <?php if ( isset( $options["accept_api_logins"] ) && $options["accept_api_logins"] ) echo "checked='checked'" ?>>
				<p class="description"><?php _e( 'Enables login to website from front end using Facebook, Twitter, Google+ or Wordpress.', 'appointments' ) ?></p>
			</td>
		</tr>

		<tr class="api_detail" <?php echo $style ?>>
			<th scope="row"><label for="facebook-no_init"><?php _e( 'My website already uses Facebook', 'appointments' ) ?></label></th>
			<td>
				<input type="checkbox" name="facebook-no_init" id="facebook-no_init" value="true" <?php if ( isset( $options["facebook-no_init"] ) && $options["facebook-no_init"] ) echo "checked='checked'" ?>>
				<p class="description"><?php _e( 'By default, Facebook script will be loaded by the plugin. If you are already running Facebook scripts, to prevent any conflict, check this option.', 'appointments' ) ?></p>
			</td>
		</tr>

		<tr class="api_detail" <?php echo $style ?>>
			<th scope="row"><label for="facebook-app_id"><?php _e( 'Facebook App ID', 'appointments' ) ?></label></th>
			<td>
				<input type="text" class="widefat" name="facebook-app_id" id="facebook-app_id" value="<?php if ( isset( $options["facebook-app_id"] ) ) { echo esc_attr( $options["facebook-app_id"] ); } ?>"/>
				<p class="description"><?php printf( __( "Enter your App ID number here. If you don't have a Facebook App yet, you will need to create one <a href='%s'>here</a>", 'appointments' ), 'https://developers.facebook.com/apps' ) ?></p>
			</td>
		</tr>

		<tr class="api_detail" <?php echo $style ?>>
			<th scope="row"><label for="twitter-app_id"><?php _e( 'Twitter Consumer Key', 'appointments' ) ?></label></th>
			<td>
				<input type="text" class="widefat" name="twitter-app_id" id="twitter-app_id" value="<?php if ( isset( $options["twitter-app_id"] ) ) { echo esc_attr( $options["twitter-app_id"] ); } ?>"/>
				<p class="description"><?php printf( __( 'Enter your Twitter App ID number here. If you don\'t have a Twitter App yet, you will need to create one <a href="%s">here</a>', 'appointments' ), 'https://dev.twitter.com/apps/new' ) ?></p>
			</td>
		</tr>

		<tr class="api_detail" <?php echo $style ?>>
			<th scope="row"><label for="twitter-app_secret"><?php _e( 'Twitter Consumer Secret', 'appointments' ) ?></label></th>
			<td>
				<input type="text" class="widefat" name="twitter-app_secret" id="twitter-app_secret" value="<?php if ( isset( $options["twitter-app_secret"] ) ) { echo esc_attr( $options["twitter-app_secret"] ); } ?>"/>
				<p class="description"><?php _e( 'Enter your Twitter App ID Secret here.', 'appointments' ) ?></p>
			</td>
		</tr>
		<tr class="api_detail" <?php echo $style ?>>
			<th scope="row"><label for="google-client_id"><?php _e( 'Google Client ID', 'appointments' ) ?></label></th>
			<td>
				<input type="text" class="widefat" name="google-client_id" id="google-client_id" value="<?php if ( isset( $options["google-client_id"] ) ) { echo esc_attr( $options["google-client_id"] ); } ?>"/>
				<p class="description">
					<?php printf( __( 'Enter your Google App Client ID here. If you don\'t have a Google App yet, you will need to create one <a href="%s">here</a>', 'appointments' ), 'https://console.developers.google.com/' ); ?> .
					<a class="app-info_trigger" data-target="gauth-instructions" href="#gauth-instructions"><?php _e( 'Show me how', 'appointments' ); ?></a>
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
	</div>

	
	<div class="app-settings-section" id="app-settings-section-display">
		<h3><?php _e( 'Display Settings', 'appointments' ) ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="make_an_appointment"><?php _e( 'Create an Appointment Page', 'appointments' ) ?></label></th>
				<td>
					<input type="checkbox" id="make_an_appointment" name="make_an_appointment" <?php checked( isset( $options["make_an_appointment"] ) && $options["make_an_appointment"] ); ?> />
					&nbsp;<?php _e( 'with', 'appointments' ) ?>&nbsp;
					<label for="app_page_type" class="screen-reader-text"><?php _e( 'Create an appointments date with this format', 'appointments' ); ?></label>
					<select name="app_page_type" id="app_page_type">
						<option value="one_month"><?php _e( 'current month\'s schedule', 'appointments' ) ?></option>
						<option value="two_months" <?php selected( 'two_months' == @$options["app_page_type"] ); ?>><?php _e( 'current and next month\'s schedules', 'appointments' ) ?></option>
						<option value="one_week" <?php selected( 'one_week' == @$options["app_page_type"] ); ?>><?php _e( 'current week\'s schedule', 'appointments' ) ?></option>
						<option value="two_weeks" <?php selected( 'two_weeks' == @$options["app_page_type"] ); ?>><?php _e( 'current and next week\'s schedules', 'appointments' ) ?></option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="show_legend"><?php _e( 'Show Legend', 'appointments' ) ?></label></th>
				<td>
					<select name="show_legend" id="show_legend">
						<option value="no" <?php selected( @$options['show_legend'] <> 'yes' ); ?>><?php _e( 'No', 'appointments' ) ?></option>
						<option value="yes" <?php selected( @$options['show_legend'] == 'yes' ); ?>><?php _e( 'Yes', 'appointments' ) ?></option>
					</select>
					<p class="description"><?php _e( 'Whether to display description fields above the pagination area.', 'appointments' ) ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="color_set"><?php _e( 'Color Set', 'appointments' ) ?></label></th>
				<td>
					<select name="color_set" id="color_set">
						<option value="1" <?php selected( @$options['color_set'] == 1 ); ?>><?php _e( 'Preset 1', 'appointments' ) ?></option>
						<option value="2" <?php selected( @$options['color_set'] == 2 ); ?>><?php _e( 'Preset 2', 'appointments' ) ?></option>
						<option value="3" <?php selected( @$options['color_set'] == 3 ); ?>><?php _e( 'Preset 3', 'appointments' ) ?></option>
						<option value="0" <?php selected( @$options['color_set'] == 0 ); ?>><?php _e( 'Custom', 'appointments' ) ?></option>
					</select>

					<p class="preset_samples" <?php if ( @$options['color_set'] == 0 ) echo 'style="display:none"' ?>>
						<?php foreach ( $appointments->get_classes() as $class => $name ): ?>
							<label>
								<span> <?php echo $name ?>: </span>
								<span>
									<a href="javascript:void(0)" class="pickcolor <?php echo $class ?> hide-if-no-js" <?php if ( @$options['color_set'] != 0 )
										echo 'style="background-color:#' . $appointments->get_preset( $class, $options['color_set'] ) . '"' ?>>
									</a>
								</span>
							</label>
						<?php endforeach; ?>
					</p>
				</td>
			</tr>


			<tr class="custom_color_row" <?php if ( @$options['color_set'] != 0 ) echo 'style="display:none"' ?>>
				<th scope="row"><?php _e( 'Custom Color Set', 'appointments' ) ?></th>
				<td colspan="2">
					<?php foreach ( $appointments->get_classes() as $class => $name ): ?>
						<label style="width:31%;display:block;float:left;">
							<span style="float:left"><?php echo $name ?>:</span>
							<span style="float:left;margin-right:8px;">
								<a href="javascript:void(0)" class="pickcolor hide-if-no-js" <?php if ( isset( $options[ $class . "_color" ] ) ) echo 'style="background-color:#' . $options[ $class . "_color" ] . '"' ?>></a>
								<input style="width:50px" type="text" class="colorpicker_input" maxlength="6" name="<?php echo $class ?>_color" id="<?php echo $class ?>_color" value="<?php if ( isset( $options[ $class . "_color" ] ) ) echo $options[ $class . "_color" ] ?>"/>
							</span>
						</label>
					<?php endforeach; ?>
					<div style="clear:both"></div>
					<span class="description"><?php _e( 'If you have selected Custom color set, for each cell enter 3 OR 6-digit Hex code of the color manually without # in front or use the colorpicker.', 'appointments' ) ?></span>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Require these from the client:', 'appointments' ) ?></th>
				<td colspan="2">
					<input type="checkbox" id="ask_name" name="ask_name" <?php if ( isset( $options["ask_name"] ) && $options["ask_name"] ) echo 'checked="checked"' ?> />&nbsp;<label for="ask_name"><?php echo $appointments->get_field_name( 'name' ) ?></label>
					&nbsp;&nbsp;&nbsp;
					<input type="checkbox" id="ask_email" name="ask_email" <?php if ( isset( $options["ask_email"] ) && $options["ask_email"] ) echo 'checked="checked"' ?> />&nbsp;<label for="ask_email"><?php echo $appointments->get_field_name( 'email' ) ?></label>
					&nbsp;&nbsp;&nbsp;
					<input type="checkbox" id="ask_phone" name="ask_phone" <?php if ( isset( $options["ask_phone"] ) && $options["ask_phone"] ) echo 'checked="checked"' ?> />&nbsp;<label for="ask_phone"><?php echo $appointments->get_field_name( 'phone' ) ?></label>
					&nbsp;&nbsp;&nbsp;
					<input type="checkbox" id="ask_address" name="ask_address" <?php if ( isset( $options["ask_address"] ) && $options["ask_address"] ) echo 'checked="checked"' ?> />&nbsp;<label for="ask_address"><?php echo $appointments->get_field_name( 'address' ) ?></label>
					&nbsp;&nbsp;&nbsp;
					<input type="checkbox" id="ask_city" name="ask_city" <?php if ( isset( $options["ask_city"] ) && $options["ask_city"] ) echo 'checked="checked"' ?> />&nbsp;<label for="ask_city"><?php echo $appointments->get_field_name( 'city' ) ?></label>
					&nbsp;&nbsp;&nbsp;
					<input type="checkbox" id="ask_note" name="ask_note" <?php if ( isset( $options["ask_note"] ) && $options["ask_note"] ) echo 'checked="checked"' ?> />&nbsp;<label for="ask_note"><?php echo $appointments->get_field_name( 'note' ) ?></label>
					&nbsp;&nbsp;&nbsp;
					<br/>
					<p class="description"><?php _e( 'The selected fields will be available in the confirmation area and they will be asked from the client. If selected, filling of them is mandatory (except note field).', 'appointments' ) ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="additional_css"><?php _e( 'Additional css Rules', 'appointments' ) ?></label></th>
				<td colspan="2">
					<textarea class="widefat" rows="6" name="additional_css" id="additional_css"><?php echo esc_textarea( $options['additional_css'] ); ?></textarea>
					<p class="description"><?php _e( 'You can add css rules to customize styling. These will be added to the front end appointment page only.', 'appointments' ) ?></p>
				</td>
			</tr>
			<?php do_action( 'app-settings-display_settings' ); ?>
		</table>
	</div>


	<div class="app-settings-section" id="app-settings-section-payments">
		<?php
		if ( empty( $options['payment_required'] ) ) {
			$options['payment_required'] = 'no';
		}
		$use_payments = ( 'yes' == $options['payment_required'] );
		?>
		<h3><?php _e( 'Payment Settings', 'appointments' ); ?></h3>
		<table class="form-table">

				<tr>
					<th scope="row"><label for="payment_required"><?php _e( 'Payment required', 'appointments' ) ?></label></th>
					<td>
						<select name="payment_required" id="payment_required">
							<option value="no" <?php if ( ! $use_payments ) echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
							<option value="yes" <?php if ( $use_payments ) echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
						</select>
						<p class="description"><?php printf( __( 'Whether you require a payment to accept appointments. If selected Yes, client is asked to pay through Paypal and the appointment will be in pending status until the payment is confirmed by Paypal IPN. If selected No, appointment will be in pending status until you manually approve it using the %s unless Auto Confirm is not set as Yes.', 'appointments' ), '<a href="' . admin_url( 'admin.php?page=appointments' ) . '">' . __( 'Appointments page', 'appointments' ) . '</a>' ) ?></p>
					</td>
				</tr>

				<tr class="payment_row" <?php if ( ! $use_payments ) echo 'style="display:none"' ?>>
					<th scope="row"><label for="currency"><?php _e( 'Paypal Currency', 'appointments' ) ?></label></th>
					<td colspan="2">
						<select name="currency" id="currency">
							<?php
							$sel_currency = ( $options['currency'] ) ? $options['currency'] : $options['currency'];
							$currencies   = App_Template::get_currencies();

							foreach ( $currencies as $k => $v ) {
								echo '<option value="' . $k . '"' . ( $k == $sel_currency ? ' selected' : '' ) . '>' . esc_html( $v, true ) . '</option>' . "\n";
							}
							?>
						</select>
					</td>
				</tr>
				<tr class="payment_row" <?php if ( ! $use_payments ) echo 'style="display:none"' ?>>
					<th scope="row"><label for="mode"><?php _e( 'PayPal Mode', 'appointments' ) ?></label></th>
					<td>
						<select name="mode" id="mode">
							<option value="sandbox"<?php selected( $options['mode'], 'sandbox' ) ?>><?php _e( 'Sandbox', 'appointments' ) ?></option>
							<option value="live"<?php selected( $options['mode'], 'live' ) ?>><?php _e( 'Live', 'appointments' ) ?></option>
						</select>
					</td>
				</tr>

				<tr class="payment_row" <?php if ( ! $use_payments ) echo 'style="display:none"' ?>>
					<th scope="row"><label for="merchant_email"><?php _e( 'PayPal Merchant E-mail', 'appointments' ) ?></label></th>
					<td colspan="2">
						<input value="<?php echo esc_attr( $options['merchant_email'] ); ?>" size="30" name="merchant_email" id="merchant_email" type="text"/>
						<p class="description"> <?php printf( __( 'Just for your information, your IPN link is: <b>%s </b>. You may need this information in some cases.', 'appointments' ), admin_url( 'admin-ajax.php?action=app_paypal_ipn' ) ); ?> </p>
					</td>
				</tr>

				<tr class="payment_row" <?php if ( ! $use_payments ) echo 'style="display:none"' ?>>
					<th scope="row"><label for="return"><?php _e( 'Thank You Page', 'appointments' ) ?></label></th>
					<td colspan="2">
						<?php wp_dropdown_pages( array(
							"show_option_none"   => __( 'Home page', 'appointments' ),
							"option_none_value " => 0,
							"name"               => "return",
							"selected"           => @$options["return"]
						) ) ?>
						<p class="description"><?php _e( 'The page that client will be returned when he clicks the return link on Paypal website.', 'appointments' ) ?></p>
					</td>
				</tr>

				<tr class="payment_row" <?php if ( ! $use_payments ) echo 'style="display:none"' ?>>
					<th scope="row"><label for="percent_deposit"><?php _e( 'Deposit (%)', 'appointments' ) ?></label></th>
					<td colspan="2">
						<input value="<?php echo esc_attr( @$options['percent_deposit'] ); ?>" style="width:50px" name="percent_deposit" id="percent_deposit" type="text"/>
						<p class="description"><?php _e( 'You may want to ask a certain percentage of the service price as deposit, e.g. 25. Leave this field empty to ask for full price.', 'appointments' ) ?></p>
					</td>
				</tr>

				<tr class="payment_row" <?php if ( ! $use_payments ) echo 'style="display:none"' ?>>
					<th scope="row"><label for="fixed_deposit"><?php _e( 'Deposit (fixed)', 'appointments' ) ?></label></th>
					<td colspan="2">
						<input value="<?php echo esc_attr( @$options['fixed_deposit'] ); ?>" style="width:50px" name="fixed_deposit" id="fixed_deposit" type="text"/>
						<p class="description"><?php _e( 'Same as above, but a fixed deposit will be asked from the client per appointment. If both fields are filled, only the fixed deposit will be taken into account.', 'appointments' ) ?></p>
					</td>
				</tr>

				<tr class="payment_row" <?php if ( ! $use_payments ) echo 'style="display:none"' ?>>
					<th scope="row"><label for="allow_free_autoconfirm"><?php _e( 'Allow zero-priced appointments auto-confirm', 'appointments' ) ?></label></th>
					<td colspan="2">
						<input value="1" <?php checked( true, @$options['allow_free_autoconfirm'] ); ?> name="allow_free_autoconfirm" id="allow_free_autoconfirm" type="checkbox"/>
						<p class="description"><?php _e( 'Allow auto-confirm for zero-priced appointments in a paid environment.', 'appointments' ) ?></p>
					</td>
				</tr>

				<tr class="payment_row" style="<?php if ( ! $use_payments ) { echo 'display:none;'; } ?>border-top: 1px solid lightgrey;">
					<th scope="row">&nbsp;</th>
					<td>
						<p class="description"><?php printf( __( 'The below fields require %s plugin. ', 'appointments' ), '<a href="http://premium.wpmudev.org/project/e-commerce/" target="_blank">MarketPress</a>' ) ?></p>
					</td>
				</tr>

				<tr class="payment_row" <?php if ( ! $use_payments ) {
					echo 'style="display:none"';
				} ?>>
					<th scope="row"><?php _e( 'Integrate with MarketPress', 'appointments' ) ?></th>
					<td colspan="2">
						<input type="checkbox"
						       name="use_mp" <?php if ( isset( $options["use_mp"] ) && $options["use_mp"] )
							echo 'checked="checked"' ?> />
		<span
			class="description"><?php _e( 'Appointments can be set as products. Any appointment shortcode added to a product page will make that page an "Appointment Product Page". For details, please see FAQ.', 'appointments' ) ?>
			<?php if ( ! $appointments->marketpress_active ) {
				echo '<br />';
				_e( 'Note: MarketPress is not actived on this website', 'appointments' );
			} ?>
		</span>
					</td>
				</tr>

				<?php do_action( 'app-settings-payment_settings-marketpress' ); ?>

				<tr class="payment_row" <?php if ( ! $use_payments ) {
					echo 'style="display:none"';
				} ?>>
					<th scope="row"><?php _e( 'Create an Appointment Product Page', 'appointments' ) ?></th>
					<td colspan="2">
						<input type="checkbox"
						       name="make_an_appointment_product" <?php if ( isset( $options["make_an_appointment_product"] ) && $options["make_an_appointment_product"] )
							echo 'checked="checked"' ?> />
						&nbsp;<?php _e( 'with', 'appointments' ) ?>&nbsp;
						<select name="app_page_type_mp">
							<option
								value="one_month"><?php _e( 'current month\'s schedule', 'appointments' ) ?></option>
							<option
								value="two_months" <?php if ( 'two_months' == @$options["app_page_type_mp"] )
								echo 'selected="selected"' ?>><?php _e( 'current and next month\'s schedules', 'appointments' ) ?></option>
							<option
								value="one_week" <?php if ( 'one_week' == @$options["app_page_type_mp"] )
								echo 'selected="selected"' ?>><?php _e( 'current week\'s schedule', 'appointments' ) ?></option>
							<option
								value="two_weeks" <?php if ( 'two_weeks' == @$options["app_page_type_mp"] )
								echo 'selected="selected"' ?>><?php _e( 'current and next week\'s schedules', 'appointments' ) ?></option>
						</select>
						<br/>
						<span
							class="description"><?php _e( 'Same as the above "Create an Appointment Page", but this time appointment shortcodes will be inserted in a new Product page and page title will be "Appointment". This is also the product name.', 'appointments' ) ?></span>
						<?php
						$page_id = $wpdb->get_var( "SELECT ID FROM " . $wpdb->posts . " WHERE post_title = 'Appointment' AND post_type='product' " );
						if ( $page_id ) { ?>
							<br/><span
								class="description"><?php _e( '<b>Note:</b> You already have such a page. If you check this checkbox, another page with the same title will be created. To edit existing page: ', 'appointments' ) ?>
								<a href="<?php echo admin_url( 'post.php?post=' . $page_id . '&action=edit' ) ?>"
								   target="_blank"><?php _e( 'Click here', 'appointments' ) ?></a>
		&nbsp;
								<?php _e( 'To view the page:', 'appointments' ) ?>
								<a href="<?php echo get_permalink( $page_id ) ?>"
								   target="_blank"><?php _e( 'Click here', 'appointments' ) ?></a>
	</span>
						<?php }
						?>
					</td>
				</tr>

				<?php
				/**
				 * Integrations or add-ons can use this action to add their own payment
				 * settings to the form.
				 */
				do_action( 'app_settings_form_payment', $options, $use_payments );
				?>

			</table>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				$('select[name="payment_required"]').change(function () {
					if ($('select[name="payment_required"]').val() == "yes") {
						$(".payment_row").show();
					}
					else {
						$(".payment_row").hide();
					}
				});
			});
		</script>
	</div>


	<div class="app-settings-section" id="app-settings-section-notifications">
		<h3><?php _e( 'Notification Settings', 'appointments' ) ?></h3>
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
						<textarea cols="90"
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
						<input value="<?php echo esc_attr( $options['reminder_time'] ); ?>" size="90"
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
						<textarea cols="90"
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
						<textarea cols="90"
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
		, CANCEL <?php _e( "(Adds a cancellation link to the email body)", "appointments" ) ?>
	</span>
					</td>
				</tr>

			</table>
	</div>


	<div class="app-settings-section" id="app-settings-section-advanced">
		<h3><?php _e( 'Advanced Settings', 'appointments' ) ?></h3>
		<table class="form-table">

			<tr valign="top">
				<th scope="row"><?php _e( 'Use Built-in Cache', 'appointments' ) ?></th>
				<td colspan="2">
					<select name="use_cache">
						<option value="no" <?php if ( @$options['use_cache'] <> 'yes' )
							echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
						<option value="yes" <?php if ( @$options['use_cache'] == 'yes' )
							echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
					</select>
					<span
						class="description"><?php _e( 'Appointments+ has a built-in cache to increase performance. If you are making changes in the styling of your appointment pages, modifying shortcode parameters or having some issues while using it, disable it by selecting No.', 'appointments' ) ?></span>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Clear Cache', 'appointments' ) ?></th>
				<td colspan="2">
					<input type="checkbox"
					       name="force_cache" <?php if ( isset( $options["force_cache"] ) && $options["force_cache"] )
						echo 'checked="checked"' ?> />
					<span
						class="description"><?php _e( 'Cache is automatically cleared at regular intervals (Default: 10 minutes) or when you change a setting. To clear it manually check this checkbox.', 'appointments' ) ?></span>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Allow Overwork (end of day)', 'appointments' ) ?></th>
				<td colspan="2">
					<select name="allow_overwork">
						<option value="no" <?php if ( @$options['allow_overwork'] <> 'yes' )
							echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
						<option value="yes" <?php if ( @$options['allow_overwork'] == 'yes' )
							echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
					</select>
					<span
						class="description"><?php _e( 'Whether you accept appointments exceeding working hours for the end of day. For example, if you are working until 6pm, and a client asks an appointment for a 60 minutes service at 5:30pm, to allow such an appointment you should select this setting as Yes. Please note that this is only practical if the selected service lasts longer than the base time. Such time slots are marked as "not possible" in the schedule.', 'appointments' ) ?></span>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Allow Overwork (break hours)', 'appointments' ) ?></th>
				<td colspan="2">
					<select name="allow_overwork_break">
						<option value="no" <?php if ( @$options['allow_overwork_break'] <> 'yes' )
							echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
						<option
							value="yes" <?php if ( @$options['allow_overwork_break'] == 'yes' )
							echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
					</select>
					<span
						class="description"><?php _e( 'Same as above, but valid for break hours. If you want to allow appointments exceeding break times, then select this as Yes.', 'appointments' ) ?></span>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Number of appointment records per page', 'appointments' ) ?></th>
				<td colspan="2">
					<input type="text" style="width:50px" name="records_per_page"
					       value="<?php if ( isset( $options["records_per_page"] ) )
						       echo $options["records_per_page"] ?>"/>
					<span
						class="description"><?php _e( 'Number of records to be displayed on admin appointments page. If left empty: 50', 'appointments' ) ?></span>
				</td>
			</tr>
			<?php do_action( 'app-settings-advanced_settings' ); ?>
		</table>
	</div>

	<?php do_action( 'app-settings-after_advanced_settings' ); ?>

	<input type="hidden" name="action_app" value="save_general"/>
	<?php wp_nonce_field( 'update_app_settings', 'app_nonce' ); ?>
	<p class="submit">
		<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Settings', 'appointments' ) ?>"/>
	</p>

</form>

<script type="text/javascript">
	jQuery(document).ready(function ($) {
		$('select[name="login_required"]').change(function () {
			if ($('select[name="login_required"] :selected').val() == 'yes') {
				$(".api_detail").show();
			}
			else {
				$(".api_detail").hide();
			}
		});
	});
</script>

<script type="text/javascript">
	jQuery(document).ready(function ($) {
		var hex = new Array;

		$('select[name="color_set"]').change(function () {
			var n = $('select[name="color_set"] :selected').val();
			if (n == 0) {
				$(".custom_color_row").show();
				$(".preset_samples").hide();
			}
			else {
				$(".custom_color_row").hide();
				$(".preset_samples").show();
				<?php foreach ( $appointments->get_classes() as $class => $name ) {
				echo $class . '=new Array;';
				for ( $k = 1; $k <= 3; $k ++ ) {
					echo $class . '[' . $k . '] = "' . $appointments->get_preset( $class, $k ) . '";';
				}
				echo '$(".preset_samples").find("a.' . $class . '").css("background-color", "#"+' . $class . '[n]);';
			} ?>
			}
		});
	});
</script>

<script type="text/javascript">
	jQuery(document).ready(function ($) {
		$('.colorpicker_input').each(function () {
			var id = this.id;
			$('#' + id).ColorPicker({
					onSubmit: function (hsb, hex, rgb, el) {
						$(el).val(hex);
						$(el).ColorPickerHide();
					},
					onBeforeShow: function () {
						$(this).ColorPickerSetColor(this.value);
					},
					onChange: function (hsb, hex, rgb) {
						$('#' + id).val(hex);
						$('#' + id).parent().find('a.pickcolor').css('background-color', '#' + hex);
					}
				})
				.bind('keyup', function () {
					$(this).ColorPickerSetColor(this.value);
				});
			;
		});
		$('.colorpicker_input').keyup(function () {
			var a = $(this).val();
			a = a.replace(/[^a-fA-F0-9]/, '');
			if (a.length === 3 || a.length === 6)
				$(this).parent().find('a.pickcolor').css('background-color', '#' + a);
		});
	});
</script>
<?php

class App_Shortcode_Confirmation extends App_Shortcode {
	public function __construct () {
		$this->_defaults = array(
			'title' => array(
				'value' => __('<h3>Please check the appointment details below and confirm:</h3>', 'appointments'),
				'help' => __('Text above fields. Default: "Please check the appointment details below and confirm:"', 'appointments'),
				'example' => __('Please check the appointment details below and confirm:', 'appointments'),
			),
			'button_text' => array(
				'value' => __('Please click here to confirm this appointment', 'appointments'),
				'help' => __('Text of the button that asks client to confirm the appointment. Default: "Please click here to confirm this appointment"', 'appointments'),
				'example' => __('Please click here to confirm this appointment', 'appointments'),
			),
			'confirm_text' => array(
				'value' => __('We have received your appointment. Thanks!', 'appointments'),
				'help' => __('Javascript text that will be displayed after receiving of the appointment. This will only be displayed if you do not require payment. Default: "We have received your appointment. Thanks!"', 'appointments'),
				'example' => __('We have received your appointment. Thanks!', 'appointments'),
			),
			'warning_text' => array(
				'value' => __('Please fill in the requested field','appointments'),
				'help' => __(' Javascript text displayed if client does not fill a required field. Default: "Please fill in the requested field"', 'appointments'),
				'example' => __('Please fill in the requested field','appointments'),
			),
			'name' => array(
				'value' => __('Your name:','appointments'),
				'help' => __('Descriptive title of the field.', 'appointments'),
				'example' => __('Your name:','appointments'),
			),
			'email' => array(
				'value' => __('Your email:','appointments'),
				'help' => __('Descriptive title of the field.', 'appointments'),
				'example' => __('Your email:','appointments'),
			),
			'phone' => array(
				'value' => __('Your phone:','appointments'),
				'help' => __('Descriptive title of the field.', 'appointments'),
				'example' => __('Your phone:','appointments'),
			),
			'address' => array(
				'value' => __('Your address:','appointments'),
				'help' => __('Descriptive title of the field.', 'appointments'),
				'example' => __('Your address:','appointments'),
			),
			'city' => array(
				'value' => __('City:','appointments'),
				'help' => __('Descriptive title of the field.', 'appointments'),
				'example' => __('City:','appointments'),
			),
			'note' => array(
				'value' => __('Your notes:','appointments'),
				'help' => __('Descriptive title of the field.', 'appointments'),
				'example' => __('Your notes:','appointments'),
			),
			'gcal' => array(
				'value' => __('Access Google Calendar and submit appointment','appointments'),
				'help' => __('Text that will be displayed beside Google Calendar checkbox. Default: "Open Google Calendar and submit appointment"', 'appointments'),
				'example' => __('Access Google Calendar and submit appointment','appointments'),
			),
		);
	}

	public function get_usage_info () {
		return '' .
		       __('Inserts a form which displays the details of the selected appointment and has fields which should be filled by the client.', 'appointments') .
		       '<br />' .
		       __('<b>This shortcode is always required to complete an appointment.</b>', 'appointments') .
		       '';
	}

	public function process_shortcode ($args=array(), $content='') {
		$args = wp_parse_args($args, $this->_defaults_to_args());
		extract( $args );

		global $appointments;

		// Get user form data from his cookie
		if ( isset( $_COOKIE["wpmudev_appointments_userdata"] ) )
			$data = unserialize( stripslashes( $_COOKIE["wpmudev_appointments_userdata"] ) );
		else
			$data = array();

		$n = isset( $data["n"] ) ? sanitize_text_field( $data["n"] ) : ''; // Name
		$e = isset( $data["e"] ) ? sanitize_text_field( $data["e"] ) : ''; // Email
		$p = isset( $data["p"] ) ? sanitize_text_field( $data["p"] ) : ''; // Phone
		$a = isset( $data["a"] ) ? sanitize_text_field( $data["a"] ) : ''; // Address
		$c = isset( $data["c"] ) ? sanitize_text_field( $data["c"] ) : ''; // City
		$g = isset( $data["g"] ) ? sanitize_text_field( $data["g"] ) : ''; // GCal selection
		if ( $g )
			$gcal_checked = ' checked="checked"';
		else
			$gcal_checked = '';

		// User may have already saved his data before
		if ( is_user_logged_in() ) {
			global $current_user;
			$user_info = get_userdata( $current_user->ID );

			$name_meta = get_user_meta( $current_user->ID, 'app_name', true );
			if ( $name_meta )
				$n = $name_meta;
			else if ( $user_info->display_name )
				$n = $user_info->display_name;
			else if ( $user_info->user_nicename )
				$n = $user_info->user_nicename;
			else if ( $user_info->user_login )
				$n = $user_info->user_login;

			$email_meta = get_user_meta( $current_user->ID, 'app_email', true );
			if ( $email_meta )
				$e = $email_meta;
			else if ( $user_info->user_email )
				$e = $user_info->user_email;

			$phone_meta = get_user_meta( $current_user->ID, 'app_phone', true );
			if ( $phone_meta )
				$p = $phone_meta;

			$address_meta = get_user_meta( $current_user->ID, 'app_address', true );
			if ( $address_meta )
				$a = $address_meta;

			$city_meta = get_user_meta( $current_user->ID, 'app_city', true );
			if ( $city_meta )
				$c = $city_meta;
		}
		$ret = '';

		ob_start();

		?>
		<div class="appointments-confirmation-wrapper">
			<fieldset>
				<legend><?php echo $args['title']; ?></legend>
				<div class="appointments-confirmation-service"></div>
				<div class="appointments-confirmation-worker" style="display:none"></div>
				<div class="appointments-confirmation-start"></div>
				<div class="appointments-confirmation-end"></div>
				<div class="appointments-confirmation-price" style="display:none"></div>

				<div class="appointments-name-field" style="display:none">
					<label>
						<span><?php echo $args['name']; ?></span>
						<input type="text" class="appointments-name-field-entry" id="<?php echo esc_attr(apply_filters('app-shortcode-confirmation-name_field_id', 'appointments-field-customer_name')); ?>" value="<?php echo esc_attr( $n ); ?>" />
					</label>
				</div>
				<div class="appointments-email-field" style="display:none">
					<label>
						<span><?php echo $args['email']; ?></span>
						<input type="text" class="appointments-email-field-entry" id="<?php echo esc_attr(apply_filters('app-shortcode-confirmation-email_field_id', 'appointments-field-customer_email')); ?>" value="<?php echo esc_attr( $e ); ?>" />
					</label>
				</div>
				<div class="appointments-phone-field" style="display:none">
					<label>
						<span><?php echo $args['phone']; ?></span>
						<input type="text" class="appointments-phone-field-entry" id="<?php echo esc_attr(apply_filters('app-shortcode-confirmation-phone_field_id', 'appointments-field-customer_phone')); ?>" value="<?php echo esc_attr( $p ); ?>" />
					</label>
				</div>
				<div class="appointments-address-field" style="display:none">
					<label>
						<span><?php echo $args['address']; ?></span>
						<input type="text" class="appointments-address-field-entry" id="<?php echo esc_attr(apply_filters('app-shortcode-confirmation-address_field_id', 'appointments-field-customer_address')); ?>" value="<?php echo esc_attr( $a ); ?>" />
					</label>
				</div>
				<div class="appointments-city-field" style="display:none">
					<label>
						<span><?php echo $args['city']; ?></span>
						<input type="text" class="appointments-city-field-entry" id="<?php echo esc_attr(apply_filters('app-shortcode-confirmation-city_field_id', 'appointments-field-customer_city')); ?>" value="<?php echo esc_attr( $c ); ?>" />
					</label>
				</div>
				<div class="appointments-note-field" style="display:none">
					<label>
						<span><?php echo $args['note']; ?></span>
						<input type="text" class="appointments-note-field-entry" id="<?php echo esc_attr(apply_filters('app-shortcode-confirmation-note_field_id', 'appointments-field-customer_note')); ?>" />
					</label>
				</div>
				<div class="appointments-gcal-field" style="display:none">
					<label>
						<span><?php echo $appointments->gcal_image; ?></span>
						<input type="checkbox" class="appointments-gcal-field-entry" id="<?php echo esc_attr(apply_filters('app-shortcode-confirmation-gcal_field_id', 'appointments-field-customer_gcal')); ?>" <?php echo $gcal_checked; ?> />&nbsp;
						<?php echo $args['gcal']; ?>
					</label>
				</div>
				<?php $ret = apply_filters( 'app_additional_fields', ob_get_clean() ); ?>

				<?php ob_start(); ?>

				<div style="clear:both"></div>

				<div class="appointments-confirmation-buttons">
					<input type="hidden" class="appointments-confirmation-final-value" />
					<input type="button" class="appointments-confirmation-button" value="<?php echo esc_attr( $button_text ); ?>" />
					<input type="button" class="appointments-confirmation-cancel-button" value="<?php echo esc_attr_x( 'Cancel', 'Drop current action', 'appointments' ); ?>" />
				</div>
			</fieldset>
		</div>

		<?php

		$ret  = apply_filters( 'app_confirmation_fields', $ret . ob_get_clean() );

		wp_enqueue_script( 'app-shortcode-confirmation', appointments_plugin_url() . 'includes/shortcodes/js/app-confirmation.js', array( 'jquery' ) );

		$i10n = array(
			'waitingGif' => appointments_plugin_url() . 'images/waiting.gif',
			'isUserLoggedIn' => is_user_logged_in(),
			'loginRequired' => $appointments->options["login_required"],
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce(),
			'askName' => $appointments->options["ask_name"],
			'askEmail' => $appointments->options["ask_email"],
			'askPhone' => $appointments->options["ask_phone"],
			'askAddress' => $appointments->options["ask_address"],
			'askCity' => $appointments->options["ask_city"],
			'askGCal' => isset( $appointments->options["gcal"] ) && 'yes' == $appointments->options["gcal"],
			'warningText' => esc_js( $args['warning_text'] ),
			'confirmationText' => esc_js( $args['confirm_text'] ),
			'connectionErrorText' => esc_js( __('A connection problem occurred. Please try again.','appointments') )
		);
		wp_localize_script( 'app-shortcode-confirmation', 'AppShortcodeConfirmation', $i10n );

		return $ret;
	}
}
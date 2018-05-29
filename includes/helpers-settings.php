<?php

function appointments_get_options() {
	$options = get_option( 'appointments_options', array() );
	return wp_parse_args( $options, appointments_get_default_options() );
}

function appointments_get_option( $name ) {
	$options = appointments_get_options();
	return isset( $options[ $name ] ) ? $options[ $name ] : false;
}

function appointments_get_default_options() {
	$confirmation_message = App_Template::get_default_confirmation_message();
	$reminder_message = App_Template::get_default_reminder_message();

	$gdpr_show = strtolower( _x( 'no', 'Enter "yes" for the European Union countries.', 'appointments' ) );
	if ( ! preg_match( '/^(yes|no)$/', $gdpr_show ) ) {
		$gdpr_show = 'no';
	}

	return apply_filters( 'appointments_default_options', array(
		'min_time'					=> 30,
		'additional_min_time'		=> '',
		'admin_min_time'			=> '',
		'app_lower_limit'			=> 0,
		'app_limit'					=> 365,
		'clear_time'				=> 60,
		'spam_time'					=> 0,
		'auto_confirm'				=> 'no',
		'allow_worker_selection'	=> 'no',
		'allow_worker_confirm'		=> 'no',
		'allow_overwork'			=> 'no',
		'allow_overwork_break'		=> 'no',
		'dummy_assigned_to'			=> 0,
		'app_page_type'				=> 'monthly',
		'accept_api_logins'			=> '',
		'facebook-app_id'			=> '',
		'twitter-app_id'			=> '',
		'twitter-app_secret'		=> '',
		'show_legend'				=> 'yes',
		'gcal'						=> 'yes',
		'gcal_location'				=> '',
		'gcal_overwrite'			=> false,
		'color_set'					=> 1,
		'free_color'				=> '48c048',
		'busy_color'				=> 'ffffff',
		'notpossible_color'			=> 'ffffff',
		'make_an_appointment'		=> '',
		'ask_name'					=> '1',
		'ask_email'					=> '1',
		'ask_phone'					=> '1',
		'ask_address'				=> '',
		'ask_city'					=> '',
		'ask_note'					=> '',
		'additional_css'			=> '.entry-content td{border:none;width:50%}',
		'payment_required'			=> 'no',
		'percent_deposit'			=> '',
		'fixed_deposit'				=> '',
		'currency'					=> 'USD',
		'mode'						=> 'sandbox',
		'merchant_email'			=> '',
		'return'					=> 1,
		'login_required'			=> 'no',
		'send_confirmation'			=> 'yes',
		'send_notification'			=> 'no',
		'send_reminder'				=> 'yes',
		'reminder_time'				=> '24',
		'send_reminder_worker'		=> 'yes',
		'reminder_time_worker'		=> '4',
		'confirmation_subject'		=> __( 'Confirmation of your Appointment','appointments' ),
		'confirmation_message'		=> $confirmation_message,
		'reminder_subject'			=> __( 'Reminder for your Appointment','appointments' ),
		'reminder_message'			=> $reminder_message,
		'log_emails'				=> 'yes',
		'allow_cancel'				=> 'no',
		'cancel_page'				=> 0,
		'thank_page'				=> 0,
		'keep_options_on_uninstall' => true,
		'gdpr_delete'               => 'no',
		'gdpr_number_of_days'       => 28,
		'gdpr_number_of_days_user_erease' => 28,
		'gdpr_checkbox_show'        => $gdpr_show,
		'gdpr_checkbox_text'        => __( 'By using this form you agree with the storage and handling of your data by this website.', 'appointments' ),
		'gdpr_checkbox_alert'       => __( 'Please accept the privacy checkbox.', 'appointments' ),
		'always_load_scripts'       => 'no',
	) );
}

function appointments_update_options( $new_options ) {
	update_option( 'appointments_options', $new_options );
	appointments_delete_timetables_cache();
}

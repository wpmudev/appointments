<?php
/**
 * Contains the default App_Shortcode descendent implementations.
 */

/**
 * Non-default formatting reorder callback.
 * Bound to filter, will actually reorder default WP content formatting.
 */
function app_core_late_map_global_formatting_reorder ($content) {
	if (!preg_match('/\[app_/', $content)) return $content;

	remove_filter('the_content', 'wpautop');
	add_filter('the_content', 'wpautop', 20);
	add_filter('the_content', 'shortcode_unautop', 21);

	return $content;
}

function app_core_shortcodes_register ($shortcodes) {

	// Unless manually enabled...
	if (defined('APP_REORDER_DEFAULT_FORMATTING') && APP_REORDER_DEFAULT_FORMATTING) {
		// ... or disabled by some code ...
		if (has_action('the_content', 'wpautop')) {
			if (defined('APP_DEFAULT_FORMATTING_GLOBAL_REORDER') && APP_DEFAULT_FORMATTING_GLOBAL_REORDER) { // If global define is in place, just do this
				// ... move the default formatting functions higher up the chain
				remove_filter('the_content', 'wpautop');
				add_filter('the_content', 'wpautop', 20);
				add_filter('the_content', 'shortcode_unautop', 21);
			} else add_filter('the_content', 'app_core_late_map_global_formatting_reorder', 0); // With no global formatting, do "the_content" filtering bits only. Note the "0"
		}
	}

	include_once( 'shortcodes/class-app-shortcode-confirmation.php' );
	include_once( 'shortcodes/class-app-shortcode-my-appointments.php' );
	include_once( 'shortcodes/class-app-shortcode-services.php' );
	include_once( 'shortcodes/class-app-shortcode-monthly-worker-calendar.php' );
	include_once( 'shortcodes/class-app-shortcode-service-providers.php' );
	include_once( 'shortcodes/class-app-shortcode-schedule.php' );
	include_once( 'shortcodes/class-app-shortcode-monthly-schedule.php' );
	include_once( 'shortcodes/class-app-shortcode-pagination.php' );
	include_once( 'shortcodes/class-app-shortcode-all-appointments.php' );
	include_once( 'shortcodes/class-app-shortcode-login.php' );
	include_once( 'shortcodes/class-app-shortcode-paypal.php' );

	$shortcodes['app_worker_montly_calendar'] = 'App_Shortcode_WorkerMontlyCalendar'; // Typo :(
	$shortcodes['app_worker_monthly_calendar'] = 'App_Shortcode_WorkerMonthlyCalendar';
	$shortcodes['app_schedule'] = 'App_Shortcode_WeeklySchedule';
	$shortcodes['app_monthly_schedule'] = 'App_Shortcode_Monthly_Schedule';
	$shortcodes['app_pagination'] = 'App_Shortcode_Pagination';
	$shortcodes['app_all_appointments'] = 'App_Shortcode_All_Appointments';
	$shortcodes['app_my_appointments'] = 'App_Shortcode_MyAppointments';
	$shortcodes['app_services'] = 'App_Shortcode_Services';
	$shortcodes['app_service_providers'] = 'App_Shortcode_ServiceProviders';
	$shortcodes['app_login'] = 'App_Shortcode_Login';
	$shortcodes['app_paypal'] = 'App_Shortcode_Paypal';
	$shortcodes['app_confirmation'] = 'App_Shortcode_Confirmation';
	return $shortcodes;
}
add_filter('app-shortcodes-register', 'app_core_shortcodes_register', 1);



/**
 * Register hooks to add a shortcode button to
 * WP Editor
 */
function appointments_add_shortcode_button() {
	add_filter( 'mce_external_plugins', 'appointments_add_shortcode_tinymce_plugin' );
	add_filter( 'mce_buttons', 'appointments_register_shortcode_button' );
	add_filter( 'mce_external_languages', 'appointments_add_tinymce_i18n' );
}
add_action( 'admin_head', 'appointments_add_shortcode_button' );

/**
 * Add the JS needed to handle the shortcodes buttons
 *
 * @param $plugins
 *
 * @return mixed
 */
function appointments_add_shortcode_tinymce_plugin( $plugins ) {
	$plugins['appointments_shortcodes'] = appointments_plugin_url() . 'admin/js/editor-shortcodes.js';
	return $plugins;
}

/**
 * Add the new shortcodes buttons as plugin in TinyMCE
 *
 * @param array $buttons
 *
 * @return array
 */
function appointments_register_shortcode_button( $buttons ) {
	array_push( $buttons, '|', 'appointments_shortcodes' );

	return $buttons;
}

/**
 * TinyMCE buttons i18n
 *
 * @param array $i18n
 *
 * @return array
 */
function appointments_add_tinymce_i18n( $i18n ) {
	$i18n['appointments_shortcodes'] = appointments_plugin_dir() . '/admin/tinymce-shortcodes-i18n.php';
	return $i18n;
}



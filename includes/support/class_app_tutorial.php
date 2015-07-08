<?php

class App_Tutorial {

	private function __construct () {}

	public static function serve () {
		$me = new App_Tutorial;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action( 'admin_init', array($this, 'tutorial1') );							// Add tutorial 1
		add_action( 'admin_init', array($this, 'tutorial2') );							// Add tutorial 2
	}

	function tutorial1() {
		global $appointments;
		//load the file
		if (!class_exists('Pointer_Tutorial')) require_once( $appointments->plugin_dir . '/includes/external/pointer-tutorials.php' );

		//create our tutorial, with default redirect prefs
		$tutorial = new Pointer_Tutorial('app_tutorial1', true, false);

		//add our textdomain that matches the current plugin
		$tutorial->set_textdomain = 'appointments';

		//add the capability a user must have to view the tutorial
		$tutorial->set_capability = App_Roles::get_capability('manage_options', App_Roles::CTX_TUTORIAL);

		$tutorial->add_icon( $appointments->plugin_url . '/images/large-greyscale.png' );

		$title = sanitize_title(__('Appointments', 'appointments'));
		$settings = admin_url('admin.php?page=app_settings');

		$tutorial->add_step($settings, $title . '_page_app_settings', '.menu-top .toplevel_page_appointments', __('Appointments+ Tutorial', 'appointments'), array(
		    'content'  => '<p>' . esc_js( __('Welcome to Appointments+ plugin. This tutorial will hopefully help you to make a quick start by adjusting the most important settings to your needs. You can restart this tutorial any time clicking the link on the FAQ page.', 'appointments' ) ) . '</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($settings, $title . '_page_app_settings', 'select[name="min_time"]', __('Time Base', 'appointments'), array(
		    'content'  => '<p>' . esc_js( __('Time Base is the most important parameter of Appointments+. It is the minimum time that you can select for your appointments. If you set it too high then you may not be possible to optimize your appointments. If you set it too low, your schedule will be too crowded and you may have difficulty in managing your appointments. You should enter here the duration of the shortest service you are providing.', 'appointments' ) ) . '</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($settings, $title . '_page_app_settings', 'input:checkbox[name="make_an_appointment"]', __('Creating a functional front end appointment page', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('Appointments+ provides an easy way of creating an appointment page. Check this checkbox to include all shortcodes in a full functional page. You can later edit this page.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($settings, $title . '_page_app_settings', 'select[name="app_page_type"]', __('Creating a functional front end appointment page', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('You can select a schedule type from the list. To see how they look, you can also create more than one page, one by one and then delete unused ones.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'right', 'align' => 'center' ),
		));

		$tutorial->add_step($settings, $title . '_page_app_settings', 'select[name="color_set"]', __('Selecting a Color Set to Match Your Theme', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('It is possible to select color sets for your schedule tables from predefined sets, or customize them. When you select Custom, you will be able to set your own colors for different statuses (Busy, free, not possible/not working).', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'right', 'align' => 'center' ),
		));

		$tutorial->add_step($settings, $title . '_page_app_settings', 'select[name="login_required"]', __('Do you require login?', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('You can set whether client is required to log into the website to apply for an appointment. When you select this setting as Yes, you will see additional settings.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($settings, $title . '_page_app_settings', 'input:checkbox[name="ask_name"]', __('Requiring information from client', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('You may ask the client to fill some selectable fields so that they may not need to register on your website.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'right', 'align' => 'center' ),
		));

		$tutorial->add_step($settings, $title . '_page_app_settings', 'select[name="payment_required"]', __('Do you require payment?', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('You can set whether client is asked for a payment to accept his appointment. If this setting is selected as Yes, appointment will be in pending status until a succesful Paypal payment is completed. After you select this, you will see additional fields for your Paypal account, deposits and integration with Membership plugin.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($settings, $title . '_page_app_settings', '.notification_settings', __('Email notifications', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('There are several notification settings. Using these, you can confirm and remind your clients and also your service providers.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($settings, $title . '_page_app_settings', 'select[name="use_cache"]', __('Built-in Cache', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('Appointments+ comes with a built-in specific cache. It functions only on appointment pages and caches the content part of the page only. It is recommended to enable it especially if you have a high traffic appointment page. You can continue to use other general purpose caching plugins like W3T, WP Super Cache, Quick Cache.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($settings, $title . '_page_app_settings', '.button-primary', __('Save settings', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('Do not forget to save your settings.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($settings, $title . '_page_app_settings', '#app_tab_working_hours', __('Setting your business hours', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('Now you should set your business working hours. Click Working Hours tab and then click Next.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($settings . '&tab=working_hours', $title . '_page_app_settings', 'select[name="app_provider_id"]', __('Setting your business hours', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('Below you will see two tables, one for your working hours and the other for your break hours during the day. The second one is optional. On the left you will see no selection options yet. But as you add new services providers, you can set their working and break hours by selecting from this dropdown menu. This is only necessary if their working hours are different from those of your business.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($settings . '&tab=working_hours', $title . '_page_app_settings', '.button-primary', __('Save settings', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('Do not forget to save your settings.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($settings . '&tab=working_hours', $title . '_page_app_settings', '#app_tab_exceptions', __('Entering your holidays', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('Click the Exceptions tab to define your holidays and then click Next.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($settings . '&tab=exceptions', $title . '_page_app_settings', 'select[name="app_provider_id"]', __('Setting exceptional days', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('Below you can define your holidays and exceptional working days, for example a specific Sunday you want to work on. These dates will override your weekly working schedule for that day only. Note that you will be able to set these exceptional days for each service provider individually, when you define them.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($settings . '&tab=exceptions', $title . '_page_app_settings', '#app_tab_services', __('Setting your services', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('Click the Services tab to set your services and then click Next.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($settings . '&tab=services', $title . '_page_app_settings', '#add_service', __('Setting your services', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('You can add new service by clicking this button. A default service should have been installed during installation. You can edit and even delete that too, but you should have at least one service in this table.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($settings . '&tab=services', $title . '_page_app_settings', '.button-primary', __('Save settings', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('Do not forget to save your settings. Clicking Add New Service button does NOT save it to the database until you click the Save button.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($settings . '&tab=services', $title . '_page_app_settings', '#app_tab_workers', __('Adding and setting your service providers', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('Click the Service Providers tab to set your service providers and then click Next.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'right', 'align' => 'center' ),
		));

		$tutorial->add_step($settings . '&tab=workers', $title . '_page_app_settings', '#add_worker', __('Adding service providers', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('Adding service providers is optional. You may need this if the working schedule of your service providers are different or you want the client to pick a provider by his name. You can add new service provider by clicking this button. ', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));


		$tutorial->add_step($settings . '&tab=workers', $title . '_page_app_settings', '#app_tab_shortcodes', __('Additional Information', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('You can find detailed information about shortcode parameters on the Shortcodes page and answers to common questions on the FAQ page. Of course we will be glad to help you on our Community pages too.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'right', 'align' => 'center' ),
		));

		$tutorial->add_step($settings . '&tab=workers', $title . '_page_app_settings', 'a.wp-first-item:contains("Appointments")', __('Appointment List', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('As you start receiving appointments, you will see them here. Click on the Appointments menu item to start the other tutorial, if you have not seen it yet.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));


		if ( isset( $_GET["tutorial"] ) && 'restart1' == $_GET["tutorial"] )
			$tutorial->restart();

		//start the tutorial
		$tutorial->initialize();

		return $tutorial;
    }

	function tutorial2() {
		global $appointments;
		//load the file
		if (!class_exists('Pointer_Tutorial')) require_once( $appointments->plugin_dir . '/includes/external/pointer-tutorials.php' );

		//create our tutorial, with default redirect prefs
		$tutorial = new Pointer_Tutorial('app_tutorial2', true, false);

		//add our textdomain that matches the current plugin
		$tutorial->set_textdomain = 'appointments';

		//add the capability a user must have to view the tutorial
		$tutorial->set_capability = App_Roles::get_capability('manage_options', App_Roles::CTX_TUTORIAL);

		$tutorial->add_icon( $appointments->plugin_url . '/images/large-greyscale.png' );
		$appointments_page = admin_url('admin.php?page=appointments');

		$tutorial->add_step($appointments_page, 'toplevel_page_appointments', '.info-button', __('Appointment List', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('Appointment records are grouped by their statuses. You can see these groupings by clicking the Info icon.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'right', 'align' => 'center' ),
		));

		$tutorial->add_step($appointments_page, 'toplevel_page_appointments', '.add-new-h2', __('Entering a Manual Appointment', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('When you received appointments from your clients, they will be added to this page automatically. But you can always add a new appointment manually. Please click ADD NEW link and then click Next.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'top' ),
		));

		$tutorial->add_step($appointments_page, 'toplevel_page_appointments', 'select[name="status"]', __('Entering Data for the New Appointment', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('As you can see, you can enter all parameters here. Enter some random values and select status as PENDING, for this example. Then click Next', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'right', 'align' => 'center' ),
		));

		$tutorial->add_step($appointments_page, 'toplevel_page_appointments', 'input[name="resend"]', __('Sending Confirmation emails Manually', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('If you require payment, confirmation email is automatically sent after a Paypal payment. However if you are confirming appointments manually, you should check this checkbox for a confirmation email to be sent. You can also use this option for resending the confirmation email, e.g. after rescheduling an appointment.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'right', 'align' => 'center' ),
		));

		$tutorial->add_step($appointments_page, 'toplevel_page_appointments', '.save', __('Entering Data for the New Appointment', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('Save and then click Next.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'right', 'align' => 'center' ),
		));

		$tutorial->add_step($appointments_page, 'toplevel_page_appointments', '.error', __('Entering Data for the New Appointment', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('The result is shown here. Normally you should get a success message. Otherwise it means that you have a javascript problem on admin side.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($appointments_page, 'toplevel_page_appointments', '.info-button', __('Save New Appointment', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('As we added this appointment as "Pending" we will see it under Pending appointments. Click Pending appointments and then click Next.', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($appointments_page . '&type=pending', 'toplevel_page_appointments', '.info-button', __('Editing an Appointment', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('You can edit any appointment record. Just hover on the record and then click See Details and Edit', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		$tutorial->add_step($appointments_page . '&type=pending', 'toplevel_page_appointments', '.cancel', _x('Cancel', 'Drop current action', 'appointments'), array(
		    'content'  => '<p>' . esc_js(__('It is always possible to Cancel. Please note that these records are NOT saved until you click the Save button. Thanks for using Appointments+', 'appointments' )).'</p>',
		    'position' => array( 'edge' => 'left', 'align' => 'center' ),
		));

		if ( isset( $_GET["tutorial"] ) && 'restart2' == $_GET["tutorial"] )
			$tutorial->restart();

		//start the tutorial
		$tutorial->initialize();

		return $tutorial;
	}
}
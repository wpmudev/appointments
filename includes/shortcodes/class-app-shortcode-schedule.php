<?php
/**
 * Weekly schedule calendar shortcode.
 */
class App_Shortcode_WeeklySchedule extends App_Shortcode {
	public function __construct () {
		$this->name = __( 'Weekly Schedule', 'appointments' );
	}

	public function get_defaults() {
		$_workers = appointments_get_workers();
		$workers = array(
			array( 'text' => __( 'Any provider', 'appointments' ), 'value' => 0 )
		);
		foreach ( $_workers as $worker ) {
			/** @var Appointments_Worker $worker */
			$workers[] = array( 'text' => $worker->get_name(), 'value' => $worker->ID );
		}

		$_services = appointments_get_services();
		$services = array(
			array( 'text' => __( 'Any service', 'appointments' ), 'value' => 0 )
		);
		foreach ( $_services as $service ) {
			/** @var Appointments_Service $service */
			$services[] = array( 'text' => $service->name, 'value' => $service->ID );
		}

		return array(
			'title' => array(
				'type' => 'text',
				'name' => __( 'Title', 'appointments' ),
				'value' => __('<h3>Our schedule from START to END</h3>', 'appointments'),
				'help' => __('Text that will be displayed as the schedule title. Placeholders START and END will be automatically replaced by their real values.', 'appointments'),
			),
			'logged' => array(
				'type' => 'text',
				'name' => __( 'Logged in text', 'appointments' ),
				'value' => __('Click on a free time slot to apply for an appointment.', 'appointments'),
				'help' => __('Text that will be displayed after the title only to the clients who are logged in or you don\'t require a login.', 'appointments'),
				'example' => __('Click on a free time slot to apply for an appointment.', 'appointments'),
			),
			'notlogged' => array(
				'type' => 'text',
				'name' => __( 'Logged out text', 'appointments' ),
				'value' => __('You need to login to make an appointment. Please click here to register/login: LOGIN_PAGE', 'appointments'),
				'help' => __('Text that will be displayed after the title only to the clients who are not logged in and you require a login. <code>LOGIN_PAGE</code> will be replaced with your website\'s login page, while <code>REGISTRATION_PAGE</code> will be replaced with your website\'s registration page.', 'appointments'),
				'example' => __('You need to login to make an appointment. Please click here to register/login: LOGIN_PAGE', 'appointments'),
			),
			'service' => array(
				'type' => 'select',
				'name' => __( 'Service', 'appointments' ),
				'options' => $services,
				'value' => 0,
				'help' => __('Select service only if you want to force the table display the service with entered ID. Default: Service is selected by dropdown.', 'appointments'),
			),
			'worker' => array(
				'type' => 'select',
				'name' => __( 'Provider', 'appointments' ),
				'options' => $workers,
				'value' => 0,
				'help' => __('Select service provider only if you want to force the table display the service provider with entered ID. Default: Service provider is selected by dropdown.', 'appointments'),
			),
			'long' => array(
				'type' => 'checkbox',
				'name' => __( 'Long week days', 'appointments' ),
				'value' => 0,
				'help' => __('If checked, long week days are displayed on the schedule table row, e.g. "Saturday" instead of "Sa".', 'appointments'),
			),
			'class' => array(
				'type' => 'text',
				'name' => __( 'CSS Class', 'appointments' ),
				'value' => '',
				'help' => __('A css class name for the schedule table. Default is empty.', 'appointments'),
			),
			'add' => array(
				'type' => 'text',
				'name' => __( 'Number of months added', 'appointments' ),
				'value' => 0,
				'help' => __('Number of months to add to the schedule to use for preceding months\' schedules. Enter 1 for next month, 2 for the other month, so on. Default: "0" (Current month)', 'appointments'),
			),
			'_noscript' => array(
				'value' => false,
			),
			'date' => array(
				'type' => 'text',
				'name' => __( 'Date', 'appointments' ),
				'value' => '',
				'help' => __('Normally calendar starts from the current month. If you want to force it to start from a certain date, enter that date here. Most date formats are supported, but YYYY-MM-DD is recommended. Notes: 1) This value will also affect other subsequent calendars on the same page. 2) It is sufficient to enter a date inside the month. Default: "0" (Current month)', 'appointments'),
			),
			'require_provider' => array(
				'type' => 'checkbox',
				'name' => __( 'Require Provider', 'appointments' ),
				'value' => 0,
				'help' => __('Checking this argument means a timetable will not be rendered unless a service provider has been previously selected.', 'appointments'),
			),
			'required_message' => array(
				'type' => 'text',
				'name' => __( 'Required Message', 'appointments' ),
				'value' => __('Please, select a service provider.', 'appointments'),
				'help' => __('The message that will be shown if service providers are required.', 'appointments'),
			),
			'require_service' => array(
				'type' => 'checkbox',
				'name' => __( 'Require Service', 'appointments' ),
				'value' => 0,
				'help' => __('Checking this argument means a timetable will not be rendered unless a service has been previously selected.', 'appointments'),
			),
			'required_service_message' => array(
				'type' => 'text',
				'name' => __( 'Required Service Message', 'appointments' ),
				'value' => __('Please, select a service.', 'appointments'),
				'help' => __('The message that will be shown if services are required.', 'appointments')
			),
		);
	}

	public function process_shortcode( $args = array(), $content = '' ) {
		$appointments = appointments();
		$options = appointments_get_options();

		$args = wp_parse_args($args, $this->_defaults_to_args());
		extract( $args );


		// Force service
		$service_id = null;
		$service_id = isset( $_REQUEST["app_service_id"] ) ? absint( $_REQUEST["app_service_id"] ) : 0;
		if ( $args['service'] ) {
			// Check if such a service exists
			if ( ! appointments_get_service( $args['service'] ) ) {
				return '';
			}
			$service_id = $args['service'];
			$_REQUEST["app_service_id"] = $args['service'];
		}

		/*if ( appointments_get_services_min_id() ) {
		$service_id = appointments_get_services_min_id();
		}*/

		$location_id = isset( $_REQUEST["app_location_id"] ) ? absint( $_REQUEST["app_location_id"] ) : 0;

		if( ! $service_id && empty( $require_service ) ){
			if ( appointments_get_services_min_id() ) {
				$service_id = appointments_get_services_min_id();
			}
		}
		else{
			$service_id = 0;
		}
		$service_id = appointments_get_services_min_id();
		$appointments->get_lsw(); // This should come after Force service


		$workers_by_service = appointments_get_workers_by_service( $service_id );
		$single_worker = false;
		if ( 1 === count( $workers_by_service ) ) {
			$single_worker = $workers_by_service[0]->ID;
		}

		// Force worker or pick up the single worker
		$worker_id = 0;
		if ( $args['worker'] ) {
			// Check if such a worker exists
			if ( ! appointments_is_worker( $args['worker'] ) ) {
				return '';
			}
			$_REQUEST["app_provider_id"] = $args['worker'];
			$worker_id = $args['worker'];
		}
		else if ( $single_worker && ! $require_provider ) {

				$_REQUEST["app_provider_id"] = $single_worker;
				$args['worker'] = $single_worker;
				$worker_id = $single_worker;
		}
		elseif ( $require_provider && $appointments->worker ) {
			$worker_id = $appointments->worker;
		}

		// Force a date
		if ( isset( $args['date'] ) && ! empty( $args['date'] ) && !isset( $_GET["wcalendar"] ) ) {
			$time = $appointments->first_of_month( strtotime( $args['date'], $appointments->local_time ), $args['add'] );
			$_GET["wcalendar"] = $time;
		}
		else {
			if (!empty($_GET['wcalendar_human'])) $_GET['wcalendar'] = strtotime($_GET['wcalendar_human']);
			if ( isset( $_GET["wcalendar"] ) && (int)$_GET['wcalendar'] )
				$time = $appointments->first_of_month( (int)$_GET["wcalendar"], $args['add']  );
			else{
				//$time = $appointments->first_of_month( $appointments->local_time, $args['add']  );
				$time = current_time( 'timestamp' );
			}
		}

		$slots = appointments_get_weekly_schedule_slots( $time );

		$year = date("Y", $time);
		$month = date("m",  $time);

		if (!empty($title)) {
			$replacements = array(
				date_i18n("F Y",  strtotime("{$year}-{$month}-01")), // START
				appointments_get_worker_name( $service_id ),
				$appointments->get_service_name( $service_id ),
			);
			$title = str_replace(
				array("START", "WORKER", "SERVICE"),
				$replacements,
				$title);
		} else {
			$title = '';
		}

		$has_worker = ! empty( $worker_id );
		$has_service = ! empty( $require_service ) ? $service_id : false;


		$c  = '';
		$c .= '<div class="appointments-wrapper">';

		if (!$has_worker && !empty($require_provider)) {
			$c .= !empty($required_message)
				? $required_message
				: __('Please, select a service provider.', 'appointments')
			;
		} elseif (!$has_service && !empty($require_service)) {
			$c .= !empty($required_service_message)
				? $required_service_message
				: __('Please, select a service.', 'appointments')
			;
		} else {
			$c .= apply_filters('app-shortcodes-monthly_schedule-title', $title, $args);

			if ( is_user_logged_in() || 'yes' != $options["login_required"] ) {
				$c .= $args['logged'] ? "<div class='appointments-instructions'>{$args['logged']}</div>" : '';
			} else {
				$codec = new App_Macro_GeneralCodec;
				if ( ! $options["accept_api_logins"] ) {
					//$c .= str_replace( 'LOGIN_PAGE', '<a class="appointments-login_show_login" href="'.site_url( 'wp-login.php').'">'. __('Login','appointments'). '</a>', $args['notlogged'] );
					$c .= $codec->expand($args['notlogged'], App_Macro_GeneralCodec::FILTER_BODY);
				} else {
					$c .= '<div class="appointments-login">';
					//$c .= str_replace( 'LOGIN_PAGE', '<a class="appointments-login_show_login" href="javascript:void(0)">'. __('Login','appointments'). '</a>', $args['notlogged'] );
					$c .= $codec->expand($args['notlogged'], App_Macro_GeneralCodec::FILTER_BODY);
					$c .= '<div class="appointments-login_inner">';
					$c .= '</div>';
					$c .= '</div>';
				}
			}

			$c .= '<div class="appointments-list">';

			
			$cal_args = array(
				'worker_id' => $worker_id,
				'service_id' => $service_id,
				'location_id' => $location_id,
				'long' => $args['long'],
				'class' => $args['class'],
				'echo' => false
			);

			$c .= appointments_weekly_calendar( $time, $cal_args );
			$c .= '</div>';

		}
		$c .= '</div>'; // .appointments-wrapper
		$script = '';

		$appointments->add2footer( $script );

		return $c;
	}

	public function get_usage_info () {
		return __('Creates a weekly table whose cells are clickable to apply for an appointment.', 'appointments');
	}
}


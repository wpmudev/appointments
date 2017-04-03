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

		$current_time = current_time( 'timestamp' );
		$options = appointments_get_options();
		$args = wp_parse_args( $args, $this->_defaults_to_args() );

		$service_id = isset( $_REQUEST["app_service_id"] ) ? absint( $_REQUEST["app_service_id"] ) : 0;
		if ( appointments_get_services_min_id() ) {
			$service_id = appointments_get_services_min_id();
		}

		$worker_id = isset( $_REQUEST["app_provider_id"] ) ? absint( $_REQUEST["app_provider_id"] ) : 0;
		if ( ! $worker_id ) {
			$worker_id = isset( $_REQUEST["app_worker_id"] ) ? absint( $_REQUEST["app_worker_id"] ) : 0;
		}
		$location_id = isset( $_REQUEST["app_location_id"] ) ? absint( $_REQUEST["app_location_id"] ) : 0;

		// Force service
		if ( $args['service'] ) {
			// Check if such a service exists
			if ( ! appointments_get_service( $args['service'] ) ) {
				return '';
			}
			$service_id = absint( $args['service'] );
			$_REQUEST["app_service_id"] = $args['service'];
		}


		$workers_by_service = appointments_get_workers_by_service( $service_id );
		$single_worker = false;
		if ( 1 === count( $workers_by_service ) ) {
			$single_worker = $workers_by_service[0]->ID;
		}

		if ( $args['worker'] ) {
			// Check if such a worker exists
			if ( ! appointments_is_worker( $args['worker'] ) ) {
				return '';
			}
			$worker_id = absint( $args['worker'] );
			$_REQUEST["app_provider_id"] = $args['worker'];
		} else if ( $single_worker ) {
			// Select the only provider if that is the case
			$_REQUEST["app_provider_id"] = $single_worker;
			$args['worker']              = $single_worker;
			$worker_id = absint( $args['worker'] );
		}

		// Force a date
		if ( $args['date'] && ! isset( $_GET["wcalendar"] ) ) {
			$time              = strtotime( $args['date'], $current_time ) + ( $args['add'] * 7 * 86400 );
			$_GET["wcalendar"] = $time;
		} else {
			if ( isset( $_GET["wcalendar"] ) && (int) $_GET['wcalendar'] ) {
				$time = (int) $_GET["wcalendar"] + ( $args['add'] * 7 * 86400 );
			} else {
				$time = $current_time + ( $args['add'] * 7 * 86400 );
			}
		}

		$slots = appointments_get_weekly_schedule_slots( $time );

		if ( '' != $args['title'] ) {
			$start_day = current( $slots['the_week'] );
			end( $slots['the_week'] );
			$end_day = current( $slots['the_week'] );
			reset( $slots['the_week'] );
			$args['title'] = str_replace(
				array( "START", "END" ),
				array(
					date_i18n( appointments_get_date_format( 'date' ), strtotime( $start_day ) ),
					date_i18n( appointments_get_date_format( 'date' ), strtotime( $end_day ) )
				),
				$args['title']
			);
		}
		else {
			$args['title'] = '';
		}

		$has_worker = ! empty( $worker_id );
		$has_service = ! empty( $args['require_service'] ) ? $service_id : false;

		$c = '';
		$c .= '<div class="appointments-wrapper">';

		if ( ! $has_worker && ! empty( $require_provider ) ) {
			$c .= ! empty( $required_message )
				? $required_message
				: __( 'Please, select a service provider.', 'appointments' );
		} elseif ( ! $has_service && ! empty( $require_service ) ) {
			$c .= ! empty( $required_service_message )
				? $required_service_message
				: __( 'Please, select a service.', 'appointments' );
		} else {
			$c .= $args['title'];

			if ( is_user_logged_in() || 'yes' != $options["login_required"] ) {
				$c .= $args['logged'] ? "<div class='appointments-instructions'>{$args['logged']}</div>" : '';
			} else {
				$codec = new App_Macro_GeneralCodec;
				if ( ! $options["accept_api_logins"] ) {
					//$c .= str_replace( 'LOGIN_PAGE', '<a class="appointments-login_show_login" href="'.site_url( 'wp-login.php').'">'. __('Login','appointments'). '</a>', $notlogged );
					$c .= $codec->expand( $args['notlogged'], App_Macro_GeneralCodec::FILTER_BODY );
				} else {
					$c .= '<div class="appointments-login">';
					//$c .= str_replace( 'LOGIN_PAGE', '<a class="appointments-login_show_login" href="javascript:void(0)">'. __('Login','appointments'). '</a>', $args['notlogged ']);
					$c .= $codec->expand( $args['notlogged'], App_Macro_GeneralCodec::FILTER_BODY );
					$c .= '<div class="appointments-login_inner">';
					$c .= '</div>';
					$c .= '</div>';
				}
			}

			$c .= '<div class="appointments-list">';

			$current_time = current_time( 'timestamp' );
			$date = $time ? $time : $current_time;
			$c .= appointments_weekly_calendar( $date, array(
				'worker_id' => $worker_id,
				'service_id' => $service_id,
				'location_id' => $location_id,
				'long' => $args['long'],
				'class' => $args['class'],
				'echo' => false
			));

			$c .= '</div>';
		}
		$c .= '</div>'; // .appointments-wrapper

		$script = '';

		if ( ! $args['_noscript'] ) {
			$appointments->add2footer( $script );
		}

		return $c;
	}

	public function get_usage_info () {
		return __('Creates a weekly table whose cells are clickable to apply for an appointment.', 'appointments');
	}
}

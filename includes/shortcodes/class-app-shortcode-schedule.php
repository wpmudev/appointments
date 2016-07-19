<?php
/**
 * Weekly schedule calendar shortcode.
 */
class App_Shortcode_WeeklySchedule extends App_Shortcode {
	public function __construct () {
		$this->_defaults = array(
			'title' => array(
				'value' => __('<h3>Our schedule from START to END</h3>', 'appointments'),
				'help' => __('Text that will be displayed as the schedule title. Placeholders START and END will be automatically replaced by their real values.', 'appointments'),
				'example' => __('Our schedule from START to END', 'appointments'),
			),
			'logged' => array(
				'value' => __('Click on a free time slot to apply for an appointment.', 'appointments'),
				'help' => __('Text that will be displayed after the title only to the clients who are logged in or you don\'t require a login.', 'appointments'),
				'example' => __('Click on a free time slot to apply for an appointment.', 'appointments'),
			),
			'notlogged' => array(
				'value' => __('You need to login to make an appointment. Please click here to register/login: LOGIN_PAGE', 'appointments'),
				'help' => __('Text that will be displayed after the title only to the clients who are not logged in and you require a login. <code>LOGIN_PAGE</code> will be replaced with your website\'s login page, while <code>REGISTRATION_PAGE</code> will be replaced with your website\'s registration page.', 'appointments'),
				'example' => __('You need to login to make an appointment. Please click here to register/login: LOGIN_PAGE', 'appointments'),
			),
			'service' => array(
				'value' => 0,
				'help' => __('Enter service ID only if you want to force the table display the service with entered ID. Default: "0" (Service is selected by dropdown). Note: Multiple selections are not allowed.', 'appointments'),
				'example' => 12,
			),
			'worker' => array(
				'value' => 0,
				'help' => __('Enter service provider ID only if you want to force the table display the service provider with entered ID. Default: "0" (Service provider is selected by dropdown). Note: Multiple selections are not allowed.', 'appointments'),
				'example' => 15,
			),
			'long' => array(
				'value' => 0,
				'help' => __('If entered 1, long week days are displayed on the schedule table row, e.g. "Saturday" instead of "Sa".', 'appointments'),
				'example' => 1,
			),
			'class' => array(
				'value' => '',
				'help' => __('A css class name for the schedule table. Default is empty.', 'appointments'),
				'example' => 'my-class',
			),
			'add' => array(
				'value' => 0,
				'help' => __('Number of weeks to add to the schedule to use for preceding weeks\' schedules. Enter 1 for next week, 2 for the other week, so on. Default: "0" (Current week)', 'appointments'),
				'example' => 1,
			),
			'_noscript' => array(
				'value' => false,
			),
			'date' => array(
				'value' => false,
				'help' => __('Normally calendar starts from the current week. If you want to force it to start from a certain date, enter that date here. Most date formats are supported, but YYYY-MM-DD is recommended. Notes: 1) This value will also affect other subsequent calendars on the same page. 2) Date value will not change starting day of week. It is sufficient to enter a date inside the week. Default: "0" (Current week)', 'appointments'),
				'example' => '2014-02-01',
			),
			'require_provider' => array(
				'value' => 0,
				'help' => __('Setting this argument to "1" means a timetable will not be rendered unless a service provider has been previously selected.', 'appointments'),
				'example' => 1,
			),
			'required_message' => array(
				'value' => __('Please, select a service provider.', 'appointments'),
				'help' => __('The message that will be shown if service providers are required.', 'appointments'),
				'example' => __('Please, select a service.', 'appointments'),
			),
			'require_service' => array(
				'value' => 0,
				'help' => __('Setting this argument to "1" means a timetable will not be rendered unless a service has been previously selected.', 'appointments'),
				'example' => 1,
			),
			'required_service_message' => array(
				'value' => __('Please, select a service.', 'appointments'),
				'help' => __('The message that will be shown if services are required.', 'appointments'),
				'example' => __('Please, select a service.', 'appointments'),
			),

		);
	}

	public function process_shortcode ($args=array(), $content='') {
		$appointments = appointments();

		$args = wp_parse_args( $args, $this->_defaults_to_args() );

		// Force service
		if ( $args['service'] ) {
			// Check if such a service exists
			if ( ! appointments_get_service( $args['service'] ) ) {
				return '';
			}
			$_REQUEST["app_service_id"] = $args['service'];
		}

		$appointments->get_lsw(); // This should come after Force service
		$workers_by_service = appointments_get_workers_by_service( $appointments->service );
		$single_worker = false;
		if ( 1 === count( $workers_by_service ) ) {
			$single_worker = $workers_by_service[0]->ID;
		}

		if ( $args['worker'] ) {
			// Check if such a worker exists
			if ( ! appointments_is_worker( $args['worker'] ) ) {
				return '';
			}
			$_REQUEST["app_provider_id"] = $args['worker'];
		} else if ( $single_worker ) {
			// Select the only provider if that is the case
			$_REQUEST["app_provider_id"] = $single_worker;
			$args['worker']              = $single_worker;
		}

		// Force a date
		if ( $args['date'] && ! isset( $_GET["wcalendar"] ) ) {
			$time              = strtotime( $args['date'], $appointments->local_time ) + ( $args['add'] * 7 * 86400 );
			$_GET["wcalendar"] = $time;
		} else {
			if ( isset( $_GET["wcalendar"] ) && (int) $_GET['wcalendar'] ) {
				$time = (int) $_GET["wcalendar"] + ( $args['add'] * 7 * 86400 );
			} else {
				$time = $appointments->local_time + ( $args['add'] * 7 * 86400 );
			}
		}

		$start_of_calendar = $appointments->sunday( $time ) + $appointments->start_of_week * 86400;

		if ( '' != $args['title'] )
			$args['title'] = str_replace(
				array( "START", "END" ),
				array(
					date_i18n($appointments->date_format, $start_of_calendar ),
					date_i18n($appointments->date_format, $start_of_calendar + 6*86400 )
				),
				$args['title']
			);
		else {
			$args['title'] = '';
		}

		$has_worker = ! empty( $appointments->worker ) || ! empty( $args['worker'] );

		$has_service = ! empty( $require_service ) ? $_REQUEST["app_service_id"] : false;

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

			if ( is_user_logged_in() || 'yes' != $appointments->options["login_required"] ) {
				$c .= $args['logged'] ? "<div class='appointments-instructions'>{$args['logged']}</div>" : '';
			} else {
				$codec = new App_Macro_GeneralCodec;
				if ( ! @$appointments->options["accept_api_logins"] ) {
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
			$c .= $appointments->get_weekly_calendar( $time, $args['class'], $args['long'] );

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
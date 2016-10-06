<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Monthly schedule calendar.
 */
if ( ! class_exists( 'App_Shortcode_Monthly_Schedule' ) ) {
	class App_Shortcode_Monthly_Schedule extends App_Shortcode {

		public function __construct () {
			$this->name = __( 'Monthly Schedule', 'appointments' );

			$_workers = appointments_get_workers();
			$workers = array(
				array( 'text' => __( 'Any provider', 'appointments' ), 'value' => '' )
			);
			foreach ( $_workers as $worker ) {
				/** @var Appointments_Worker $worker */
				$workers[] = array( 'text' => $worker->get_name(), 'value' => $worker->ID );
			}

			$_services = appointments_get_services();
			$services = array(
				array( 'text' => __( 'Any service', 'appointments' ), 'value' => '' )
			);
			foreach ( $_services as $service ) {
				/** @var Appointments_Service $service */
				$services[] = array( 'text' => $service->name, 'value' => $service->ID );
			}

			$this->_defaults = array(
				'title' => array(
					'type' => 'text',
					'name' => __( 'Title', 'appointments' ),
					'value' => __('<h3>Our schedule for START</h3>', 'appointments'),
					'help' => __('Text that will be displayed as the schedule title. Placeholders START, WORKER and SERVICE will be automatically replaced by their real values.', 'appointments'),
				),
				'logged' => array(
					'type' => 'text',
					'name' => __( 'After Title text (logged-out users)', 'appointments' ),
					'value' => __('Click a free day to apply for an appointment.', 'appointments'),
					'help' => __('Text that will be displayed after the title only to the clients who are logged in or you don\'t require a login.', 'appointments'),
				),
				'notlogged' => array(
					'type' => 'text',
					'name' => __( 'Not logged text', 'appointments' ),
					'value' => __('You need to login to make an appointment. Please click here to register/login: LOGIN_PAGE', 'appointments'),
					'help' => __('Text that will be displayed after the title only to the clients who are not logged in and you require a login. <code>LOGIN_PAGE</code> will be replaced with your website\'s login page, while <code>REGISTRATION_PAGE</code> will be replaced with your website\'s registration page.', 'appointments'),
				),
				'service' => array(
					'type' => 'select',
					'name' => __( 'Service', 'appointments' ),
					'value' => 0,
					'options' => $services,
					'help' => __('Select a service only if you want to force the table display the entered service. Default: Service is selected by dropdown.', 'appointments'),
				),
				'worker' => array(
					'type' => 'select',
					'name' => __( 'Provider', 'appointments' ),
					'value' => 0,
					'options' => $workers,
					'help' => __('Select a provider only if you want to force the table display the entered provider. Default: Provider is selected by dropdown.', 'appointments'),
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
				'widget' => array(
					'value' => 0,
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

		public function process_shortcode ($args=array(), $content='') {
			global $current_user, $appointments;

			extract(wp_parse_args($args, $this->_defaults_to_args()));


			// Force service
			if ( $service ) {
				// Check if such a service exists
				if ( !appointments_get_service( $service ) )
					return;
				$_REQUEST["app_service_id"] = $service;
			}

			$appointments->get_lsw(); // This should come after Force service


			$workers_by_service = appointments_get_workers_by_service( $appointments->service );
			$single_worker = false;
			if ( 1 === count( $workers_by_service ) ) {
				$single_worker = $workers_by_service[0]->ID;
			}

			// Force worker or pick up the single worker
			if ( $worker ) {
				// Check if such a worker exists
				if (! appointments_is_worker($worker)) return;
				$_REQUEST["app_provider_id"] = $worker;
			}
			else if ( $single_worker ) {
				// Select the only provider if that is the case
				$_REQUEST["app_provider_id"] = $single_worker;
				$worker = $single_worker;
			}

			// Force a date
			if ( $date && !isset( $_GET["wcalendar"] ) ) {
				$time = $appointments->first_of_month( strtotime( $date, $appointments->local_time ), $add );
				$_GET["wcalendar"] = $time;
			}
			else {
				if (!empty($_GET['wcalendar_human'])) $_GET['wcalendar'] = strtotime($_GET['wcalendar_human']);
				if ( isset( $_GET["wcalendar"] ) && (int)$_GET['wcalendar'] )
					$time = $appointments->first_of_month( (int)$_GET["wcalendar"], $add  );
				else
					$time = $appointments->first_of_month( $appointments->local_time, $add  );
			}

			$year = date("Y", $time);
			$month = date("m",  $time);

			if (!empty($title)) {
				$replacements = array(
					date_i18n("F Y",  strtotime("{$year}-{$month}-01")), // START
					appointments_get_worker_name(
						(!empty($_REQUEST['app_provider_id']) ? $_REQUEST['app_provider_id'] : null)
					),
					$appointments->get_service_name(
						(!empty($_REQUEST['app_service_id']) ? $_REQUEST['app_service_id'] : null)
					),
				);
				$title = str_replace(
					array("START", "WORKER", "SERVICE"),
					$replacements,
					$title);
			} else {
				$title = '';
			}

			$has_worker = !empty($appointments->worker) || !empty($worker);

			$has_service = !empty($require_service) ? $_REQUEST["app_service_id"] : false;



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

				if ( is_user_logged_in() || 'yes' != $appointments->options["login_required"] ) {
					$c .= $logged ? "<div class='appointments-instructions'>{$logged}</div>" : '';
				} else {
					$codec = new App_Macro_GeneralCodec;
					if ( !@$appointments->options["accept_api_logins"] ) {
						//$c .= str_replace( 'LOGIN_PAGE', '<a class="appointments-login_show_login" href="'.site_url( 'wp-login.php').'">'. __('Login','appointments'). '</a>', $notlogged );
						$c .= $codec->expand($notlogged, App_Macro_GeneralCodec::FILTER_BODY);
					} else {
						$c .= '<div class="appointments-login">';
						//$c .= str_replace( 'LOGIN_PAGE', '<a class="appointments-login_show_login" href="javascript:void(0)">'. __('Login','appointments'). '</a>', $notlogged );
						$c .= $codec->expand($notlogged, App_Macro_GeneralCodec::FILTER_BODY);
						$c .= '<div class="appointments-login_inner">';
						$c .= '</div>';
						$c .= '</div>';
					}
				}

				$c .= '<div class="appointments-list">';
				$c .= $appointments->get_monthly_calendar($time, $class, $long, $widget);
				$c .= '</div>';

			}
			$c .= '</div>'; // .appointments-wrapper
			$script = '';

			$appointments->add2footer( $script );

			return $c;
		}

		public function get_usage_info () {
			return __('Creates a monthly calendar plus time tables whose free time slots are clickable to apply for an appointment.', 'appointments');
		}
	}

}
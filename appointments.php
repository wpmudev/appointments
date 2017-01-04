<?php
/*
Plugin Name: Appointments+
Description: Lets you accept appointments from front end and manage or create them from admin side
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 2.0.0
Author: WPMU DEV
Author URI: http://premium.wpmudev.org/
Textdomain: appointments
WDP ID: 679841
*/

/*
Copyright 2007-2013 Incsub (http://incsub.com)
Author - Hakan Evin <hakan@incsub.com>
Contributor - Ve Bailovity (Incsub)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( !class_exists( 'Appointments' ) ) {

class Appointments {

	public $version = "2.0";
	public $db_version;

	public $timetables = array();

	public $local_time;
	/** @var bool|Appointments_Google_Calendar  */
	public $gcal_api = false;
	public $locale_error;
	public $time_format;
	public $datetime_format;
	public $log_file;
	public $salt;
	public $worker;
	public $location;
	public $service;
	public $openid;
	public $plugin_url;
	/** @var Appointments_Admin  */
	public $admin;

	/** @var  Appointments_Addons_Loader */
	public $addons_loader;

	/** @var Appointments_Notifications_Manager */
	public $notifications;

	public $pro = false;

	public $shortcodes = array();


	function __construct() {

		include_once( 'includes/helpers.php' );
		include_once( 'includes/helpers-settings.php' );
		include_once( 'includes/deprecated-hooks.php' );
		include_once( 'includes/class-app-notifications-manager.php' );
		include_once( 'includes/class-app-api-logins.php' );

		// Load premium features
		if ( is_readable( appointments_plugin_dir() . 'includes/pro/class-app-pro.php' ) ) {
			include_once( appointments_plugin_dir() . 'includes/pro/class-app-pro.php' );
			$this->pro = new Appointments_Pro();
		}

		$this->timetables = get_transient( 'app_timetables' );
		if ( ! $this->timetables || ! is_array( $this->timetables ) ) {
			$this->timetables = array();
		}

		$this->plugin_url = plugins_url(basename(dirname(__FILE__)));

		// Read all options at once
		$this->options = get_option( 'appointments_options' );

		// To follow WP Start of week, time, date settings
		$this->local_time = current_time('timestamp');
		$this->start_of_week = appointments_week_start() - 1;

		$this->time_format = appointments_get_date_format( 'time' );
		$this->date_format = appointments_get_date_format( 'date' );
		$this->datetime_format = appointments_get_date_format( 'full' );

		add_action( 'delete_user', 'appointments_delete_worker' );		// Modify database in case a user is deleted
		add_action( 'wpmu_delete_user', 'appointments_delete_worker' );	// Same as above
		add_action( 'remove_user_from_blog', array( &$this, 'remove_user_from_blog' ), 10, 2 );	// Remove his records only for that blog

		add_action( 'plugins_loaded', array(&$this, 'localization') );		// Localize the plugin
		add_action( 'init', array( &$this, 'init' ), 20 ); 						// Initial stuff
		add_filter( 'the_posts', array(&$this, 'load_styles') );			// Determine if we use shortcodes on the page

		add_action( 'admin_init', array( $this, 'maybe_upgrade' ) );

		include_once( 'includes/class-app-service.php' );
		include_once( 'includes/class-app-worker.php' );
		include_once( 'includes/class-app-appointment.php' );
		include_once( 'includes/class-app-transaction.php' );

		if ( is_admin() ) {
			$this->load_admin();
		}


		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			include_once( 'includes/class-app-ajax.php' );
			new Appointments_AJAX();
		}

		// Check for cookies
		if (!empty($this->options['login_required']) && 'yes' === $this->options['login_required']) {
			// If we require a login and we had an user logged in, 
			// we don't need cookies after they log out
			add_action('wp_logout', array($this, 'drop_cookies_on_logout'));
		}

		// Widgets
		require_once( appointments_plugin_dir() . 'includes/widgets.php' );
		add_action( 'widgets_init', array( &$this, 'widgets_init' ) );

		// Integration with other plugins/Themes
		include_once( appointments_plugin_dir() . 'includes/integration/integration.php' );

		$this->pages_to_be_cached = array();
		$this->had_filter = false; // There can be a wpautop filter. We will check this later on.

		add_action('init', array($this, 'get_gcal_api'), 10);

		// Database variables
		global $wpdb;
		$this->db 					= &$wpdb;
		$this->services_table 		= $wpdb->prefix . "app_services";
		$this->transaction_table 	= $wpdb->prefix . "app_transactions";
		$this->cache_table 			= $wpdb->prefix . "app_cache";
		// DB version
		$this->db_version 			= get_option( 'app_db_version' );

		// Set meta tables
		$wpdb->app_appointmentmeta = appointments_get_table( 'appmeta' );

		// Set log file location
		$uploads = wp_upload_dir();
		if ( isset( $uploads["basedir"] ) )
			$this->uploads_dir 	= $uploads["basedir"] . "/";
		else
			$this->uploads_dir 	= WP_CONTENT_DIR . "/uploads/";
		$this->log_file 		= $this->uploads_dir . "appointments-log.txt";

		// Other default settings
		$this->script = $this->uri = $this->error_url = '';
		$this->location = $this->service = $this->worker = 0;
		$this->gcal_image = '<img src="' . $this->plugin_url . '/images/gc_button1.gif" />';
		$this->locale_errlocale_error = false;

		// Create a salt, if it doesn't exist from the previous installation
		if ( !$salt = get_option( "appointments_salt" ) ) {
			$salt = mt_rand();
			add_option( "appointments_salt", $salt ); // Save it to be used until it is cleared manually
		}
		$this->salt = $salt;

		// Deal with zero-priced appointments auto-confirm
		if ( isset( $this->options['payment_required'] ) && 'yes' == $this->options['payment_required'] && !empty($this->options['allow_free_autoconfirm'])) {
			if (!defined('APP_CONFIRMATION_ALLOW_FREE_AUTOCONFIRM')) define('APP_CONFIRMATION_ALLOW_FREE_AUTOCONFIRM', true);
		}

		$this->notifications = new Appointments_Notifications_Manager();
	}


	public function load_admin() {
		include_once( 'admin/class-app-admin.php' );
		$this->admin = new Appointments_Admin();
	}

	function maybe_upgrade() {
		if ( isset( $_GET['app-clear'] ) && current_user_can( 'manage_options' ) ) {
			$this->flush_cache();
		}

		$db_version = get_option( 'app_db_version' );

		if ( $db_version == $this->version ) {
			return;
		}

		if ( false === $db_version ) {
		    appointments_activate();
        }

		appointments_clear_cache();

		include_once( 'includes/class-app-upgrader.php' );

		$upgrader = new Appointments_Upgrader( $this->version );
		$upgrader->upgrade( $db_version, $this->version );
	}


	function get_gcal_api() {
		if ( false === $this->gcal_api && ! defined( 'APP_GCAL_DISABLE' ) ) {
			require_once appointments_plugin_dir() . 'includes/class-app-gcal.php';
			$this->gcal_api = new Appointments_Google_Calendar();
		}
		return $this->gcal_api;
	}



/**
***************************************************************************************************************
* Methods for optimization
*
* $l: location ID - For future use
* $s: service ID
* $w: worker ID
* $stat: Status (open: working or closed: not working)
* IMPORTANT: This plugin is NOT intended for hundreds of services or service providers,
*  but it is intended to make database queries as cheap as possible with smaller number of services/providers.
*  If you have lots of services and/or providers, codes will not scale and appointments pages will be VERY slow.
*  If you need such an application, override some of the methods below with a child class.
***************************************************************************************************************
*/

	/**
	 * Get location, service, worker
	 */
	function get_lsw() {
		$this->location = $this->get_location_id();
		$this->service = $this->get_service_id();
		$this->worker = $this->get_worker_id();
	}

	/**
	 * Get location ID for future use
	 */
	function get_location_id() {
		if ( isset( $_REQUEST["app_location_id"] ) )
			return (int)$_REQUEST["app_location_id"];

		return 0;
	}

	/**
	 * Get smallest service ID
	 * We assume total number of services is not too high, which is the practical case.
	 * Otherwise this method might be expensive
	 *
	 * @deprecated since 1.6
	 *
	 * @return integer
	 */
	function get_first_service_id() {
		_deprecated_function( __FUNCTION__, '1.6', 'appointments_get_services_min_id()' );
		return appointments_get_services_min_id();
	}

	/**
	 * Get service ID from front end
	 * @return integer
	 */
	function get_service_id() {
		if ( isset( $_REQUEST["app_service_id"] ) )
			return (int)$_REQUEST["app_service_id"];
		else if ( !$service_id = appointments_get_services_min_id() )
			$service_id = 0;

		return $service_id;
	}

	/**
	 * Get worker ID from front end
	 * worker = provider
	 * @return integer
	 */
	function get_worker_id() {
		if ( isset( $_REQUEST["app_provider_id"] ) )
			return (int)$_REQUEST["app_provider_id"];

		if ( isset( $_REQUEST["app_worker_id"] ) )
			return (int)$_REQUEST["app_worker_id"];

		return 0;
	}



	 /**
	 * Allow only certain order_by clauses
	 * @since 1.2.8
	 */
	function sanitize_order_by( $order_by="ID" ) {
		$whitelist = apply_filters( 'app_order_by_whitelist', array( 'ID', 'name', 'start', 'end', 'duration', 'price',
					'ID DESC', 'name DESC', 'start DESC', 'end DESC', 'duration DESC', 'price DESC', 'RAND()' ) );
		if ( in_array( $order_by, $whitelist ) )
			return $order_by;
		else
			return 'ID';
	}

	/**
	 * Get a single service with given ID
	 *
	 * @deprecated Deprecated since version 1.6
	 *
	 * @param ID: Id of the service to be retrieved
	 * @return object
	 */
	function get_service( $ID ) {
		_deprecated_function( __FUNCTION__, '1.6', 'appointments_get_service()' );
		return appointments_get_service( $ID );
	}


	/**
	 * Get all workers
	 *
	 * @deprecated Deprecated since version 1.6
	 *
	 * @param order_by: ORDER BY clause for mysql
	 * @return array of objects
	 */
	function get_workers( $order_by="ID" ) {
		_deprecated_function( __FUNCTION__, '1.6', 'appointments_get_workers()' );
		$args = array(
			'orderby' => $order_by
		);
		return appointments_get_workers( $args );
	}

	/**
	 * Get all services
	 * @param order_by: ORDER BY clause for mysql
	 * @deprecated Deprecated since version 1.6
	 * @return array of objects
	 */
	function get_services( $order_by="ID" ) {
		_deprecated_function( __FUNCTION__, '1.6', 'appointments_get_services()' );
		$args = array( 'orderby'=> $order_by );
		return appointments_get_services( $args );
	}


	/**
	 * Get workers giving a specific service (by its ID)
 	 * We assume total number of workers is not too high, which is the practical case.
	 * Otherwise this method would be expensive
	 *
	 * @deprecated Deprecated since version 1.6
	 *
	 * @param ID: Id of the service to be retrieved
	 * @param string $order_by ORDER BY clause for mysql
	 * @return array|false array of objects
	 */
	function get_workers_by_service( $ID, $order_by = "ID" ) {
		_deprecated_function( __FUNCTION__, '1.6', 'appointments_get_workers_by_service()' );
		$workers = appointments_get_workers_by_service( $ID, $order_by );

		if ( empty( $workers ) )
			return false;

		return $workers;
	}

	/**
	 * Check if there is only one worker giving the selected service
	 *
	 * @deprecated since 1.6
	 *
	 * @param service: Id of the service for which check will be done
 	 * @since 1.1.1
	 * @return int|boolean (worker ID if there is one, otherwise false)
	 */
	function is_single_worker( $service_id ) {
		_deprecated_function( __FUNCTION__, '1.6' );

		$workers = appointments_get_workers_by_service( $service_id );
		if ( 1 === count( $workers ) ) {
			return $workers[0]->ID;
		}

		return false;
	}

	/**
	 * Return a row from working hours table, i.e. days/hours we are working or we have break
	 * @param stat: open (works), or closed (breaks).
	 * @return object
	 */
	function get_work_break( $l, $w, $stat ) {
		_deprecated_function( __FUNCTION__, '2.0', 'appointments_get_worker_working_hours()' );

		$work_break = appointments_get_worker_working_hours( $stat, $w, $l );

		if ( false === $work_break ) {
			return null;
		}

		// Backward compatibility
		$work_break->hours = maybe_serialize( $work_break->hours );

		return $work_break;
	}

	/**
	 * Return a row from exceptions table, i.e. days we are working or having holiday
	 * @deprecated since 1.9.2
	 * @return object
	 */
	function get_exception( $l, $w, $stat ) {
		_deprecated_function( __FUNCTION__, '1.9.2', 'appointments_get_worker_exceptions()' );
		return appointments_get_worker_exceptions( $w, $stat, $l );
	}

	/**
	 * Return an appointment given its ID
	 *
	 * @deprecated since 1.6
	 *
	 * @param app_id: ID of the appointment to be retreived from database
	 * @since 1.1.8
	 * @return object
	 */
	function get_app( $app_id ) {
		_deprecated_function( __FUNCTION__, '1.6', 'appointments_get_appointment()' );
		return appointments_get_appointment( $app_id );
	}

	/**
	 * Return all reserve appointments (i.e. pending, paid, confirmed or reserved by GCal)
	 * @param week: Optionally appointments only in the number of week in ISO 8601 format (since 1.2.3).
	 * Weekly gives much better results in RAM usage compared to monthly, with a tolerable, slight increase in number of queries
	 * @return array of objects
	 *
	 * @deprecated since 1.6
	 */
	function get_reserve_apps( $l, $s, $w, $week=0 ) {
		_deprecated_function( __FUNCTION__, '1.6', 'appointments_get_appointments()' );
		$args = array(
			'location' => $l,
			'service' => $s,
			'week' => $week,
			'worker' => $w
		);
		return appointments_get_appointments_filtered_by_services( $args );
	}

	/**
	 * Return all reserve appointments by worker ID
	 * @param week: Optionally appointments only in the number of week in ISO 8601 format (since 1.2.3)
	 * @return array of objects
	 */
	function get_reserve_apps_by_worker( $l, $w, $week=0 ) {
		$apps = wp_cache_get( 'reserve_apps_by_worker_'. $l . '_' . $w . '_' . $week );
		if ( false === $apps ) {
			$services = appointments_get_services();
			if ( $services ) {
				$apps = array();
				foreach ( $services as $service ) {
					$args = array(
						'location' => $l,
						'service' => $service->ID,
						'worker' => $w,
						'week' => $week
					);
					$apps_worker = appointments_get_appointments_filtered_by_services( $args );
					if ( $apps_worker )
						$apps = array_merge( $apps, $apps_worker );
				}
			}
			wp_cache_set( 'reserve_apps_by_worker_'. $l . '_' . $w . '_' . $week, $apps );
		}
		return $apps;
	}

	/**
	 * Return reserve appointments by service ID
	 *
	 * @deprecated since 1.6
	 *
	 * @param week: Optionally appointments only in the number of week in ISO 8601 format (since 1.2.3)
	 * @since 1.1.3
	 * @return array of objects
	 */
	function get_reserve_apps_by_service( $l, $s, $week=0 ) {
		_deprecated_function( __FUNCTION__, '1.6', 'appointments_get_appointments_filtered_by_services()' );
		$args = array(
			'location' => $l,
			'service' => $s,
			'week' => $week
		);
		return appointments_get_appointments_filtered_by_services( $args );
	}


	/**
	 * Find if a user is dummy
	 * @param user_id: Id of the user who will be checked if he is dummy
	 * since 1.0.6
	 * @return bool
	 */
	function is_dummy( $user_id=0 ) {
		global $wpdb, $current_user;
		if ( !$user_id )
			$user_id = $current_user->ID;

		// A dummy should be a worker
		$result = appointments_get_worker( $user_id );
		if ( ! $result )
			return false;

		// This is only supported after V1.0.6 and if DB is altered
		if ( !$this->db_version )
			return false;

		if ( $result->dummy )
			return true;

		return false;
	}


	/**
	 *
	 * @deprecated since 1.6
	 *
	 * @param $worker_id
	 *
	 * @return bool
	 */
	public function is_worker( $worker_id ) {
		_deprecated_function( __FUNCTION__, '1.6', 'appointments_is_worker()' );
		return appointments_is_worker( $worker_id );
	}


	/**
	 * Find worker name given his ID
	 *
	 * @deprecated since 1.6
	 *
	 * @return string
	 */
	function get_worker_name( $worker=0, $field = true ) {
		_deprecated_function( __FUNCTION__, '1.6', 'appointments_get_worker_name()' );

		if ( $field ) {
			$field = 'default';
		}
		else {
			$field = 'user_login';
		}

		return appointments_get_worker_name( $worker, $field );
	}


	/**
	 * Find worker email given his ID
	 * since 1.0.6
	 * @return string
	 */
	function get_worker_email( $worker=0 ) {
		// Real person
		if ( !$this->is_dummy( $worker ) ) {
			$worker_data = get_userdata( $worker );
			if ( $worker_data )
				$worker_email = $worker_data->user_email;
			else
				$worker_email = '';
			return apply_filters( 'app_worker_email', $worker_email, $worker );
		}
		// Dummy
		if ( isset( $this->options['dummy_assigned_to'] ) && $this->options['dummy_assigned_to'] ) {
			$worker_data = get_userdata( $this->options['dummy_assigned_to'] );
			if ( $worker_data )
				$worker_email = $worker_data->user_email;
			else
				$worker_email = '';
			return apply_filters( 'app_dummy_email', $worker_email, $worker );
		}

		// If not set anything, assign to admin
		return $this->get_admin_email( );
	}

	/**
	 * Return admin email
	 * since 1.2.7
	 * @return string
	 */
	function get_admin_email( ) {
		global $current_site;
		$admin_email = get_option('admin_email');
		if ( !$admin_email )
			$admin_email = 'admin@' . $current_site->domain;

		return apply_filters( 'app_get_admin_email', $admin_email );
	}

	/**
	 * Find service name given its ID
	 * @return string
	 */
	function get_service_name( $service=0 ) {
		// Safe text if we delete a service
		$name = __('Not defined', 'appointments');
		$result = appointments_get_service( $service );
		if ( $result )
			$name = $result->name;

		$name = apply_filters( 'app_get_service_name', $name, $service );

		return stripslashes( $name );
	}

	/**
	 * Find client name given his appointment
	 * @return string
	 */
	function get_client_name( $app_id ) {
		$name = '';
		// This is only used on admin side, so an optimization is not required.
		$result = appointments_get_appointment( $app_id );
		if ( $result ) {
			// Client can be a user
			if ( $result->user ) {
				$userdata = get_userdata( $result->user );
				if ( $userdata ) {
					$href = function_exists('bp_core_get_user_domain') && (defined('APP_BP_LINK_TO_PROFILE') && APP_BP_LINK_TO_PROFILE)
						? bp_core_get_user_domain($result->user)
						: admin_url("user-edit.php?user_id="). $result->user
					;
					$name = '<a href="' . apply_filters('app_get_client_name-href', $href, $app_id, $result) . '" target="_blank">' .
						($result->name && !(defined('APP_USE_LEGACY_ADMIN_USERDATA_OVERRIDES') && APP_USE_LEGACY_ADMIN_USERDATA_OVERRIDES) ? $result->name : $userdata->user_login) .
					'</a>';
				}
				else
					$name = $result->name;
			}
			else {
				$name = $result->name;
				if ( !$name )
					$name = $result->email;
			}
		}
		return apply_filters( 'app_get_client_name', $name, $app_id, $result );
	}

	/**
	 * Get price for the current service and worker
	 * If worker has additional price (optional), it is added to the service price
	 * @param paypal: If set true, deposit price is calculated
	 * @return string
	 */
	function get_price( $paypal=false ) {
		global $current_user;
		$this->get_lsw();
		$service_obj = appointments_get_service( $this->service );
		$worker_obj = appointments_get_worker( $this->worker );

		if ( $worker_obj && $worker_obj->price )
			$worker_price = $worker_obj->price;
		else
			$worker_price = 0;

		$price = 0;
		if ( $service_obj ) {
			$price = $service_obj->price + $worker_price;
		}


		/**
		 * Filter allows other plugins or integrations to apply a discount to
		 * the price.
		 */
		$price = apply_filters( 'app_get_price_prepare', $price, $paypal, $this );

		// Discount
		if ( $this->is_member() && isset( $this->options["members_discount"] ) && $this->options["members_discount"] ) {
			// Special condition: Free for members
			if ( 100 == $this->options["members_discount"] )
				$price = 0;
			else
				$price = $price * ( 100 - $this->options["members_discount"] )/100;
		}

		if ( $paypal ) {
			// Deposit
			if ( isset( $this->options["percent_deposit"] ) && $this->options["percent_deposit"] )
				$price = $price * $this->options["percent_deposit"] / 100;
			if ( isset( $this->options["fixed_deposit"] ) && $this->options["fixed_deposit"] )
				$price = $this->options["fixed_deposit"];

			// It is possible to ask special amounts to be paid
			$price = apply_filters( 'app_paypal_amount', $price, $this->service, $this->worker, $current_user->ID );
		} else {
			$price = apply_filters( 'app_get_price', $price, $this->service, $this->worker, $current_user->ID );
		}

		// Use number_format right at the end, cause it converts the number to a string.
		$price = number_format( $price, 2 );
		return $price;
	}

	/**
	 * Get deposit given price
	 * This is required only for manual pricing
	 * @param price: the full price
	 * @since 1.0.8
	 * @return string
	 */
	function get_deposit( $price ) {

		$deposit = 0;

		if ( !$price )
			return apply_filters( 'app_get_deposit', 0 );

		// Discount
		if ( $this->is_member() && isset( $this->options["members_discount"] ) && $this->options["members_discount"] ) {
			// Special condition: Free for members
			if ( 100 == $this->options["members_discount"] )
				$price = 0;
			else
				$price = number_format( $price * ( 100 - $this->options["members_discount"] )/100, 2 );
		}

		// Deposit
		if ( isset( $this->options["percent_deposit"] ) && $this->options["percent_deposit"] )
			$deposit = number_format( $price * $this->options["percent_deposit"] / 100, 2 );
		if ( isset( $this->options["fixed_deposit"] ) && $this->options["fixed_deposit"] )
			$deposit = $this->options["fixed_deposit"];

		return apply_filters( 'app_get_deposit', $deposit );
	}


	/**
	 * Get the capacity of the current service
	 * @return integer
	 */
	function get_capacity( $service_id = false ) {
		if ( $service_id && $service = appointments_get_service( $service_id ) ) {
			$service_id = $service->ID;
		}
		else {
			$service_id = $this->service;
		}
		return appointments_get_service_capacity( $service_id );
	}

/**
**************************************
* Methods for Specific Content Caching
* Developed especially for this plugin
**************************************
*/


	/**
	 * Get request uri
	 * @return string
	 */
	function get_uri() {
		// Get rid of # part
		if ( strpos( $_SERVER['REQUEST_URI'], '#' ) !== false ) {
			$uri_arr = explode( '#', $_SERVER['REQUEST_URI'] );
			$uri = $uri_arr[0];
		}
		else
			$uri = $_SERVER['REQUEST_URI'];

		return $uri;
	}

	/**
	 * Flush both database and object caches
	 *
	 */
	function flush_cache( ) {
		wp_cache_flush();
		appointments_clear_cache();
	}

/****************
* General methods
*****************
*/

	/**
     * Provide options if asked outside the class
 	 * @return array
     */
	function get_options() {
		return $this->options;
	}

	/**
	 * Save a message to the log file
	 */
	function log( $message='' ) {
		if ( $message ) {
			$to_put = '<b>['. date_i18n( $this->datetime_format, $this->local_time ) .']</b> '. $message;
			// Prevent multiple messages with same text and same timestamp
			if ( !file_exists( $this->log_file ) || strpos( @file_get_contents( $this->log_file ), $to_put ) === false ) {
				@file_put_contents( $this->log_file, $to_put . chr(10). chr(13), FILE_APPEND );
			}

		}
	}

	/**
	 * Remove tabs and breaks
	 */
	function esc_rn( $text ) {
		$text = str_replace( array("\t","\n","\r"), "", $text );
		return $text;
	}

	/**
	 * Converts number of seconds to hours:mins acc to the WP time format setting
	 * @param integer $secs Seconds
	 * @param bool $forced_format
	 * @param bool $do_i18n
	 * @return bool|int|string
	 */
	function secs2hours( $secs, $forced_format=false, $do_i18n = true ) {
		$min = (int)($secs / 60);
		$hours = "00";
		if ( $min < 60 )
			$hours_min = $hours . ":" . $min;
		else {
			$hours = (int)($min / 60);
			if ( $hours < 10 )
				$hours = "0" . $hours;
			$mins = $min - $hours * 60;
			if ( $mins < 10 )
				$mins = "0" . $mins;
			$hours_min = $hours . ":" . $mins;
		}
		if (!empty($forced_format)) $hours_min = strtotime($hours_min . ":00");
		else if ($this->time_format) $hours_min = strtotime($hours_min . ":00"); // @TODO: TEST THIS THOROUGHLY!!!!

		if( $do_i18n ) {
			$hours_min = date_i18n( $this->time_format, $hours_min );
		}
		elseif ( $forced_format ) {
			$hours_min = date( $forced_format, $hours_min );
		}
		else {
			$hours_min = date( $this->time_format, $hours_min );
		}

		return $hours_min;
	}

	function secs_to_24h( $secs ) {
		$min = (int)($secs / 60);
		$hours = "00";
		if ( $min < 60 )
			$hours_min = $hours . ":" . $min;
		else {
			$hours = (int)($min / 60);
			if ( $hours < 10 )
				$hours = "0" . $hours;
			$mins = $min - $hours * 60;
			if ( $mins < 10 )
				$mins = "0" . $mins;
			$hours_min = $hours . ":" . $mins;
		}

		return date( 'H:i', strtotime( $hours_min ) );
    }

	/**
	 * Return an array of preset base times, so that strange values are not set
	 * @return array
	 */
	function time_base() {
		$default = array( 10,15,30,60,90,120 );
		$options = appointments_get_options();
		$a = $options["additional_min_time"];
		// Additional time bases
		if ( isset( $a ) && $a && is_numeric( $a ) )
			$default[] = $a;
		return apply_filters( 'app_time_base', $default );
	}

	/**
	 *	Return minimum set interval time
	 *  If not set, return a safe time.
     *
	 *	@return integer
	 */
	function get_min_time() {
	    $options = appointments_get_options();
	    $min_time = $options['min_time'];
		if ( $min_time && $min_time > apply_filters( 'app_safe_min_time', 9 ) ) {
			return apply_filters( 'app-time-min_time', absint( $min_time ) );
		} else {
			return apply_filters( 'app-time-min_time', apply_filters( 'app_safe_time', 10 ) );
		}
	}

	/**
	 *	Number of days that an appointment can be taken
	 *	@return integer
	 */
	function get_app_limit() {
		$options = appointments_get_options();
		$app_limit = $options['app_limit'];
		if ( $app_limit ) {
			return apply_filters( 'app_limit', absint( $app_limit ) );
		} else {
			return apply_filters( 'app_limit', 365 );
		}
	}

	/**
	 * Return an array of weekdays
	 * @return array
	 */
	function weekdays() {
		return array(
			__('Sunday', 'appointments') => 'Sunday',
			__('Monday', 'appointments') => 'Monday',
			__('Tuesday', 'appointments') => 'Tuesday',
			__('Wednesday', 'appointments') => 'Wednesday',
			__('Thursday', 'appointments') => 'Thursday',
			__('Friday', 'appointments') => 'Friday',
			__('Saturday', 'appointments') => 'Saturday'
		);
	}

	/**
	 * Return all available statuses
	 *
	 * @deprecated Since version 1.6
	 *
	 * @return array
	 */
	function get_statuses() {
		_deprecated_function( __FUNCTION__, '1.6', 'appointments_get_statuses()' );
		return appointments_get_statuses();
	}


	/**
	 * Return a selected field name to further customize them and make translation easier
	 * @return string (name of the field)
	 */
	function get_field_name( $key ) {

		$field_names = array(
            'name'		=> __('Name', 'appointments'),
            'email'		=> __('Email', 'appointments'),
            'phone'		=> __('Phone', 'appointments'),
            'address'	=> __('Address', 'appointments'),
            'city'		=> __('City', 'appointments'),
            'note'		=> __('Note', 'appointments')
        );

		$field_names = apply_filters( 'app_get_field_name', $field_names );

		if ( array_key_exists( $key, $field_names ) ) {
			return $field_names[ $key ];
		} else {
			return __( 'Not defined', 'appointments' );
		}
	}

	/**
	 * Return an array of all available front end box classes
	 * @return array
	 */
	function get_classes() {
		return apply_filters( 'app_box_class_names',
			array(
				'free'        => __( 'Free', 'appointments' ),
				'busy'        => __( 'Busy', 'appointments' ),
				'notpossible' => __( 'Not possible', 'appointments' )
			)
		);
	}

	/**
	 * Return a default color for a selected box class
	 * @return string
	 */
	function get_preset( $class, $set ) {
	    $presets = array(
            1 => array(
                'free' => '48c048',
                'busy' => 'ffffff',
                'notpossible' => 'ffffff'
            ),
            2 => array(
	            'free' => '73ac39',
	            'busy' => '616b6b',
	            'notpossible' => '8f99a3'
            ),
            3 => array(
	            'free' => '40BF40',
	            'busy' => '454C54',
	            'notpossible' => '454C54'
            )
        );
	    return isset( $presets[ $set ][ $class ] ) ? $presets[ $set ][ $class ] : '111111';
	}

	/**
	 * Change status for a given app ID
	 *
	 * @deprecated since 1.6
	 *
	 * @return bool
	 */
	function change_status( $stat, $app_id ) {
		_deprecated_function( __FUNCTION__, '1.6', 'appointments_update_appointment_status()' );
		return appointments_update_appointment_status( $app_id, $stat );
	}


/************************************************************
* Methods for Shortcodes and those related to shortcodes only
*************************************************************
*/


	/**
	 * Generate an excerpt from the selected service/worker page
	 * Applies custom filter set instead of the default one.
	 */
	function get_excerpt( $page_id, $thumb_size, $thumb_class, $worker_id=0, $show_thumb_holder = false ) {
		$text = '';
		if ( !$page_id )
			return $text;
		$page = get_post( $page_id );
		if ( !$page )
			return $text;

		$text = get_the_excerpt( $page_id );
		if ( empty( $text ) ) {
			$text = $page->post_content;
		}

		$text = strip_shortcodes( $text );

		$text = apply_filters('app_the_content', $text, $page_id, $worker_id );
		$text = str_replace(']]>', ']]&gt;', $text);
		$excerpt_length = apply_filters('app_excerpt_length', 55);
		$excerpt_more = apply_filters('app_excerpt_more', ' &hellip; <a href="'. esc_url( get_permalink($page->ID) ) . '" target="_blank">' . __( 'More information <span class="meta-nav">&rarr;</span>', 'appointments' ) . '</a>');
		$text = wp_trim_words( $text, $excerpt_length, $excerpt_more );

		if ( $show_thumb_holder ) {
			// @TODO Little crap :( to avoid so many queries when there are many services
			$thumb = '<div class="appointments-service-thumb appointments-service-thumb-' . absint( $page_id ) . '" data-page="' . absint( $page_id ) . '"></div>';
		}
		else {
			$thumb = $this->get_thumbnail( $page_id, $thumb_size, $thumb_class, $worker_id );
		}


		return apply_filters( 'app_excerpt', $thumb. $text, $page_id, $worker_id );
	}

	/**
	 * Fetch content from the selected service/worker page.
	 * Applies custom filter set instead of the default one.
	 */
	function get_content( $page_id, $thumb_size, $thumb_class, $worker_id=0, $show_thumb_holder = false ) {
		$content = '';
		if ( !$page_id )
			return $content;
		$page = get_post( $page_id );
		if ( !$page )
			return $content;

		if ( $show_thumb_holder ) {
			// @TODO Little crap :( to avoid so many queries when there are many services
			$thumb = '<div class="appointments-service-thumb appointments-service-thumb-' . absint( $page_id ) . '" data-page="' . absint( $page_id ) . '"></div>';
		}
		else {
			$thumb = $this->get_thumbnail( $page_id, $thumb_size, $thumb_class, $worker_id );
		}


		$app_content = apply_filters( 'app_pre_content', wpautop( $this->strip_app_shortcodes( $page->post_content ), true ) );

		return apply_filters( 'app_content', $thumb. $app_content, $page_id, $worker_id );
	}

	/**
	 * Clear app shortcodes
	 * @since 1.1.9
	 */
	function strip_app_shortcodes( $content ) {
		// Don't even try to touch a non string, just in case
		if ( !is_string( $content ) )
			return $content;
		else
			return preg_replace( '%\[app_(.*?)\]%is', '', $content );
	}

	/**
	 * Get html code for thumbnail or avatar
	 */
	function get_thumbnail( $page_id, $thumb_size, $thumb_class, $worker_id ) {

		if ( $thumb_size && 'none' != $thumb_size ) {
			if ( strpos( $thumb_size, 'avatar' ) !== false ) {
				if ( strpos( $thumb_size, ',' ) !== false ) {
					$size_arr = explode( ",", $thumb_size );
					$size = $size_arr[1];
				}
				else
					$size = 96;
				$thumb = get_avatar( $worker_id, $size );
				if ( $thumb_class ) {
					// Dirty, but faster than preg_replace
					$thumb = str_replace( "class='", "class='".$thumb_class." ", $thumb );
					$thumb = str_replace( 'class="', 'class="'.$thumb_class.' ', $thumb );
				}
			}
			else {
				if ( strpos( $thumb_size, ',' ) !== false )
					$size = explode( ",", $thumb_size );
				else
					$size = $thumb_size;

				$thumb = get_the_post_thumbnail( $page_id, $size, apply_filters( 'app_thumbnail_attr', array('class'=>$thumb_class) ) );
			}
		}
		else
			$thumb = '';

		return apply_filters( 'app_thumbnail', $thumb, $page_id, $worker_id );
	}

	

	

	/**
	 * Build GCal url for GCal Button. It requires UTC time.
	 * @param start: Timestamp of the start of the app
	 * @param end: Timestamp of the end of the app
	 * @param php: If this is called for php. If false, called for js
	 * @param address: Address of the appointment
	 * @param city: City of the appointment
	 * @return string
	 */
	function gcal( $service, $start, $end, $php=false, $address, $city ) {
		// Find time difference from Greenwich as GCal asks UTC

		$text = sprintf(__('%s Appointment', 'appointments'), $this->get_service_name($service));

		if (!$php) $text = esc_js( $text );

		$gmt_start = get_gmt_from_date( date( 'Y-m-d H:i:s', $start ), "Ymd\THis\Z" );
		$gmt_end = get_gmt_from_date( date( 'Y-m-d H:i:s', $end ), "Ymd\THis\Z" );

		$location = isset($this->options["gcal_location"]) && '' != trim($this->options["gcal_location"])
			? esc_js(str_replace(array('ADDRESS', 'CITY'), array($address, $city), $this->options["gcal_location"]))
			: esc_js(get_bloginfo('description'))
		;

		$param = array(
			'action' => 'TEMPLATE',
			'text' => $text,
			'dates' => $gmt_start . "/" . $gmt_end,
			'sprop' => 'website:' . home_url(),
			'location' => $location
		);

		return add_query_arg(
			apply_filters('app_gcal_variables', $param, $service, $start, $end), 
			'http://www.google.com/calendar/event'
		);
	}

	/**
	 * Die showing which field has a problem
     *
     * @param string $field_name
	 */
	function json_die( $field_name ) {
		die( json_encode( array("error"=>sprintf( __( 'Something wrong about the submitted %s', 'appointments'), $this->get_field_name($field_name)))));
	}

	/**
	 * Check for too frequent back to back apps
	 * return true means no spam
	 * @return bool
	 */
	function check_spam() {
		if ( ! isset( $this->options["spam_time"] ) || ! $this->options["spam_time"] ||
		     ! isset( $_COOKIE["wpmudev_appointments"] )
		) {
			return true;
		}

		$apps = unserialize( stripslashes( $_COOKIE["wpmudev_appointments"] ) );

		if ( ! is_array( $apps ) || empty( $apps ) ) {
			return true;
		}

		$checkdate = date( 'Y-m-d H:i:s', $this->local_time - $this->options["spam_time"] );

		$results = appointments_get_appointments( array(
			'app_id'     => maybe_unserialize( $_COOKIE["wpmudev_appointments"] ),
			'status'     => 'pending',
			'date_query' => array(
				array(
					'field'   => 'created',
					'compare' => '>',
					'value'   => $checkdate
				)
			)
		) );

		// A recent app is found

		if ( $results ) {
			return false;
		}

		return true;
	}



	/**
	 * Find timestamp of first day of month for a given time
	 * @param time: input whose first day will be found
	 * @param add: how many months to add
	 * @return integer (timestamp)
	 * @since 1.0.4
	 */
	function first_of_month( $time, $add ) {
		$year = date( "Y", $time );
		$month = date( "n",  $time ); // Notice "n"

		return mktime( 0, 0, 0, $month+$add, 1, $year );
	}

	/**
	 * Helper function to create a monthly schedule
	 */
	function get_monthly_calendar( $timestamp=false, $class='', $long, $widget ) {
		$this->get_lsw();

		$price = $this->get_price( );

		$date = $timestamp ? $timestamp : $this->local_time;

		$year = date("Y", $date);
		$month = date("m",  $date);
		$time = strtotime("{$year}-{$month}-01");

		$days = (int)date('t', $time);
		$first = (int)date('w', strtotime(date('Y-m-01', $time)));
		$last = (int)date('w', strtotime(date('Y-m-' . $days, $time)));

		$schedule_key = sprintf('%sx%s', strtotime(date('Y-m-01', $time)), strtotime(date('Y-m-' . $days, $time)));

		$tbl_class = $class;
		$tbl_class = $tbl_class ? "class='{$tbl_class}'" : '';

		$ret = '';
		$ret .= '<div class="app_monthly_schedule_wrapper">';

		$ret .= '<a id="app_schedule">&nbsp;</a>';
		$ret  = apply_filters( 'app_monthly_schedule_before_table', $ret );
		$ret .= "<table width='100%' {$tbl_class}>";
		$ret .= $this->_get_table_meta_row_monthly('thead', $long);
		$ret .= '<tbody>';

		$ret = apply_filters( 'app_monthly_schedule_before_first_row', $ret );

		if ( $first > $this->start_of_week )
			$ret .= '<tr><td class="no-left-border" colspan="' . ($first - $this->start_of_week) . '">&nbsp;</td>';
		else if ( $first < $this->start_of_week )
			$ret .= '<tr><td class="no-left-border" colspan="' . (7 + $first - $this->start_of_week) . '">&nbsp;</td>';
		else
			$ret .= '<tr>';

		$todays_no = date("w", $this->local_time ); // Number of today
		$working_days = $this->get_working_days( $this->worker, $this->location ); // Get an array of working days
		$capacity = $this->get_capacity();
		$time_table = '';


		for ($i=1; $i<=$days; $i++) {
			$date = date('Y-m-' . sprintf("%02d", $i), $time);
			$dow = (int)date('w', strtotime($date));
			$ccs = strtotime("{$date} 00:00");
			$cce = strtotime("{$date} 23:59");
			if ($this->start_of_week == $dow)
				$ret .= '</tr><tr>';

			$init_time = time();

			$class_name = '';
			// First mark passed days
			if ( $this->local_time > $cce ) {
				$class_name = 'notpossible app_past';
			}
			// Then check if this time is blocked
			else if ( isset( $this->options["app_lower_limit"] ) && $this->options["app_lower_limit"]
				&&( $this->local_time + $this->options["app_lower_limit"] * 3600) > $cce ) {
				$class_name = 'notpossible app_blocked';
			}
			// Check today is holiday
			else if ( $this->is_holiday( $ccs, $cce ) ) {
				$class_name = 'notpossible app_holiday';
			}
			// Check if we are working today
			else if ( !in_array( date("l", $ccs ), $working_days ) && !$this->is_exceptional_working_day( $ccs, $cce ) ) {
				$class_name = 'notpossible notworking';
			}
			// Check if we are exceeding app limit at the end of day
			else if ( $cce > $this->local_time + ( $this->get_app_limit() + 1 )*86400 ) {
				$class_name = 'notpossible';
			}
			// If nothing else, then it must be free unless all time slots are taken
			else {
				// At first assume all cells are busy
				$this->is_a_timetable_cell_free = false;

				$time_table .= $this->get_timetable( $ccs, $capacity, $schedule_key );

				// Look if we have at least one cell free from get_timetable function
				if ( $this->is_a_timetable_cell_free )
					$class_name = 'free';
				else
					$class_name = 'busy';
				// Clear time table for widget
				if ( $widget )
					$time_table = '';
			}



			// Check for today
			if ( $this->local_time > $ccs && $this->local_time < $cce )
				$class_name = $class_name . ' today';

			$ret .= '<td class="'.$class_name.'" title="'.date_i18n($this->date_format, $ccs).'"><p>'.$i.'</p>
			<input type="hidden" class="appointments_select_time" value="'.$ccs .'" /></td>';

		}

		if ( 0 == (6 - $last + $this->start_of_week) )
			$ret .= '</tr>';
		else if ( $last > $this->start_of_week )
			$ret .= '<td class="no-right-border" colspan="' . (6 - $last + $this->start_of_week) . '">&nbsp;</td></tr>';
		else if ( $last + 1 == $this->start_of_week )
			$ret .= '</tr>';
		else
			$ret .= '<td class="no-right-border" colspan="' . (6 + $last - $this->start_of_week) . '">&nbsp;</td></tr>';

		$ret = apply_filters( 'app_monthly_schedule_after_last_row', $ret );
		$ret .= '</tbody>';
		$ret .= $this->_get_table_meta_row_monthly('tfoot', $long);
		$ret .= '</table>';
		$ret  = apply_filters( 'app_monthly_schedule_after_table', $ret );
		$ret .= '</div>';

		$ret .= '<div class="app_timetable_wrapper">';
		$ret .= $time_table;
		$ret .= '</div>';

		$ret .= '<div style="clear:both"></div>';

		$script  = '';
		$script .= 'var selector = ".app_monthly_schedule_wrapper table td.free", callback = function (e) {';
			$script .= '$(selector).off("click", callback);';
			$script .= 'var selected_timetable=$(".app_timetable_"+$(this).find(".appointments_select_time").val());';
			$script .= '$(".app_timetable:not(selected_timetable)").hide();';
			$script .= 'selected_timetable.show("slow", function () { $(selector).on("click", callback); });';
		$script .= '};';
		$script .= '$(selector).on("click", callback);';

		$this->add2footer( $script );
		return $ret;
	}

	function console_log( $start, $desc = '' ) {
		$finish = microtime( true );
		?>
		<script>console.log('<?php echo $desc . ' - ' . ( $finish - $start ); ?>');</script>
		<?php
	}

	/**
	 * Helper function to create a time table for monthly schedule
	 */
	function get_timetable( $day_start, $capacity, $schedule_key=false ) {
		$timetable_key = $day_start . '-' . $capacity;

		if ( ! $schedule_key ) {
			$timetable_key .= '-0';
		}
		else {
			$timetable_key .=  '-' . $schedule_key;
		}

		// We need this only for the first timetable
		// Otherwise $time will be calculated from $day_start
		if ( isset( $_GET["wcalendar"] ) && (int)$_GET['wcalendar'] ) {
			$time = (int)$_GET["wcalendar"];
		}
		else {
			$time = $this->local_time;
		}

		$timetable_key .= '-' . $this->worker;
		$timetable_key .= '-' . date( 'Ym', $time );

		// Calculate step
		$start = $end = 0;
		if ( $min_max = $this->min_max_wh( 0, 0 ) ) {
			$start = $min_max["min"];
			$end = $min_max["max"];
		}
		if ( $start >= $end ) {
			$start = 8;
			$end = 18;
		}
		$start = apply_filters( 'app_schedule_starting_hour', $start, $day_start, 'day' );
		$end = apply_filters( 'app_schedule_ending_hour', $end, $day_start, 'day' );

		$first = $start *3600 + $day_start; // Timestamp of the first cell
		$last = $end *3600 + $day_start; // Timestamp of the last cell
		$min_step_time = $this->get_min_time() * 60; // Cache min step increment

		if (appointments_use_legacy_duration_calculus()) {
			$step = $min_step_time; // Timestamp increase interval to one cell ahead
		} else {
			$service = appointments_get_service($this->service);
			$step = (!empty($service->duration) ? $service->duration : $min_step_time) * 60; // Timestamp increase interval to one cell ahead
		}

		if ( ! appointments_use_legacy_duration_calculus() ) {
			$start_result = appointments_get_worker_working_hours( 'open', $this->worker, $this->location );
			$start_unpacked_days = $start_result->hours;
		} else {
			$start_unpacked_days = array();
		}
		if ( appointments_use_break_times_padding_calculus() ) {
			$break_result = appointments_get_worker_working_hours( 'closed', $this->worker, $this->location );
			$break_times = $break_result->hours;
		} else {
			$break_times = array();
		}

		// Allow direct step increment manipulation,
		// mainly for service duration based calculus start/stop times
		$step = apply_filters('app-timetable-step_increment', $step, 'timetable' );

		if ( empty( $step ) || ! is_numeric( $step ) ) {
		    // If step is null/0 etc we can end up with problems
            return '';
        }

		$timetable_key .= '-' . $step . '-' . $this->service;

		// Are we looking to today?
		// If today is a working day, shows its free times by default
		if ( date( 'Ymd', $day_start ) == date( 'Ymd', $time ) )
			$style = '';
		else
			$style = ' style="display:none"';

		if ( isset( $this->timetables[ $timetable_key ] ) ) {
			$data =  $this->timetables[ $timetable_key ];
		}
		else {
			$data = array();
			for ( $t=$first; $t<$last; ) {
				$ccs = apply_filters('app_ccs', $t); 				// Current cell starts
				$cce = apply_filters('app_cce', $ccs + $step);		// Current cell ends

// Fix for service durations calculus and workhours start conflict with different duration services
// Example: http://premium.wpmudev.org/forums/topic/problem-with-time-slots-not-properly-allocating-free-time
				$this_day_key = date('l', $t);
				if (!empty($start_unpacked_days) && ! appointments_use_legacy_duration_calculus() ) {
					if (!empty($start_unpacked_days[$this_day_key])) {
						// Check slot start vs opening start
						$this_day_opening_timestamp = strtotime(date('Y-m-d ' . $start_unpacked_days[$this_day_key]['start'], $ccs));
						if ($t < $this_day_opening_timestamp) {
							$t = ($t - $step) + (apply_filters('app_safe_time', 1) * 60);
							$t = apply_filters('app_next_time_step', $t+$step, $ccs, $step); //Allows dynamic/variable step increment.
							continue;
						}

						// Check slot end vs opening end - optional, but still applies
						//$this_day_closing_timestamp = strtotime(date('Y-m-d ' . $start_unpacked_days[$this_day_key]['end'], $ccs));
						//if ($cce > $this_day_closing_timestamp) continue;
					}
				}
// Breaks are not behaving like paddings, which is to be expected.
// This fix (2) will force them to behave more like paddings
				if (!empty($break_times[$this_day_key]['active']) && appointments_use_break_times_padding_calculus() ) {
					$active = $break_times[$this_day_key]['active'];
					$break_starts = $break_times[$this_day_key]['start'];
					$break_ends = $break_times[$this_day_key]['end'];
					if (!is_array($active) && 'no' !== $active) {
						$break_start_ts = strtotime(date('Y-m-d ' . $break_starts, $ccs));
						$break_end_ts = strtotime(date('Y-m-d ' . $break_ends, $ccs));
						if ($t == $break_start_ts) {
							$t += ($break_end_ts - $break_start_ts) - $step;
							$t = apply_filters('app_next_time_step', $t+$step, $ccs, $step); //Allows dynamic/variable step increment.
							continue;
						}
					} else if (is_array($active) && in_array('yes', array_values($active))) {
						$has_break_time = false;
						for ($idx=0; $idx<count($break_starts); $idx++) {
							$break_start_ts = strtotime(date('Y-m-d ' . $break_starts[$idx], $ccs));
							$break_end_ts = strtotime(date('Y-m-d ' . $break_ends[$idx], $ccs));
							if ($t == $break_start_ts) {
								$has_break_time = $break_end_ts - $break_start_ts;
								break;
							}
						}
						if ($has_break_time) {
							$t += ($has_break_time - $step);
							$t = apply_filters('app_next_time_step', $t+$step, $ccs, $step); //Allows dynamic/variable step increment.
							continue;
						}
					}
				}
// End fixes area
				$is_busy = $this->is_busy( $ccs, $cce, $capacity );

				$title = apply_filters('app-schedule_cell-title', date_i18n($this->datetime_format, $ccs), $is_busy, $ccs, $cce, $schedule_key);

				$class_name = '';
				// Mark now
				if ( $this->local_time > $ccs && $this->local_time < $cce )
					$class_name = 'notpossible now';
				// Mark passed hours
				else if ( $this->local_time > $ccs )
					$class_name = 'notpossible app_past';
				// Then check if this time is blocked
				else if ( isset( $this->options["app_lower_limit"] ) && $this->options["app_lower_limit"]
				          &&( $this->local_time + $this->options["app_lower_limit"] * 3600) > $cce )
					$class_name = 'notpossible app_blocked';
				// Check if this is break
				else if ( $this->is_break( $ccs, $cce ) )
					$class_name = 'notpossible app_break';
				// Then look for appointments
				else if ( $is_busy )
					$class_name = 'busy';
				// Then check if we have enough time to fulfill this app
				else if ( !$this->is_service_possible( $ccs, $cce, $capacity ) )
					$class_name = 'notpossible service_notpossible';
				// If nothing else, then it must be free
				else {
					$class_name = 'free';
					// We found at least one timetable cell to be free
				}
				$class_name = apply_filters( 'app_class_name', $class_name, $ccs, $cce );

				$data[] = array(
					'class' => $class_name,
					'title' => $title,
					'hours' => $this->secs2hours( $ccs - $day_start ),
					'ccs' => $ccs,
					'cce' => $cce
				);

				$t = apply_filters('app_next_time_step', $t+$step, $t, $step); //Allows dynamic/variable step increment.
			}

		}




		$this->timetables[ $timetable_key ] = $data;

		// Save timetables only once at the end of the execution
		//add_action( 'shutdown', array( $this, 'regenerate_timetables' ) );
		add_action( 'shutdown', array( $this, 'save_timetables' ) );


		$ret  = '';
		$ret .= '<div class="app_timetable app_timetable_'.$day_start.'"'.$style.'>';
		$ret .= '<div class="app_timetable_title">';
		$ret .= date_i18n( $this->date_format, $day_start );
		$ret .= '</div>';

		foreach ( $data as $row ) {
			if ( 'free' == $row['class'] ) {
				// We found at least a cell free
				$this->is_a_timetable_cell_free = true;
			}

			$ret .= '<div class="app_timetable_cell app_timetable_cell-' . date( 'H-i', $row['ccs'] ) . '  '.$row['class'].'" title="'.esc_attr($row['title']).'">'.
			        $row['hours']. '<input type="hidden" class="appointments_take_appointment" value="' . $this->pack( $row['ccs'], $row['cce'] ) . '" />';

			$ret .= '</div>';
		}
		$ret .= '<div style="clear:both"></div>';

		$ret .= '</div>';



		return $ret;

	}

	/**
	 * Regenerate most used timetables so users do not wait too long
	 * when viewing calendars
	 */
	public function regenerate_timetables() {
		// @TODO: Regenerate based on use stats instead. This is too random.
		global $wpdb;

		$services = appointments_get_services();
		$workers = appointments_get_workers();

		$durations = wp_list_pluck( $services, 'duration' );
		$durations = array_unique( $durations );

		$capacities = wp_list_pluck( $services, 'capacity' );
		$capacities = array_unique( $capacities );

		$month = date( 'm', current_time( 'timestamp' ) );
		$day_start = strtotime( date( 'Y-' . $month . '-01 00:00:00' ) ); // First day of this month
		// But do not regenerate more than 6 timetables
		for ( $i = 0; $i <= 3 && $i < count( $durations ); $i++ ) {
			// @TODO if it's cached, don't count this one
			for ( $j = 0; $j <= 2 && $j < count( $capacities ); $j++ ) {
				$this->get_timetable( $durations[ $i ], $capacities[ $j ] );
			}
		}
	}

	public function save_timetables() {
		set_transient( 'app_timetables', $this->timetables, 86400 ); // save for one day
	}

	function _get_table_meta_row_monthly ($which, $long) {
		if ( ! $long ) {
			$day_names_array = $this->arrange( $this->get_short_day_names(), false );
		} else {
			$day_names_array = $this->arrange( $this->get_day_names(), false );
		}
		$cells = '<th>' . join('</th><th>', $day_names_array) . '</th>';
		return "<{$which}><tr>{$cells}</tr></{$which}>";
	}

	/**
	 * Helper function to create a weekly schedule
	 */
	function get_weekly_calendar( $timestamp=false, $class='', $long = false ) {

		$this->get_lsw();

		$current_time = current_time( 'timestamp' );
		$date = $timestamp ? $timestamp : $current_time;
		return appointments_weekly_calendar( $date, array(
            'worker_id' => $this->worker,
            'service_id' => $this->service,
            'location_id' => $this->location,
            'long' => $long,
            'class' => $class,
            'echo' => false
        ));

	}

	function get_day_names() {
	    global $wp_locale;
	    return $wp_locale->weekday;
	}

	function get_short_day_names () {
	    global $wp_locale;
		return array_values( $wp_locale->weekday_initial );
	}

	/**
	 * Returns the timestamp of Sunday of the current time or selected date
	 * @param timestamp: Timestamp of the selected date or false for current time
	 * @return integer (timestamp)
	 */
	function sunday( $timestamp=false ) {
		$date = $timestamp ? $timestamp : $this->local_time;
		// Return today's timestamp if today is sunday and start of the week is set as Sunday
		if ( "Sunday" == date( "l", $date ) && 0 == $this->start_of_week )
			return strtotime("today", $date);
		// Else return last week's timestamp
		else
			return strtotime("last Sunday", $date );
	}

	/**
	 * Arranges days array acc. to start of week, e.g 1234567 (Week starting with Monday)
	 * @param days: input array
	 * @param prepend: What to add as first element
	 * @pram nod: If number of days (true) or name of days (false)
	 * @return array
	 */
	function arrange( $days, $prepend, $nod=false ) {
		if ( $this->start_of_week ) {
			for ( $n = 1; $n<=$this->start_of_week; $n++ ) {
				array_push( $days, array_shift( $days ) );
			}
			// Fix for displaying past days; apply only for number of days
			if ( $nod ) {
				$first = false;
				$temp = array();
				foreach ( $days as $key=>$day ) {
					if ( !$first )
						$first = $day; // Save the first day
					if ( $day < $first )
						$temp[$key] = $day + 7; // Latter days should be higher than the first day
					else
						$temp[$key] = $day;
				}
				$days = $temp;
			}
		}
		if ( false !== $prepend )
			array_unshift( $days, $prepend );

		return $days;
	}

	/**
	 * Get which days of the week we are working
	 * @return array (may be empty)
	 */
	function get_working_days( $worker=0, $location=0 ) {
		$working_days = array();
		$result = appointments_get_worker_working_hours( 'open', $worker,  $location );
		if ( $result !== null ) {
			$days = $result->hours;
			if ( is_array( $days ) ) {
				foreach ( $days as $day_name=>$day ) {
					if ( isset( $day["active"] ) && 'yes' == $day["active"] ) {
						$working_days[] = $day_name;
					}
				}
			}
		}
		return $working_days;
	}

	/**
	 * Check if this is an exceptional working day
	 * Optionally a worker is selectable ( $w != 0 )
	 * @return bool
	 */
	function is_exceptional_working_day( $ccs, $cce, $w=0 ) {
		// A worker can be forced
		if ( !$w )
			$w = $this->worker;
		$is_working_day = false;
		$result = appointments_get_worker_exceptions( $w, 'open', $this->location );
		if ( $result != null  && strpos( $result->days, date( 'Y-m-d', $ccs ) ) !== false )
			$is_working_day = true;

		return apply_filters( 'app_is_exceptional_working_day', $is_working_day, $ccs, $cce, $this->service, $w );
	}

	/**
	 * Check if today is holiday
	 * Optionally a worker is selectable ( $w != 0 )
	 * @return bool
	 */
	function is_holiday( $ccs, $cce, $w=0 ) {
		// A worker can be forced
		if ( ! $w ) {
			$w = $this->worker;
		}

		return appointments_is_worker_holiday( $w, $ccs, $cce );
	}

	/**
	 * Check if it is break time
	 * Optionally a worker is selectable ( $w != 0 )
	 * @return bool
	 */
	function is_break( $ccs, $cce, $w=0 ) {
		// A worker can be forced
		if ( ! $w ) {
			$w = $this->worker;
		}

		return appointments_is_interval_break( $ccs, $cce, $w, $this->location );
	}

	/**
	 * Check if a specific worker is working at this time slot
	 * @return bool
	 * @since 1.2.2
	 */
	function is_working( $ccs, $cse, $w ) {
		if ( $this->is_exceptional_working_day( $ccs, $cse, $w ) ) {
			return true;
		}
		if ( $this->is_holiday( $ccs, $cse, $w ) ) {
			return false;
		}
		if ( $this->is_break( $ccs, $cse, $w ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Correctly calculate timestamp based on day and hours:min
	 * This is required as php versions prior to 5.3 cannot calculate 24:00
	 * @param $this_day: Date in d F Y format
	 * @param $end: time in military hours:min format
	 * @since 1.1.8
	 * @return integer (timestamp)
	 */
	function str2time( $this_day, $end ) {
		if ( '24:00' != $end )
			return strtotime( $this_day. " ". $end, $this->local_time );
		else
			return ( strtotime( $this_day. " 23:59", $this->local_time ) + 60 );
	}

	/**
	 * Check if time is enough for this service
	 * e.g if we are working until 6pm, it is not possible to take an app with 60 mins duration at 5:30pm
	 * Please note that "not possible" is an exception
	 * @return bool
	 */
	function is_service_possible( $ccs, $cce, $capacity ) {

		// If this cell exceeds app limit then return false
		if ( $this->get_app_limit() < ceil( ( $ccs - $this->local_time ) /86400 ) )
			return false;

		$result = appointments_get_service( $this->service );
		if ( !$result !== null ) {
			$duration = $result->duration;
			if( !$duration )
				return true; // This means min time will be applied. No need to look

			// The same for break time
			if ( isset( $this->options["allow_overwork_break"] ) && 'yes' == $this->options["allow_overwork_break"] )
				$allow_overwork_break = true;
			else
				$allow_overwork_break = false;

			// Check for further appointments or breaks on this day, if this is a lasting appointment
			if ( $duration > $this->get_min_time() ) {
				$step = ceil( $duration/$this->get_min_time() );
				$min_secs = $this->get_min_time() *60;
				if ( $step < 20 ) { // Let's not exaggerate !
					for ( $n =1; $n < $step; $n++ ) {
						if ( $this->is_busy( $ccs + $n * $min_secs, $ccs + ($n+1) * $min_secs, $capacity ) )
							return false; // There is an appointment in the predeeding times
						// We can check breaks here too
						if ( !$allow_overwork_break ) {
							if ( $this->is_break( $ccs + $n * $min_secs, $ccs + ($n+1) * $min_secs ) )
								return false; // There is a break in the predeeding times
						}
					}
				}
			}
			// Now look where our working hour ends

			$days = wp_cache_get('app-open_times-for-' . $this->worker);
			if (!$days) {
				// Preprocess and cache workhours
				// Look where our working hour ends
				$result_days = appointments_get_worker_working_hours( 'open', $this->worker, $this->location );
				if ( $result_days && is_object( $result_days ) && ! empty( $result_days->hours ) ) {
					$days = $result_days->hours;
				}
				if ( $days ) {
					wp_cache_set( 'app-open_times-for-' . $this->worker, $days );
				}
			}
			if (!is_array($days) || empty($days)) return true;

			// If overwork is allowed, lets mark this
			if ( isset( $this->options["allow_overwork"] ) && 'yes' == $this->options["allow_overwork"] )
				$allow_overwork = true;
			else
				$allow_overwork = false;

			// What is the name of this day?
			$this_days_name = date("l", $ccs );
			// This days midnight
			$this_day = date("d F Y", $ccs );
			// Will the service exceed or working time?
			$css_plus_duration = $ccs + ($duration *60);

			foreach( $days as $day_name=>$day ) {
				// // Jose's fix pt1 (c19c7d65bb860a265ceb7f6a6075ae668bd60100)
				//if ( $day_name == $this_days_name && isset( $day["active"] ) && 'yes' == $day["active"] ) {
				if ( $day_name == $this_days_name ) {

					// Special case: End time is 00:00
					$end_mil = $this->to_military( $day["end"] );
					if ( '00:00' == $end_mil )
						$end_mil = '24:00';

					if ( $allow_overwork ) {
						if ( $ccs >= $this->str2time( $this_day, $end_mil ) )
							return false;
					}
					else {
						if (  $css_plus_duration > $this->str2time( $this_day, $end_mil ) )
							return false;
					}

					// We need to check a special case where schedule starts on eg 4pm, but our work starts on 4:30pm.
					if ( $ccs < strtotime( $this_day . " " . $this->to_military( $day["start"] ) , $this->local_time ) )
						return false;
				}
			}

		}
		return true;
	}

	/**
	 * Return available number of workers for a time slot
	 * e.g if one worker works between 8-11 and another works between 13-15, there is no worker between 11-13
	 * This is called from is_busy function
	 * since 1.0.6
	 * @return integer
	 */
	function available_workers( $ccs, $cce ) {
		// If a worker is selected we dont need to do anything special

		if ( $this->worker )
			return $this->get_capacity();

		return appointments_get_available_workers_for_interval( $ccs, $cce, $this->service, $this->location );
	}

	/**
	 * Check if a cell is not available, i.e. all appointments taken OR we dont have workers for this time slot
	 * @return bool
	 */
	function is_busy( $start, $end, $capacity ) {
	    $args = array(
            'location_id' => $this->location,
            'service_id' => $this->service,
            'worker_id' => $this->worker
        );
		return apppointments_is_range_busy( $start, $end, $args );
	}



	/**
	 * Remove duplicate appointment objects by app ID
	 * @since 1.1.5.1
	 * @return array of objects
	 */
	function array_unique_object_by_ID( $apps ) {
		if ( !is_array( $apps ) || empty( $apps ) )
			return array();
		$idlist = array();
		// Save array to a temp area
		$result = $apps;
		foreach ( $apps as $key=>$app ) {
			if ( isset( $app->ID ) ) {
				if ( in_array( $app->ID, $idlist ) )
					unset( $result[$key] );
				else
					$idlist[] = $app->ID;
			}
		}
		return $result;
	}

	/**
	 * Get the maximum and minimum working hour
	 * @return array|false
	 */
	function min_max_wh( $worker=0, $location=0 ) {
		$this->get_lsw();

		$result = appointments_get_worker_working_hours( 'open', $this->worker, $this->location );
		if ( $result !== null ) {
			$days = $result->hours;
			$days = array_filter($days);
			if ( is_array( $days ) ) {
				$min = 24; $max = 0;
				foreach ( $days as $day ) {
					// Jose's fix pt2 (c19c7d65bb860a265ceb7f6a6075ae668bd60100)
					/*
					if ( isset( $day["active"] ) && 'yes' == $day["active"] ) {
						$start = date( "G", strtotime( $this->to_military( $day["start"] ) ) );
						$end_timestamp = strtotime( $this->to_military( $day["end"] ) );
						$end = date( "G", $end_timestamp );
						// Add 1 hour if there are some minutes left. e.g. for 10:10pm, make max as 23
						if ( '00' != date( "i", $end_timestamp ) && $end != 24 )
							$end = $end + 1;
						if ( $start < $min )
							$min = $start;
						if ( $end > $max )
							$max = $end;
						// Special case: If end is 0:00, regard it as 24
						if ( 0 == $end && '00' == date( "i", $end_timestamp ) )
							$max = 24;
					}
					*/
					$start = date( "G", strtotime( $this->to_military( $day["start"] ) ) );
	                $end_timestamp = strtotime( $this->to_military( $day["end"] ) );
	                $end = date( "G", $end_timestamp );
	                // Add 1 hour if there are some minutes left. e.g. for 10:10pm, make max as 23
	                if ( '00' != date( "i", $end_timestamp ) && $end != 24 )
	                    $end = $end + 1;
	                if ( $start < $min )
	                    $min = $start;
	                if ( $end > $max )
	                    $max = $end;
	                // Special case: If end is 0:00, regard it as 24
	                if ( 0 == $end && '00' == date( "i", $end_timestamp ) )
	                    $max = 24;
				}
				return array( "min"=>$min, "max"=>$max );
			}
		}
		return false;
	}

	/**
	 * Convert any time format to military format
	 * @since 1.0.3
	 * @return string
	 */
	function to_military( $time, $end=false ) {
		// Already in military format
		if ( 'H:i' == $this->time_format )
			return $time;
		// In one of the default formats
		if ( 'g:i a' == $this->time_format  || 'g:i A' == $this->time_format )
			return date( 'H:i', strtotime( $time ) );

		// Custom format. Use a reference time
		// ref will something like 23saat45dakika
		$ref = date_i18n( $this->time_format, strtotime( "23:45" ) );
		if ( strpos( $ref, "23" ) !== false )
			$twentyfour = true;
		else
			$twentyfour = false;
		// Now ref is something like saat,dakika
		$ref = ltrim( str_replace( array( '23', '45' ), ',', $ref ), ',' );
		$ref_arr = explode( ',', $ref );
		if ( isset( $ref_arr[0] ) ) {
			$s = $ref_arr[0]; // separator. We will replace it by :
			if ( isset($ref_arr[1]) && $ref_arr[1] )
				$e = $ref_arr[1];
			else {
				$e = 'PLACEHOLDER';
				$time = $time. $e; // Add placeholder at the back
			}
			if ( $twentyfour )
				$new_e = '';
			else
				$new_e = ' a';
		}
		else
			return $time; // Nothing found ??

		return date( 'H:i', strtotime( str_replace( array($s,$e), array(':',$new_e), $time ) ) );
	}


	/**
	 * Pack several fields as a string using glue ":"
	 * location : service : worker : ccs : cce : post ID
	 * @return string
	 */
	function pack( $ccs, $cce ){
		global $post;
		if ( is_object( $post ) )
			$post_id = $post->ID;
		else
			$post_id = 0;
		return $this->location . ":" . $this->service . ":" . $this->worker . ":" . $ccs . ":" . $cce . ":" . $post_id;
	}

	/**
	 * Save a cookie so that user can see his appointments
	 */
	function save_cookie( $app_id, $name, $email, $phone, $address, $city, $gcal ) {
		if ( isset( $_COOKIE["wpmudev_appointments"] ) )
			$apps = unserialize( stripslashes( $_COOKIE["wpmudev_appointments"] ) );
		else
			$apps = array();

		$apps[] = $app_id;

		// Prevent duplicates
		$apps = array_unique( $apps );
		// Add 365 days grace time
		$expire = $this->local_time + 3600 * 24 * ( $this->options["app_limit"] + 365 );

		$expire = apply_filters( 'app_cookie_time', $expire );

		if ( defined('COOKIEPATH') ) $cookiepath = COOKIEPATH;
		else $cookiepath = "/";
		if ( defined('COOKIEDOMAIN') ) $cookiedomain = COOKIEDOMAIN;
		else $cookiedomain = '';

		@setcookie("wpmudev_appointments", serialize($apps), $expire, $cookiepath, $cookiedomain);

		$data = array(
					"n"	=> $name,
					"e"	=> $email,
					"p"	=> $phone,
					"a"	=> $address,
					"c"	=> $city,
					"g"	=> $gcal
					);
		@setcookie("wpmudev_appointments_userdata", serialize($data), $expire, $cookiepath, $cookiedomain);

		// May be required to clean up or modify userdata cookie
		do_action( 'app_save_cookie', $app_id, $apps );

		// Save user data too
		if ( is_user_logged_in() && defined('APP_USE_LEGACY_USERDATA_OVERWRITING') && APP_USE_LEGACY_USERDATA_OVERWRITING ) {
			global $current_user;
			if ( $name )
				update_user_meta( $current_user->ID, 'app_name', $name );
			if ( $email )
				update_user_meta( $current_user->ID, 'app_email', $email );
			if ( $phone )
				update_user_meta( $current_user->ID, 'app_phone', $phone );
			if ( $address )
				update_user_meta( $current_user->ID, 'app_address', $address );
			if ( $city )
				update_user_meta( $current_user->ID, 'app_city', $city );

			do_action( 'app_save_user_meta', $current_user->ID, array( 'name'=>$name, 'email'=>$email, 'phone'=>$phone, 'address'=>$address, 'city'=>$city ) );
		}
	}

	/**
	 * Make sure we clean up cookies after logging out.
	 */
	public function drop_cookies_on_logout () {
	    $options = appointments_get_options();
		if ( 'yes' !== $options['login_required'] ) {
			return;
		}

		$path = defined('COOKIEPATH')
			? COOKIEPATH
			: '/'
		;
		$domain = defined('COOKIEDOMAIN')
			? COOKIEDOMAIN
			: ''
		;
		$drop = $this->local_time - 3600;

		@setcookie("wpmudev_appointments", "", $drop, $path, $domain);
		@setcookie("wpmudev_appointments_userdata", "", $drop, $path, $domain);
	}


/*******************************
* Methods for frontend login API
********************************
*/


/*******************************
* User methods
********************************
*/
	

	

/****************************************
* Methods for integration with Membership
*****************************************
*/


	/**
	* Finds if user is Membership member with sufficient level
	* @return bool
	*/
	function is_member( ) {
	    $membership_active = ( is_admin() && class_exists('membershipadmin') ) || ( !is_admin() && class_exists('membershippublic') );
		if ( $membership_active && isset( $this->options["members"] ) ) {
			global $current_user;
			$meta = maybe_unserialize( $this->options["members"] );
			$member = new M_Membership($current_user->ID);
			if( is_array( $meta ) && $current_user->ID > 0 && $member->has_levels()) {
				// Load the levels for this member
				$levels = $member->get_level_ids( );
				if ( is_array( $levels ) && is_array( $meta["level"] ) ) {
					foreach ( $levels as $level ) {
						if ( in_array( $level->level_id, $meta["level"] ) )
							return true; // Yes, user has sufficent level
					}
				}
			}
		}
		return false;
	}

/*****************************************
* Methods for integration with Marketpress
******************************************
*/

	/**
	 * Check if Marketpress plugin is active
	 * @Since 1.0.1
	 *
	 * @deprecated
	 */
	function check_marketpress_plugin() {
		global $mp;
		return class_exists('MarketPress') && is_object( $mp );
	}




/*******************************
* Methods for inits, styles, js
********************************
*/

	/**
	 * Initialize widgets
	 */
	function widgets_init() {
		if ( !is_blog_installed() )
			return;

		register_widget( 'Appointments_Widget_Services' );
		register_widget( 'Appointments_Widget_Service_Providers' );
		register_widget( 'Appointments_Widget_Monthly_Calendar' );
	}

	/**
	 * Add a script to be used in the footer, checking duplicates
	 * In some servers, footer scripts were called twice. This function fixes it.
	 * @since 1.2.0
	 */
	function add2footer( $script='' ) {

		if ( $script && strpos( $this->script, $script ) === false )
			$this->script = $this->script . $script;
	}

	/**
	 * Load javascript to the footer
	 */
	function wp_footer() {
		$script = '';
		$this->script = apply_filters( 'app_footer_scripts', $this->script );

        if ( $this->script ) {
	        ob_start();
	        ?>
	        <script type='text/javascript'>
		        var appDocReadyHandler = function($) {
					<?php echo $this->script; ?>
		        };
		        jQuery(document).ready(appDocReadyHandler);
	        </script>
	        <?php
	        $script = ob_get_clean();
        }

		echo $this->esc_rn( $script );
		do_action('app-footer_scripts-after');
	}

	/**
	 * Load style and script only when they are necessary
	 * http://beerpla.net/2010/01/13/wordpress-plugin-development-how-to-include-css-and-javascript-conditionally-and-only-when-needed-by-the-posts/
	 */
	function load_styles( $posts ) {
		if ( empty($posts) || is_admin() )
			return $posts;

		$this->shortcode_found = false; // use this flag to see if styles and scripts need to be enqueued
		foreach ( $posts as $post ) {
			if ( is_object( $post ) && stripos( $post->post_content, '[app_' ) !== false ) {
				$this->shortcode_found = true;

				do_action('app-shortcodes-shortcode_found', $post);
			}
		}

		if ( $this->shortcode_found )
			$this->load_scripts_styles( );

		return $posts;
	}

	/**
	 * Function to load all necessary scripts and styles
	 * Can be called externally, e.g. when forced from a page template
	 */
	function load_scripts_styles( ) {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-tablesorter', $this->plugin_url . '/js/jquery.tablesorter.min.js', array('jquery'), $this->version );
		add_action( 'wp_footer', array( &$this, 'wp_footer' ) );	// Publish plugin specific scripts in the footer

		// TODO: consider this
		wp_enqueue_script( 'app-js-check', $this->plugin_url . '/js/js-check.js', array('jquery'), $this->version);

		$thank_page_id = ! empty( $this->options['thank_page'] ) ? absint( $this->options['thank_page'] ) : 0;
		$cancel_page_id = ! empty( $this->options['cancel_page'] ) ? absint( $this->options['cancel_page'] ) : 0;
		wp_localize_script( 'app-js-check', '_appointments_data',
			array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'root_url' => plugins_url('appointments/images/'),
				'thank_page_url' => get_permalink( $thank_page_id ),
				'cancel_url' => get_permalink( $cancel_page_id )
			)
		);

		if ( !current_theme_supports( 'appointments_style' ) ) {
			wp_enqueue_style( "appointments", $this->plugin_url. "/css/front.css", array(), $this->version );
			add_action( 'wp_head', array( &$this, 'wp_head' ) );
		}

		do_action('app-scripts-general');

		// Prevent external caching plugins for this page
		if ( !defined( 'DONOTCACHEPAGE' ) )
			define( 'DONOTCACHEPAGE', true );
		// Prevent W3T Minify
		if ( !defined( 'DONOTMINIFY' ) )
			define( 'DONOTMINIFY', true );

		// Set up services support defaults
		$show_login_button = array('google', 'wordpress');
		if (!empty($this->options['facebook-app_id'])) $show_login_button[] = 'facebook';
		if (!empty($this->options['twitter-app_id']) && !empty($this->options['twitter-app_secret'])) $show_login_button[] = 'twitter';

		// Is registration allowed?
		$do_register = is_multisite()
			? in_array(get_site_option('registration'), array('all', 'user'))
			: (int)get_option('users_can_register')
		;

		// Load the rest only if API use is selected
		if (@$this->options['accept_api_logins']) {
			wp_enqueue_script('appointments_api_js', $this->plugin_url . '/js/appointments-api.js', array('jquery'), $this->version );
			wp_localize_script('appointments_api_js', 'l10nAppApi', apply_filters('app-scripts-api_l10n', array(
				'facebook' => __('Login with Facebook', 'appointments'),
				'twitter' => __('Login with Twitter', 'appointments'),
				'google' => __('Login with Google+', 'appointments'),
				'wordpress' => __('Login with WordPress', 'appointments'),
				'submit' => __('Submit', 'appointments'),
				'cancel' => _x('Cancel', 'Drop current action', 'appointments'),
				'please_wait' => __('Please, wait...', 'appointments'),
				'logged_in' => __('You are now logged in', 'appointments'),
				'error' => __('Login error. Please try again.', 'appointments'),
				'_can_use_twitter' => (!empty($this->options['twitter-app_id']) && !empty($this->options['twitter-app_secret'])),
				'show_login_button' => $show_login_button,
				'register' => ($do_register ? __('Register', 'appointments') : ''),
				'registration_url' => ($do_register ? wp_registration_url() : ''),
			)));

			if (!empty($this->options['facebook-app_id'])) {
				if (!$this->options['facebook-no_init']) {
					add_action('wp_footer', create_function('', "echo '" .
					sprintf(
						'<div id="fb-root"></div><script type="text/javascript">
						window.fbAsyncInit = function() {
							FB.init({
							  appId: "%s",
							  status: true,
							  cookie: true,
							  xfbml: true
							});
						};
						// Load the FB SDK Asynchronously
						(function(d){
							var js, id = "facebook-jssdk"; if (d.getElementById(id)) {return;}
							js = d.createElement("script"); js.id = id; js.async = true;
							js.src = "//connect.facebook.net/en_US/all.js";
							d.getElementsByTagName("head")[0].appendChild(js);
						}(document));
						</script>',
						$this->options['facebook-app_id']
					) .
					"';"));
				}
			}
			do_action('app-scripts-api');
		}

		/**
		 * Fired when scripts/styles have been loaded
		 */
		do_action( 'appointments_scripts_loaded' );
	}

	/**
	 * css that will be added to the head, again only for app pages
	 */
	function wp_head() {

		?>
		<style type="text/css">
		<?php

		if ( isset( $this->options["additional_css"] ) && '' != trim( $this->options["additional_css"] ) ) {
			echo $this->options['additional_css'];
		}

		foreach ( $this->get_classes() as $class=>$name ) {
			if ( !isset( $this->options["color_set"] ) || !$this->options["color_set"] ) {
				if ( isset( $this->options[$class."_color"] ) )
					$color = $this->options[$class."_color"];
				else
					$color = $this->get_preset( $class, 1 );
			}
			else
				$color = $this->get_preset( $class, $this->options["color_set"] );

			echo 'td.'.$class.',div.'.$class.' {background: #'. $color .' !important;}';
		}

		?>
		</style>
		<?php
	}

	/**
     * Localize the plugin
     */
	function localization() {
		// Load up the localization file if we're using WordPress in a different language
		// Place it in Appointments+'s "languages" folder and name it "appointments-[value in wp-config].mo"
		load_plugin_textdomain( 'appointments', false, '/appointments/languages/' );
	}

	/**
	 *	Add initial settings
	 *
	 */
	function init() {

		// Since wp-cron is not reliable, use this instead
		add_option( "app_last_update", time() );

		$confirmation_message = App_Template::get_default_confirmation_message();
		$reminder_message = App_Template::get_default_reminder_message();

		add_option('appointments_options', array(
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
			'confirmation_subject'		=> __('Confirmation of your Appointment','appointments'),
			'confirmation_message'		=> $confirmation_message,
			'reminder_subject'			=> __('Reminder for your Appointment','appointments'),
			'reminder_message'			=> $reminder_message,
			'log_emails'				=> 'yes',
			'allow_cancel'				=> 'no',
			'cancel_page'				=> 0
		));

		do_action( 'appointments_init', $this );

		//  Run this code not before 10 mins

		if ( ( time() - get_option( "app_last_update" ) ) < apply_filters( 'app_update_time', 600 ) ) {
			return;
		}

		$this->remove_appointments();

		update_option( "app_last_update", time() );

	}

/*******************************
* Methods for Confirmation
********************************

	/**
	 *	Send confirmation email
	 * @param app_id: ID of the app whose confirmation will be sent
     * @return boolean
     * @deprecated since 1.7.3
	 */
	function send_confirmation( $app_id ) {
		_deprecated_function( __FUNCTION__, '1.7.3', 'appointments_send_confirmation()' );
		return appointments_send_confirmation( $app_id );
	}

	/**
	 * Send notification email
	 * @param cancel: If this is a cancellation
	 * @since 1.0.2
	 *
	 * @deprecated since 1.7.3
	 * 
	 * @return bool
	 */
	function send_notification( $app_id, $cancel=false ) {
		_deprecated_function( __FUNCTION__, '1.7.3', 'Appointments_Notification_Manager::send_notification()' );
		return $this->notifications->send_notification( $app_id, $cancel );
	}

	/**
	 * Sends out a removal notification email.
	 * This email is sent out only on admin status change, *not* on appointment cancellation by user.
	 * The email will go out to the client and, perhaps, worker and admin.
	 *
	 * @deprecated since 1.7.3
	 */
	function send_removal_notification ($app_id) {
		_deprecated_function( __FUNCTION__, '1.7.3', 'appointments_send_removal_notification()' );
		return appointments_send_removal_notification( $app_id );
	}

	/**
	 *	Check and send reminders to clients and workers for appointments
	 * @deprecated since 1.7.3
	 */
	function maybe_send_reminders() {
		_deprecated_function( __FUNCTION__, '1.7.3' );
	}

	/**
	 *	Replace placeholders with real values for email subject and content
	 */
	function _replace( $text, $user, $service, $worker, $datetime, $price, $deposit, $phone='', $note='', $address='', $email='', $city='' ) {
		/*
		return str_replace(
					array( "SITE_NAME", "CLIENT", "SERVICE_PROVIDER", "SERVICE", "DATE_TIME", "PRICE", "DEPOSIT", "PHONE", "NOTE", "ADDRESS", "EMAIL", "CITY" ),
					array( wp_specialchars_decode(get_option('blogname'), ENT_QUOTES), $user, $worker, $service, mysql2date( $this->datetime_format, $datetime ), $price, $deposit, $phone, $note, $address, $email, $city ),
					$text
				);
		*/
		$balance = !empty($price) && !empty($deposit)
			? (float)$price - (float)$deposit
			: (!empty($price) ? $price : 0.0)
		;
		$replacement = array(
			'SITE_NAME' => wp_specialchars_decode(get_option('blogname'), ENT_QUOTES),
			'CLIENT' => $user,
			'SERVICE_PROVIDER' => $worker,
			'SERVICE' => preg_replace('/\$(\d)/', '\\\$$1', $service),
			'DATE_TIME' => mysql2date($this->datetime_format, $datetime),
			'PRICE' => $price,
			'DEPOSIT' => $deposit,
			'BALANCE' => $balance,
			'PHONE' => $phone,
			'NOTE' => $note,
			'ADDRESS' => $address,
			'EMAIL' => $email,
			'CITY' => $city,
		);
		foreach($replacement as $macro => $repl) {
			$text = preg_replace('/\b' . preg_quote($macro, '/') . '\b/U', $repl, $text);
		}
		return $text;
	}


	/**
	 *	Email message headers
	 */
	function message_headers () {
		$admin_email = $this->get_admin_email();
		$blogname = strip_tags( wp_specialchars_decode( get_option('blogname'), ENT_QUOTES) );
		$content_type = apply_filters('app-emails-content_type', 'text/plain');
		if (!(defined('APP_EMAIL_DROP_LEGACY_HEADERS') && APP_EMAIL_DROP_LEGACY_HEADERS)) {
			$message_headers = "MIME-Version: 1.0\n" . "From: {$blogname}" .  " <{$admin_email}>\n" . "Content-Type: {$content_type}; charset=\"" . get_option('blog_charset') . "\"\n";
		} else {
			$message_headers = "MIME-Version: 1.0\n" .
				"Content-Type: {$content_type}; charset=\"" . get_option('blog_charset') . "\"\n"
			;
			add_filter('wp_mail_from', create_function('', "return '{$admin_email}';"));
			add_filter('wp_mail_from_name', create_function('', "return '{$blogname}';"));
		}
		// Modify message headers
		$message_headers = apply_filters( 'app_message_headers', $message_headers );

		return $message_headers;
	}

	/**
	 *	Remove an appointment if not paid or expired
	 *	Clear expired appointments.
	 *	Change status to completed if they are confirmed or paid
	 *	Change status to removed if they are pending or reserved
	 */
	function remove_appointments( ) {
		$process_expired = apply_filters('app-auto_cleanup-process_expired', true);
		if ( ! $process_expired ) {
			return;
		}

		$options = appointments_get_options();

		$clear_secs = 0;
		if ( isset( $options["clear_time"] ) && $options["clear_time"] > 0 ) {
			$clear_secs = $options["clear_time"] * 60;
		}
		
		$expireds = appointments_get_expired_appointments( $clear_secs );

		if ( $expireds && $process_expired ) {
			foreach ( $expireds as $expired ) {
				if ( 'pending' == $expired->status || 'reserved' == $expired->status ) {
					if ( 'reserved' == $expired->status ) {
						if ( 'reserved' == $expired->status && strtotime($expired->end) > current_time( 'timestamp' ) ) {
							$new_status = $expired->status; // Don't shift the GCal apps until they actually expire (end time in past)
						}
						else {
							$new_status = 'completed';
						}
					}
					else {
						// Pending
						$new_status = 'removed';
					}
				} else if ( 'confirmed' == $expired->status || 'paid' == $expired->status ) {
					$new_status = 'completed';
				} else {
					$new_status = $expired->status; // Do nothing ??
				}

				if ( appointments_update_appointment_status( $expired->ID, $new_status ) ) {
					do_action( 'app_remove_expired', $expired, $new_status );
				}
			}
		}

		update_option( "app_last_update", time() );

		// Appointment status probably changed, so clear cache.
		// Anyway it is good to clear the cache in certain intervals.
		// This can be removed for pages with very heavy visitor traffic, but little appointments
		$this->flush_cache();
	}

	

	/**
	 * Replace CANCEL placeholder with its link
	 * Removed due to security issues
	 */
	function add_cancel_link( $text, $app_id ) {
	    // Removed due to security issues
		return str_replace( 'CANCEL', '', $text );
	}

/*******************************
* Methods for Admin
********************************
*/
	

	
	// Enqueue css on settings page
	/**
	 * @deprecated since v1.4.2-BETA-2
	 */
/*
	function admin_css_settings() {
		wp_enqueue_style( 'jquery-colorpicker-css', $this->plugin_url . '/css/colorpicker.css', false, $this->version);
	}
*/
	// Enqueue css for all admin pages


	// Return datepick locale file if it exists
	// Since 1.0.6
	function datepick_localfile() {
		return false;
	}

	// Read and return local month names from datepick
	// Since 1.0.6.1
	function datepick_local_months() {
		return false;
	}


	// Read and return abbrevated local month names from datepick
	// Since 1.0.6.3
	function datepick_abb_local_months() {
		if ( !$file = $this->datepick_localfile() )
			return false;

		if ( !$file_content = @file_get_contents( appointments_plugin_dir() . $file ) )
			return false;

		$file_content = str_replace( array("\r","\n","\t"), '', $file_content );

		if ( preg_match( '/monthNamesShort:(.*?)]/s', $file_content, $matches ) ) {
			$months = str_replace( array('[',']',"'",'"'), '', $matches[1] );
			return explode( ',', $months );
		}
		return false;
	}

	/**
	 * Deletes a worker's database records in case he is deleted
	 * @since 1.0.4
	 */
	function delete_user( $ID ) {
		appointments_delete_worker( $ID );
	}

	/**
	 * Removes a worker's database records in case he is removed from that blog
	 * @param ID: user ID
	 * @param blog_id: ID of the blog that user has been removed from
	 * @since 1.2.3
	 */
	function remove_user_from_blog( $ID, $blog_id ) {
		switch_to_blog( $blog_id );
		appointments_delete_worker( $ID );
		restore_current_blog();
	}


	
	/**
	 *	Add a service markup
     *
     * @param bool $php True if this will be used in first call, false if this is js
     * @param mixed $service Service object that will be displayed (only when php is true)
     *
     * @return string
	 */
	function add_service( $php=false, $service='' ) {
		if ( $php ) {
			if ( is_object($service)) {
				$n = $service->ID;
				$name = $service->name;
				$capacity = $service->capacity;
				$price = $service->price;
			 } else {
				return '';
			}
		} else {
			$n        = "'+n+'";
			$name     = '';
			$capacity = '0';
			$price    = '';
		}

		$min_time = $this->get_min_time();

		$html = '';
		$html .= '<tr><td>';
		$html .= $n;
		$html .= '</td><td>';
		$html .= '<input style="width:100%" type="text" name="services['.$n.'][name]" value="'.stripslashes( $name ).'" />' . apply_filters('app-settings-services-service-name', '', $n);
		$html .= '</td><td>';
		$html .= '<input style="width:90%" type="text" name="services['.$n.'][capacity]" value="'.$capacity.'" />';
		$html .= '</td><td>';
		$html .= '<select name="services['.$n.'][duration]" >';
		$k_max = apply_filters( 'app_selectable_durations', min( 24, (int)(1440/$min_time) ) );
		for ( $k=1; $k<=$k_max; $k++ ) {
			if ( $php && is_object( $service ) && $k * $min_time == $service->duration )
				$html .= '<option selected="selected">'. ($k * $min_time) . '</option>';
			else
				$html .= '<option>'. ($k * $min_time) . '</option>';
		}
		$html .= '</select>';
		$html .= '</td><td>';
		$html .= '<input style="width:90%" type="text" name="services['.$n.'][price]" value="'.$price.'" />';
		$html .= '</td><td>';
		$pages = apply_filters('app-service_description_pages-get_list', array());
		if (empty($pages)) $pages = get_pages( apply_filters('app_pages_filter',array() ) );
		$html .= '<select name="services['.$n.'][page]" >';
		$html .= '<option value="0">'. __('None','appointments') .'</option>';
		foreach( $pages as $page ) {
			if ( $php )
				$title = esc_attr( $page->post_title );
			else
				$title = esc_js( $page->post_title );

			if ( $php && is_object( $service ) && $service->page == $page->ID )
				$html .= '<option value="'.$page->ID.'" selected="selected">'. $title . '</option>';
			else
				$html .= '<option value="'.$page->ID.'">'. $title . '</option>';
		}
		$html .= '</select>';
		$html .= '</td></tr>';
		return $html;
	}

	/**
	 * Add a worker markup
     *
	 * @param bool $php True if this will be used in first call, false if this is js
	 * @param string|Appointments_Worker $worker Worker object that will be displayed (only when php is true)
     *
     * @return string
	 */
	function add_worker( $php=false, $worker='' ) {
		if ( $php ) {
			if ( is_object($worker)) {
				$k = $worker->ID;
				if ( $this->is_dummy( $worker->ID ) )
					$dummy = ' checked="checked"';
				else
					$dummy = "";
				$price = $worker->price;
				$workers = wp_dropdown_users( array( 'echo'=>0, 'show'=>'user_login', 'selected' => $worker->ID, 'name'=>'workers['.$k.'][user]', 'exclude'=>apply_filters('app_filter_providers', null) ) );
			} else {
				return '';
			}
		}
		else {
			$k = "'+k+'";
			$price = '';
			$dummy = '';
			$workers =str_replace( array("\t","\n","\r"), "", str_replace( array("'", "&#039;"), array('"', "'"), wp_dropdown_users( array ( 'echo'=>0, 'show'=>'user_login', 'include'=>0, 'name'=>'workers['.$k.'][user]', 'exclude'=>apply_filters('app_filter_providers', null)) ) ) );
		}

		$html = '';
		$html .= '<tr><td>';
		$html .= $k;
		$html .= '</td><td>';
		$html .= $workers  . apply_filters('app-settings-workers-worker-name', '', (is_object($worker) ? $worker->ID : false), $worker);
		$html .= '</td><td>';
		$html .= '<input type="checkbox" name="workers['.$k.'][dummy]" '.$dummy.' />';
		$html .= '</td><td>';
		$html .= '<input type="text" name="workers['.$k.'][price]" style="width:80%" value="'.$price.'" />';
		$html .= '</td><td>';
		$services = appointments_get_services();
		if ( $services ) {
			if ( $php && is_object( $worker ) )
				$services_provided = $worker->services_provided;
			else
				$services_provided = false;
			$html .= '<select class="add_worker_multiple" style="width:280px" multiple="multiple" name="workers['.$k.'][services_provided][]" >';
			foreach ( $services as $service ) {
				if ( $php )
					$title = stripslashes( $service->name );
				else
					$title = esc_js( $service->name );

				if ( is_array( $services_provided ) && in_array( $service->ID, $services_provided ) )
					$html .= '<option value="'. $service->ID . '" selected="selected">'. $title . '</option>';
				else
					$html .= '<option value="'. $service->ID . '">'. $title . '</option>';
			}
			$html .= '</select>';
		}
		else
			$html .= __( 'No services defined', 'appointments' );
		$html .= '</td><td>';
		$pages = apply_filters('app-biography_pages-get_list', array());
		if (empty($pages)) $pages = get_pages( apply_filters('app_pages_filter',array() ) );
		$html .= '<select name="workers['.$k.'][page]" >';
		$html .= '<option value="0">'. __('None','appointments') .'</option>';
		foreach( $pages as $page ) {
			if ( $php )
				$title = esc_attr( $page->post_title );
			else
				$title = esc_js( $page->post_title );

			if ( $php && is_object( $worker ) && $worker->page == $page->ID )
				$html .= '<option value="'.$page->ID.'" selected="selected">'. $title . '</option>';
			else
				$html .= '<option value="'.$page->ID.'">'. $title . '</option>';
		}
		$html .= '</select>';
		$html .= '</td></tr>';
		return $html;
	}

	/**
	 *	Create a working hour form
	 *  Worker can be forced.
	 *  @param status: Open (working hours) or close (break hours)
	 */
	function working_hour_form( $status='open' ) {
		$path = appointments_get_view_path( 'form-working-hours' );
		if ( is_file( $path ) ) {
			include $path;
		}
	}

	/**
	 * @internal
	 * @param $name
	 * @param $min_secs
	 * @param string $selected
	 *
	 * @return string
	 */
	public function _time_selector( $name, $min_secs, $selected = '' ) {
		ob_start();
		?>
			<select name="<?php echo esc_attr( $name ); ?>" autocomplete="off">
				<?php for ( $t = 0; $t < 3600 * 24; $t = $t + $min_secs ): ?>
					<?php

						$dhours = $this->secs2hours( $t, 'H:i', false ); // Hours in 08:30 format - escape, because they're values now.
						$shours = $this->secs2hours($t);
					?>
					<option <?php selected( $selected, strtotime( $dhours ) ); ?> value="<?php echo esc_attr( $dhours ); ?>"><?php echo $shours; ?></option>
				<?php endfor; ?>
			</select>
		<?php
		return ob_get_clean();
	}


	/**
	 * Helper function for displaying appointments
	 *
	 */
	function myapps($type = 'active') {
		App_Template::admin_my_appointments_list($type);
	}

	/**
	 * Return a safe date format that datepick can use
	 * @return string
	 * @since 1.0.4.2
	 */
	function safe_date_format() {
		// Allowed characters
		$check = str_replace( array( '-', '/', ',', 'F', 'j', 'y', 'Y', 'd', 'M', 'm' ), '', $this->date_format );
		if ( '' == trim( $check ) )
			return $this->date_format;

		// Return a default safe format
		return 'F j Y';
	}

	/**
	 * Modify a date if it is non US
	 * Change d/m/y to d-m-y so that strtotime can behave correctly
	 * Also change local dates to m/d/y format
	 * @return string
     *
     * @deprecated This function is deprecated and it will dissapear in following versions but in order
     * to keep backwards compatibility, is not really marked as deprecated cause can be used in another functions
     * see appointments_insert_appointment() and appointments_update_appointment()
     *
	 * @since 1.0.4.2
	 */
	function to_us( $date ) {
		// Find the real format we are using
		$date_format = $this->safe_date_format();
		$date_arr = explode( '/', $date_format );
		if ( isset( $date_arr[0] ) && isset( $date_arr[1] ) && 'd/m' == $date_arr[0] .'/'. $date_arr[1] )
			return str_replace( '/', '-', $date );
		// Already US format
		if ( isset( $date_arr[0] ) && isset( $date_arr[1] ) && 'm/d' == $date_arr[0] .'/'. $date_arr[1] )
			return $date;

		global $wp_locale;
		if ( !is_object( $wp_locale )  || empty( $wp_locale->month ) ) {
			$this->locale_error = true;
			return $date;
		}

		$datepick_local_months = $this->datepick_local_months();
		$datepick_abb_local_months = $this->datepick_abb_local_months();

		$months = array( 'January'=>'01','February'=>'02','March'=>'03','April'=>'04','May'=>'05','June'=>'06',
						'July'=>'07','August'=>'08','September'=>'09','October'=>'10','November'=>'11','December'=>'12' );
		// A special check where locale is set, but language files are not loaded
		if ( strpos( $date_format, 'F' ) !== false || strpos( $date_format, 'M' ) !== false ) {
			$n = 0;
			$k = 0;
			foreach ( $months as $month_name => $month_no ) {
				$month_name_local = $wp_locale->get_month($month_no);
				$month_name_abb_local = $wp_locale->get_month_abbrev($month_name_local);
				if ( $month_name_local == $month_name )
					$n++;
				// Also check if any month will give a 1970 result
				if ( '1970-01-01' == date( 'Y-m-d', strtotime( $month_name . ' 1 2012' ) ) ) {
					$this->locale_error = true;
					return $date;
				}
				// Also check translation of datepick
				if ( strpos( $date_format, 'F' ) !== false ) {
					if ( $month_name_local != trim( $datepick_local_months[$k] ) ) {
						$this->locale_error = true;
						return $date;
					}
				}
				if ( strpos( $date_format, 'M' ) !== false ) {
					// Also check translation of datepick for short month names
					if ( $month_name_abb_local != trim( $datepick_abb_local_months[$k] ) ) {
						$this->locale_error = true;
						return $date;
					}
				}
				$k++;
			}
			if ( $n > 11 ) {
				// This means we shall use English
				$this->locale_error = true;
				return $date;
			}
		}

		// Check if F (long month name) is set
		if ( strpos( $date_format, 'F' ) !== false ) {
			foreach ( $months as $month_name => $month_no ) {
				$month_name_local = $wp_locale->get_month($month_no);
				if ( strpos( $date, $month_name_local ) !== false )
					return date( 'm/d/y', strtotime( str_replace( $month_name_local, $month_name, $date ) ) );
			}
		}

		if ( strpos( $date_format, 'M' ) !== false ) {
			// Check if M (short month name) is set
			foreach ( $months as $month_name => $month_no ) {
				$month_name_local = $wp_locale->get_month($month_no);
				$month_name_abb_local = $wp_locale->get_month_abbrev($month_name_local);
				if ( strpos( $date, $month_name_abb_local ) !== false )
					return date( 'm/d/y', strtotime( str_replace( $month_name_abb_local, $month_name, $date ) ) );
			}
		}

		$this->locale_error = true;
		return $date;
	}

	

	

	 // For future use
	function reports() {
	}

	/**
	 *	Get transaction records
	 *  Modified from Membership plugin by Barry
	 *
	 * @deprecated since 2.0
	 */
	function get_transactions($type, $startat, $num) {
		_deprecated_function( __FUNCTION__, '2.0', 'appointments_get_transactions()' );
		$args = array(
			'type' => $type,
			'offset' => $startat,
			'per_page' => $num
		);
		return appointments_get_transactions( $args );
	}

	/**
	 * Find if a Paypal transaction is duplicate or not
	 *
	 * @deprecated since 2.0
	 */
	function duplicate_transaction($app_id, $amount, $currency, $timestamp, $paypal_ID, $status, $note,$content=0) {
		_deprecated_function( __FUNCTION__, '2.0', 'appointments_is_transaction_duplicated()' );
		return appointments_is_transaction_duplicated( $app_id, $timestamp, $paypal_ID );
	}

	/**
	 * Save a Paypal transaction to the database
	 *
	 * @deprecated since 2.0
	 */
	function record_transaction($app_id, $amount, $currency, $timestamp, $paypal_id, $status, $note) {
		_deprecated_function( __FUNCTION__, '2.0', 'appointments_update_transaction() or appointments_insert_transaction()' );
		$args = array(
			'app_ID' => $app_id,
			'paypal_ID' => $paypal_id,
			'stamp' => $timestamp,
			'currency' => $currency,
			'status' => $status,
			'total_amount' => (int) round($amount * 100),
			'note' => $note
		);

		if ( $transaction = appointments_get_transaction_by_paypal_id( $paypal_id ) ) {
			// Update
			appointments_update_transaction( $transaction->transaction_ID, $args );
		}
		else {
			// Insert
			appointments_insert_transaction( $args );
		}
	}

	/**
	 * @deprecated since 2.0
	 */
	function get_total() {
		_deprecated_function( __FUNCTION__, '2.0' );
		//return $this->db->get_var( "SELECT FOUND_ROWS();" );
	}


	function mytransactions ($type = 'past') {
		_deprecated_function( __FUNCTION__, '2.0' );
		//App_Template::admin_my_transactions_list($type);
	}

	function reached_ceiling () {
		return false;
	}

}
}

define('APP_PLUGIN_DIR', dirname(__FILE__));
define('APP_ADMIN_PLUGIN_DIR', trailingslashit( dirname(__FILE__) ) . 'admin');
define('APP_PLUGIN_FILE', __FILE__);

require_once APP_PLUGIN_DIR . '/includes/default_filters.php';
require_once APP_PLUGIN_DIR . '/includes/class_app_install.php';
require_once APP_PLUGIN_DIR . '/includes/class_app_timed_abstractions.php';
require_once APP_PLUGIN_DIR . '/includes/class_app_roles.php';
require_once APP_PLUGIN_DIR . '/includes/class_app_codec.php';
require_once APP_PLUGIN_DIR . '/includes/shortcodes/abstract-app-shortcode.php';
require_once APP_PLUGIN_DIR . '/includes/shortcodes.php';

App_Installer::serve();

App_Shortcodes::serve();

global $appointments;
$appointments = new Appointments();

// Load addons
include_once( 'includes/class-app-addon.php' );
include_once( 'includes/class-app-addons-loader.php' );
if ( ! defined( 'APP_PLUGIN_ADDONS_DIR' ) ) {
	define('APP_PLUGIN_ADDONS_DIR', APP_PLUGIN_DIR . '/includes/addons');
}
$appointments->addons_loader = Appointments_Addons_Loader::get_instance();
$appointments->addons_loader->load_active_addons();


if (is_admin()) {
	require_once APP_PLUGIN_DIR . '/includes/class-app-tutorial.php';
	App_Tutorial::serve();

	require_once APP_PLUGIN_DIR . '/includes/support/class_app_admin_help.php';
	App_AdminHelp::serve();

	// Setup dashboard notices
	if (file_exists(APP_PLUGIN_DIR . '/includes/external/wpmudev-dash/wpmudev-dash-notification.php')) {
		global $wpmudev_notices;
		if (!is_array($wpmudev_notices)) $wpmudev_notices = array();
		$wpmudev_notices[] = array(
			'id' => 679841,
			'name' => 'Appointments+',
			'screens' => array(
				'appointments_page_app_settings',
				'appointments_page_app_shortcodes',
				'appointments_page_app_faq',
			),
		);
		require_once APP_PLUGIN_DIR . '/includes/external/wpmudev-dash/wpmudev-dash-notification.php';
	}
	// End dash bootstrap
}

/**
 * Find blogs and uninstall tables for each of them
 * @since 1.0.2
 * @until 1.4.1
 */
if ( !function_exists( 'wpmudev_appointments_uninstall' ) ) {
	function wpmudev_appointments_uninstall () { do_action('app-core-doing_it_wrong', __FUNCTION__); }
}

if ( !function_exists( '_wpmudev_appointments_uninstall' ) ) {
	function _wpmudev_appointments_uninstall () { do_action('app-core-doing_it_wrong', __FUNCTION__); }
	function wpmudev_appointments_rmdir ($dir) { do_action('app-core-doing_it_wrong', __FUNCTION__); }
}

function appointments_activate() {
	$installer = new App_Installer();
	$installer->install();
}

function appointments_uninstall() {
	App_Installer::uninstall();
}

function appointments_plugin_url() {
	global $appointments;
	return trailingslashit( $appointments->plugin_url );
}

function appointments_plugin_dir() {
	return trailingslashit( plugin_dir_path( __FILE__ ) );
}

function appointments() {
	global $appointments;
	return $appointments;
}

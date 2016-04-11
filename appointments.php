<?php
/*
Plugin Name: Appointments+
Description: Lets you accept appointments from front end and manage or create them from admin side
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.7.2.2
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

	public $version = "1.7.2.2";
	public $db_version;

	public $timetables = array();

	public $local_time;
	public $wh_table;
	public $exceptions_table;
	public $app_table;
	public $workers_table;
	/** @var bool|Appointments_Google_Calendar  */
	public $gcal_api = false;
	public $locale_error;
	public $time_format;
	public $datetime_format;
	public $log_file;
	public $salt;
	public $plugin_dir;
	public $worker;
	public $location;
	public $service;
	public $openid;
	public $plugin_url;
	/** @var Appointments_Admin  */
	public $admin;

	/** @var Appointments_Notifications_Manager */
	public $notifications;

	function __construct() {

		include_once( 'includes/helpers.php' );
		include_once( 'includes/helpers-settings.php' );
		include_once( 'includes/deprecated-hooks.php' );
		include_once( 'includes/class-app-notifications-manager.php' );

		$this->timetables = get_transient( 'app_timetables' );
		if ( ! $this->timetables || ! is_array( $this->timetables ) ) {
			$this->timetables = array();
		}

		$this->plugin_dir = plugin_dir_path(__FILE__);
		$this->plugin_url = plugins_url(basename(dirname(__FILE__)));

		// Read all options at once
		$this->options = get_option( 'appointments_options' );

		// To follow WP Start of week, time, date settings
		$this->local_time = current_time('timestamp');
		if ( !$this->start_of_week = get_option('start_of_week') ) $this->start_of_week = 0;

		$this->time_format = get_option('time_format');
		if (empty($this->time_format)) $this->time_format = "H:i";

		$this->date_format = get_option('date_format');
		if (empty($this->date_format)) $this->date_format = "Y-m-d";

		$this->datetime_format = $this->date_format . " " . $this->time_format;

		add_action( 'delete_user', 'appointments_delete_worker' );		// Modify database in case a user is deleted
		add_action( 'wpmu_delete_user', 'appointments_delete_worker' );	// Same as above
		add_action( 'remove_user_from_blog', array( &$this, 'remove_user_from_blog' ), 10, 2 );	// Remove his records only for that blog

		add_action( 'plugins_loaded', array(&$this, 'localization') );		// Localize the plugin
		add_action( 'init', array( &$this, 'init' ), 20 ); 						// Initial stuff
		add_action( 'init', array( &$this, 'cancel' ), 19 ); 				// Check cancellation of an appointment
		add_filter( 'the_posts', array(&$this, 'load_styles') );			// Determine if we use shortcodes on the page

		add_action( 'admin_init', array( $this, 'maybe_upgrade' ) );

		include_once( 'includes/class-app-service.php' );
		include_once( 'includes/class-app-worker.php' );
		include_once( 'includes/class-app-appointment.php' );

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
		require_once( $this->plugin_dir . '/includes/widgets.php' );
		add_action( 'widgets_init', array( &$this, 'widgets_init' ) );

		// Buddypress
		require_once($this->plugin_dir . '/includes/class_app_buddypress.php');
		if (class_exists('App_BuddyPress')) App_BuddyPress::serve();

		// Membership2 Integration
		$m2_integration = $this->plugin_dir . '/includes/class_app_membership2.php';
		if ( file_exists( $m2_integration ) ) {
			require_once $m2_integration;
		}

		// Caching
		if ( 'yes' == @$this->options['use_cache'] ) {
			add_filter( 'the_content', array( &$this, 'pre_content' ), 8 );				// Check content before do_shortcode
			add_filter( 'the_content', array( &$this, 'post_content' ), 100 );			// Serve this later than do_shortcode
			add_action( 'wp_footer', array( &$this, 'save_script' ), 8 );				// Save script to database
			add_action( 'permalink_structure_changed', array( &$this, 'flush_cache' ) );// Clear cache in case permalink changed
			add_action( 'save_post', array( &$this, 'save_post' ), 10, 2 ); 			// Clear cache if it has shortcodes
		}
		$this->pages_to_be_cached = array();
		$this->had_filter = false; // There can be a wpautop filter. We will check this later on.

		// Membership integration
		$this->membership_active = false;
		add_action( 'plugins_loaded', array( &$this, 'check_membership_plugin') );

		// Marketpress integration
		$this->marketpress_active = $this->mp = false;
		$this->mp_posts = array();
		add_action( 'plugins_loaded', array( &$this, 'check_marketpress_plugin') );

		add_action('init', array($this, 'get_gcal_api'), 10);

		// Database variables
		global $wpdb;
		$this->db 					= &$wpdb;
		$this->wh_table 			= $wpdb->prefix . "app_working_hours";
		$this->exceptions_table 	= $wpdb->prefix . "app_exceptions";
		$this->services_table 		= $wpdb->prefix . "app_services";
		$this->workers_table 		= $wpdb->prefix . "app_workers";
		$this->app_table 			= $wpdb->prefix . "app_appointments";
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

		appointments_clear_cache();

		include_once( 'includes/class-app-upgrader.php' );

		$upgrader = new Appointments_Upgrader( $this->version );
		$upgrader->upgrade( $db_version, $this->version );
	}


	function get_gcal_api() {
		if ( false === $this->gcal_api && ! defined( 'APP_GCAL_DISABLE' ) ) {
			require_once $this->plugin_dir . '/includes/class-app-gcal.php';
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
	 * @param order_by: ORDER BY clause for mysql
	 * @return array of objects
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
		$work_break = null;
		$cache_key = 'appointments_work_breaks-' . $l . '-' . $w;
		$work_breaks = wp_cache_get( $cache_key );
		if ( false === $work_breaks ) {
			$work_breaks = $this->db->get_results( $this->db->prepare("SELECT * FROM {$this->wh_table} WHERE worker=%d AND location=%d", $w, $l) );
			wp_cache_set( $cache_key, $work_breaks );
		}

		if ( $work_breaks ) {
			foreach ( $work_breaks as $wb ) {
				if ( $wb->status == $stat ) {
					$work_break = $wb;
					break;
				}
			}
		}
		return $work_break;
	}

	/**
	 * Return a row from exceptions table, i.e. days we are working or having holiday
	 * @return object
	 */
	function get_exception( $l, $w, $stat ) {
		$exception = null;
		$exceptions = wp_cache_get( 'exceptions_'. $l . '_' . $w );
		if ( false === $exceptions ) {
			$exceptions = $this->db->get_results( $this->db->prepare("SELECT * FROM {$this->exceptions_table} WHERE worker=%d AND location=%d", $w, $l) );
			wp_cache_set( 'exceptions_'. $l . '_' . $w, $exceptions );
		}
		if ( $exceptions ) {
			foreach ( $exceptions as $e ) {
				if ( $e->status == $stat ) {
					$exception = $e;
					break;
				}
			}
		}
		return $exception;
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
		_deprecated_function( __FUNCTION__, '1.6', 'appointments_get_appointments()' );
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
	 * Only for Unit Testing purposes, do not use
	 */
	function _old_get_worker_name( $worker=0, $php = true ) {
		global $current_user;
		$user_name = '';
		if ( 0 == $worker ) {
			// Show different text to authorized people
			if ( is_admin() || App_Roles::current_user_can( 'manage_options', App_Roles::CTX_STAFF ) || appointments_is_worker( $current_user->ID ) )
				$user_name = __('Our staff', 'appointments');
			else
				$user_name = __('A specialist', 'appointments');
		}
		else {
			$userdata = get_userdata( $worker );
			if (is_object($userdata) && !empty($userdata->app_name)) {
				$user_name = $userdata->app_name;
			}
			if (empty($user_name)) {
				if ( !$php ) {
					$user_name = $userdata->user_login;
				}
				else {
					$user_name = $userdata->display_name;
				}

				if ( !$user_name ){
					$first_name = get_user_meta($worker, 'first_name', true);
					$last_name = get_user_meta($worker, 'last_name', true);
					$user_name = $first_name . " " . $last_name;
				}
				if ( "" == trim( $user_name ) )
					$user_name = $userdata->user_login;
			}
		}
		return apply_filters( 'app_get_worker_name', $user_name, $worker );
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

		$price = $service_obj->price + $worker_price;

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
	 * Check if plugin should use cache
	 * Available for visitors for the moment
	 * TODO: extend this for logged in users too
	 * @since 1.0.2
	 */
	function use_cache() {
		if ( 'yes' == $this->options["use_cache"] && !is_user_logged_in() )
			return true;

		return false;
	}

	/**
	 * Add a post ID to the array to be cached
	 *
	 */
	function add_to_cache( $post_id ) {
		if ( $this->use_cache() )
			$this->pages_to_be_cached[] = $post_id;
	}

	/**
	 * Serve content from cache DB if is available and post is supposed to be cached
	 * This is called before do_shortcode (this method's priority: 8)
	 * @return string (the content)
	 */
	function pre_content( $content ) {
		global $post;
		// Check if this page is to be cached
		if ( !in_array( $post->ID, $this->pages_to_be_cached ) )
			return $content;

		// Get uri and mark it for other functions too
		// The other functions are called after this (content with priority 100 and the other with footer hook)
		$this->uri = $this->get_uri();

		$result = $this->db->get_row( $this->db->prepare("SELECT * FROM {$this->cache_table} WHERE uri=%s", $this->uri) );
		if ( $result != null ) {
			// Clear uri so other functions do not deal with update/insert
			$this->uri = false;
			// We need to serve the scripts too
			$this->script = $result->script;

			// If wpautop had filter, it is almost certain that it was removed
			if ( $this->had_filter )
				$new_content = $result->content;
			else
				$new_content = wpautop( $result->content );

			return $new_content . '<!-- Served from WPMU DEV Appointments+ Cache '. $result->created .' -->';
		}
		// If cache is empty return content
		// If wpautop had filter, it is almost certain that it was removed
		if ( $this->had_filter )
			return $content;
		else
			return wpautop( $content ); // Add wpautop which we removed before
	}

	/**
	 * Save newly created content to cache DB
	 * @return string (the content)
	 */
	function post_content( $content ) {
		// Check if this page is to be cached.
		if ( !$this->uri )
			return $content;
		// Also don't save empty content
		if ( !trim( $content ) ) {
			$this->uri = '';
			return $content;
		}
		// At this point it means there is no such a row, so we can safely insert
		$this->db->insert( $this->cache_table,
					array(
						'uri' 		=> $this->uri,
						'created' 	=> date ("Y-m-d H:i:s", $this->local_time ),
						'content'	=> $content
					)
			);
		return $content;
	}

	/**
	 * Save newly created scripts at wp footer location
	 * @return none
	 */
	function save_script() {
		// Check if this page is to be cached
		if ( !$this->uri || !trim( $this->script ) )
			return;
		// There must be already such a row
		$this->db->update( $this->cache_table,
			array( 'script'	=> $this->script ),
			array( 'uri' 	=> $this->uri )
		);
	}

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
	 * Clear cache in case saved post has our shortcodes
	 * @return none
	 */
	function save_post( $post_id, $post ) {
		if ( strpos( $post->post_content, '[app_' ) !== false )
			$this->flush_cache();
	}

	/**
	 * Flush both database and object caches
	 *
	 */
	function flush_cache( ) {
		wp_cache_flush();
		appointments_clear_cache();
		if ( 'yes' == @$this->options["use_cache"] )
			$result = $this->db->query( "TRUNCATE TABLE {$this->cache_table} " );
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
	 * @param integer secs Seconds
	 * @param string $forced_format Forcing the return timestamp format
	 * @return string
	 */
	function secs2hours( $secs, $forced_format=false ) {
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
		if (!empty($forced_format)) $hours_min = date_i18n($forced_format, strtotime($hours_min . ":00"));
		else if ($this->time_format) $hours_min = date_i18n($this->time_format, strtotime($hours_min . ":00")); // @TODO: TEST THIS THOROUGHLY!!!!

		return $hours_min;
	}

	/**
	 * Return an array of preset base times, so that strange values are not set
	 * @return array
	 */
	function time_base() {
		$default = array( 10,15,30,60,90,120 );
		$a = $this->options["additional_min_time"];
		// Additional time bases
		if ( isset( $a ) && $a && is_numeric( $a ) )
			$default[] = $a;
		return apply_filters( 'app_time_base', $default );
	}

	/**
	 *	Return minimum set interval time
	 *  If not set, return a safe time.
	 *	@return integer
	 */
	function get_min_time(){
		if ( isset( $this->options["min_time"] ) && $this->options["min_time"] && $this->options["min_time"]>apply_filters( 'app_safe_min_time', 9 ) )
			return apply_filters('app-time-min_time', (int)$this->options["min_time"]);
		else
			return apply_filters('app-time-min_time', apply_filters( 'app_safe_time', 10 ));
	}

	/**
	 *	Number of days that an appointment can be taken
	 *	@return integer
	 */
	function get_app_limit() {
		if ( isset( $this->options["app_limit"] ) && $this->options["app_limit"] )
			return apply_filters( 'app_limit', (int)$this->options["app_limit"] );
		else
			return apply_filters( 'app_limit', 365 );
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

		if ( array_key_exists( $key, $field_names ) )
			return $field_names[$key];
		else
			return __( 'Not defined', 'appointments' );
	}

	/**
	 * Return an array of all available front end box classes
	 * @return array
	 */
	function get_classes() {
		return apply_filters( 'app_box_class_names',
							array(
								'free'			=> __('Free', 'appointments'),
								'busy'			=> __('Busy', 'appointments'),
								'notpossible'	=> __('Not possible', 'appointments')
								)
				);
	}

	/**
	 * Return a default color for a selected box class
	 * @return string
	 */
	function get_preset( $class, $set ) {
		if ( 1 == $set )
			switch ( $class ) {
				case 'free'			:	return '48c048'; break;
				case 'busy'			:	return 'ffffff'; break;
				case 'notpossible'	:	return 'ffffff'; break;
				default				:	return '111111'; break;
			}
		else if ( 2 == $set )
			switch ( $class ) {
				case 'free'			:	return '73ac39'; break;
				case 'busy'			:	return '616b6b'; break;
				case 'notpossible'	:	return '8f99a3'; break;
				default				:	return '111111'; break;
			}
		else if ( 3 == $set )
			switch ( $class ) {
				case 'free'			:	return '40BF40'; break;
				case 'busy'			:	return '454C54'; break;
				case 'notpossible'	:	return '454C54'; break;
				default				:	return '111111'; break;
			}
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

	/**
	 * Handle cancellation of an appointment by the client
	 * @since 1.2.6
	 */
	function cancel() {
		global $appointments;

		if ( isset( $this->options['allow_cancel'] ) && 'yes' == $this->options['allow_cancel'] ) {

			/* Cancel by the link in email */
			// We don't want to break any other plugin's init, so these conditions are very strict
			if ( isset( $_GET['app_cancel'] ) && isset( $_GET['app_id'] ) && isset( $_GET['app_nonce'] ) ) {
				$app_id = $_GET['app_id'];
				$app = appointments_get_appointment( $app_id );

				if( isset( $app->status ) )
					$stat = $app->status;
				else
					$stat = '';

				// Addons may want to add or omit some stats, but as default we don't want completed appointments to be cancelled
				$in_allowed_stat = apply_filters( 'app_cancel_allowed_status', ('pending' == $stat || 'confirmed' == $stat || 'paid' == $stat), $stat, $app_id );

				// Also the clicked link may belong to a formerly created and deleted appointment.
				// Another irrelevant app may have been created after cancel link has been sent. So we will check creation date
				if ( $in_allowed_stat && $_GET['app_nonce'] == md5( $_GET['app_id']. $appointments->salt . strtotime( $app->created ) ) ) {
					if ( appointments_update_appointment_status( $app_id, 'removed' ) ) {
						$appointments->log( sprintf( __('Client %s cancelled appointment with ID: %s','appointments'), $appointments->get_client_name( $app_id ), $app_id ) );
						appointments_send_cancel_notification( $app_id );

						do_action('app-appointments-appointment_cancelled', $app_id);
						// If there is a header warning other plugins can do whatever they need
						if ( !headers_sent() ) {
							if ( isset( $appointments->options['cancel_page'] ) &&  $appointments->options['cancel_page'] ) {
								wp_redirect( get_permalink( $appointments->options['cancel_page'] ) );
								exit;
							}
							else {
								wp_redirect( home_url() );
								exit;
							}
						}
					}
					// Gracefully go to home page if appointment has already been cancelled, or do something here
					do_action( 'app_cancel_failed', $app_id );
				}
			}

			/* Cancel from my appointments table by ajax */
			if ( isset( $_POST['app_id'] ) && isset( $_POST['cancel_nonce'] ) ) {
				$app_id = $_POST['app_id'];

				// Check if user is the real owner of this appointment to prevent malicious attempts
				$owner = false;
				// First try to find from database
				if ( is_user_logged_in() ) {
					global $current_user;
					$app = appointments_get_appointment( $app_id );
					if ( $app->user && $app->user == $current_user->ID )
						$owner = true;
				}
				// Then check cookie. Check is not so strict here, as he couldn't be seeing that cancel checkbox in the first place
				if ( !$owner && isset( $_COOKIE["wpmudev_appointments"] ) ) {
					$apps = unserialize( stripslashes( $_COOKIE["wpmudev_appointments"] ) );
					if ( is_array( $apps ) && in_array( $app_id, $apps ) )
						$owner = true;
				}
				// Addons may want to do something here
				$owner = apply_filters( 'app_cancellation_owner', $owner, $app_id );

				// He is the wrong guy, or he may have cleared his cookies while he is on the page
				if ( !$owner )
					die( json_encode( array('error'=>esc_js(__('There is an issue with this appointment. Please refresh the page and try again. If problem persists, please contact website admin.','appointments') ) ) ) );

				// Now we can safely continue for cancel
				if ( appointments_update_appointment_status( $app_id, 'removed' ) ) {
					$appointments->log( sprintf( __('Client %s cancelled appointment with ID: %s','appointments'), $appointments->get_client_name( $app_id ), $app_id ) );
					appointments_send_cancel_notification( $app_id );

					do_action('app-appointments-appointment_cancelled', $app_id);
					die( json_encode( array('success'=>1)));
				}
				else
					die( json_encode( array('error'=>esc_js(__('Appointment could not be cancelled. Please refresh the page and try again.','appointments') ) ) ) );
			}
		}
		else if ( isset( $_POST['app_id'] ) && isset( $_POST['cancel_nonce'] ) )
			die( json_encode( array('error'=>esc_js(__('Cancellation of appointments is disabled. Please contact website admin.','appointments') ) ) ) );
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

		$text = $page->post_content;

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


		$app_content = apply_filters( 'app_pre_content', wpautop( $this->strip_app_shortcodes( $page->post_content ), $page_id, $worker_id ) );

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
	 * @return json object
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

		if (defined('APP_USE_LEGACY_DURATION_CALCULUS') && APP_USE_LEGACY_DURATION_CALCULUS) {
			$step = $min_step_time; // Timestamp increase interval to one cell ahead
		} else {
			$service = appointments_get_service($this->service);
			$step = (!empty($service->duration) ? $service->duration : $min_step_time) * 60; // Timestamp increase interval to one cell ahead
		}

		if (!(defined('APP_USE_LEGACY_DURATION_CALCULUS') && APP_USE_LEGACY_DURATION_CALCULUS)) {
			$start_result = $this->get_work_break( $this->location, $this->worker, 'open' );
			if (!empty($start_result->hours)) $start_unpacked_days = maybe_unserialize($start_result->hours);
		} else $start_unpacked_days = array();
		if (defined('APP_BREAK_TIMES_PADDING_CALCULUS') && APP_BREAK_TIMES_PADDING_CALCULUS) {
			$break_result = $this->get_work_break($this->location, $this->worker, 'closed');
			if (!empty($break_result->hours)) $break_times = maybe_unserialize($break_result->hours);
		} else $break_times = array();

		// Allow direct step increment manipulation,
		// mainly for service duration based calculus start/stop times
		$step = apply_filters('app-timetable-step_increment', $step);

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
				if (!empty($start_unpacked_days) && !(defined('APP_USE_LEGACY_DURATION_CALCULUS') && APP_USE_LEGACY_DURATION_CALCULUS)) {
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
				if (!empty($break_times[$this_day_key]['active']) && defined('APP_BREAK_TIMES_PADDING_CALCULUS') && APP_BREAK_TIMES_PADDING_CALCULUS) {
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
		if ( !$long )
			$day_names_array = $this->arrange( $this->get_short_day_names(), false );
		else
			$day_names_array = $this->arrange( $this->get_day_names(), false );
		$cells = '<th>' . join('</th><th>', $day_names_array) . '</th>';
		return "<{$which}><tr>{$cells}</tr></{$which}>";
	}

	/**
	 * Helper function to create a weekly schedule
	 */
	function get_weekly_calendar( $timestamp=false, $class='', $long ) {
		global $wpdb;

		$this->get_lsw();

		$price = $this->get_price( );

		$year = date("Y", $this->local_time);
		$month = date("m",  $this->local_time);

		$date = $timestamp ? $timestamp : $this->local_time;

		$sunday = $this->sunday( $date ); // Timestamp of first Sunday of any date

		$start = $end = 0;
		if ( $min_max = $this->min_max_wh( 0, 0 ) ) {
			$start = $min_max["min"];
			$end = $min_max["max"];
		}
		if ( $start >= $end ) {
			$start = 8;
			$end = 18;
		}
		$start = apply_filters( 'app_schedule_starting_hour', $start, $date, 'week' );
		$end = apply_filters( 'app_schedule_ending_hour', $end, $date, 'week' );

		$first = $start *3600 + $sunday; // Timestamp of the first cell of first Sunday
		$last = $end *3600 + $sunday; // Timestamp of the last cell of first Sunday
		$schedule_key = sprintf("%sx%s", $date, $date+(7*86400));

		if (defined('APP_USE_LEGACY_DURATION_CALCULUS') && APP_USE_LEGACY_DURATION_CALCULUS) {
			$step = $this->get_min_time() * 60; // Timestamp increase interval to one cell below
		} else {
			$service = appointments_get_service($this->service);
			$step = (!empty($service->duration) ? $service->duration : $this->get_min_time()) * 60; // Timestamp increase interval to one cell below
		}

		$days = $this->arrange( array(0,1,2,3,4,5,6), -1, true ); // Arrange days acc. to start of week

		$tbl_class = $class;
		$tbl_class = $tbl_class ? "class='{$tbl_class}'" : '';

		$ret = '';
		$ret .= '<a name="app_schedule">&nbsp;</a>';
		$ret = apply_filters( 'app_schedule_before_table', $ret );
		$ret .= "<table width='100%' {$tbl_class}>";
		$ret .= $this->_get_table_meta_row('thead', $long);
		$ret .= '<tbody>';

		$ret = apply_filters( 'app_schedule_before_first_row', $ret );

		$todays_no = date("w", $this->local_time ); // Number of today
		$working_days = $this->get_working_days( $this->worker, $this->location ); // Get an array of working days
		$capacity = $this->get_capacity();

		// Allow direct step increment manipulation,
		// mainly for service duration based calculus start/stop times
		$step = apply_filters('app-timetable-step_increment', $step);

		for ( $t=$first; $t<$last; $t=$t+$step ) {
			foreach ( $days as $key=>$i ) {
				if ( $i == -1 ) {
					$from = apply_filters( 'app_weekly_calendar_from', $this->secs2hours( $t - $sunday ), $t );
					$to = apply_filters( 'app_weekly_calendar_to', $this->secs2hours( $t - $sunday + $step ), $t );
					$ret .= "<td class='appointments-weekly-calendar-hours-mins'>".$from." &#45; ".$to."</td>";
				}
				else {
					$ccs = apply_filters('app_ccs', $t + $i * 86400); // Current cell starts
					$cce = apply_filters('app_cce', $ccs + $step); // Current cell ends

					$class_name = '';
					$is_busy = $this->is_busy( $ccs, $cce, $capacity );
					$title = apply_filters('app-schedule_cell-title', date_i18n($this->datetime_format, $ccs), $is_busy, $ccs, $cce, $schedule_key);

					// Also mark now
					if ( $this->local_time > $ccs && $this->local_time < $cce )
						$class_name = 'notpossible now';
					// Mark passed hours
					else if ( $this->local_time > $ccs )
						$class_name = 'notpossible app_past';
					// Then check if this time is blocked
					else if ( isset( $this->options["app_lower_limit"] ) && $this->options["app_lower_limit"]
						&&( $this->local_time + $this->options["app_lower_limit"] * 3600) > $cce )
						$class_name = 'notpossible app_blocked';
					// Check today is holiday
					else if ( $this->is_holiday( $ccs, $cce ) )
						$class_name = 'notpossible app_holiday';
					// Check if we are working today
					else if ( !in_array( date("l", $ccs ), $working_days ) && !$this->is_exceptional_working_day( $ccs, $cce ) )
						$class_name = 'notpossible notworking';
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
					else
						$class_name = 'free';

					$class_name = apply_filters( 'app_class_name', $class_name, $ccs, $cce );

					$ret .= '<td class="'.$class_name.'" title="'.esc_attr($title).'">
					<input type="hidden" class="appointments_take_appointment" value="'.$this->pack( $ccs, $cce ).'" /></td>';
				}
			}
			$ret .= '</tr><tr>'; // Close the last day of the week
		}
		$ret = apply_filters( 'app_schedule_after_last_row', $ret );
		$ret .= '</tbody>';
		$ret .= $this->_get_table_meta_row('tfoot', $long);
		$ret .= '</table>';
		$ret = apply_filters( 'app_schedule_after_table', $ret );

		return $ret;
	}

	function _get_table_meta_row ($which, $long) {
		if ( !$long )
			$day_names_array = $this->arrange( $this->get_short_day_names(), __(' ', 'appointments') );
		else
			$day_names_array = $this->arrange( $this->get_day_names(), __(' ', 'appointments') );
		$cells = '<th class="hourmin_column">&nbsp;' . join('</th><th>', $day_names_array) . '</th>';
		return "<{$which}><tr>{$cells}</tr></{$which}>";
	}

	function get_day_names () {
		return array(
			__('Sunday', 'appointments'),
			__('Monday', 'appointments'),
			__('Tuesday', 'appointments'),
			__('Wednesday', 'appointments'),
			__('Thursday', 'appointments'),
			__('Friday', 'appointments'),
			__('Saturday', 'appointments'),
		);
	}

	function get_short_day_names () {
		return array(
			__('Su', 'appointments'),
			__('Mo', 'appointments'),
			__('Tu', 'appointments'),
			__('We', 'appointments'),
			__('Th', 'appointments'),
			__('Fr', 'appointments'),
			__('Sa', 'appointments'),
		);
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
		global $wpdb;
		$working_days = array();
		$result = $this->get_work_break( $location, $worker, 'open' );
		if ( $result !== null ) {
			$days = maybe_unserialize( $result->hours );
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
		$result = $this->get_exception( $this->location, $w, 'open' );
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
		if ( !$w )
			$w = $this->worker;
		$is_holiday = false;
		$result = $this->get_exception( $this->location, $w, 'closed' );
		if ( $result != null  && strpos( $result->days, date( 'Y-m-d', $ccs ) ) !== false )
			$is_holiday = true;

		return apply_filters( 'app_is_holiday', $is_holiday, $ccs, $cce, $this->service, $w );
	}

	/**
	 * Check if it is break time
	 * Optionally a worker is selectable ( $w != 0 )
	 * @return bool
	 */
	function is_break( $ccs, $cce, $w=0 ) {
		// A worker can be forced
		if ( !$w )
			$w = $this->worker;

		// Try getting cached preprocessed hours
		$days = wp_cache_get('app-break_times-for-' . $w);
		if (!$days) {
			// Preprocess and cache workhours
			// Look where our working hour ends
			$result_days = $this->get_work_break($this->location, $w, 'closed');
			if ($result_days && is_object($result_days) && !empty($result_days->hours)) $days = maybe_unserialize($result_days->hours);
			if ($days) wp_cache_set('app-break_times-for-' . $w, $days);
		}
		if (!is_array($days) || empty($days)) return false;

		// What is the name of this day?
		$this_days_name = date("l", $ccs );
		// This days midnight
		$this_day = date("d F Y", $ccs );

		foreach( $days as $day_name=>$day ) {
			if ( $day_name == $this_days_name && isset( $day["active"] ) && 'yes' == $day["active"] ) {
				$end = $this->to_military( $day["end"] );
				// Special case: End is 00:00
				if ( '00:00' == $end )
					$end = '24:00';
				if ( $ccs >= strtotime( $this_day. " ". $this->to_military( $day["start"] ), $this->local_time ) &&
					$cce <= $this->str2time( $this_day, $end ) ) {
					return true;
				}
			} else if ($day_name == $this_days_name && isset($day["active"]) && is_array($day["active"])) {
				foreach ($day["active"] as $idx => $active) {
					$end = $this->to_military( $day["end"][$idx] );
					// Special case: End is 00:00
					if ('00:00' == $end) $end = '24:00';

					if (
						$ccs >= strtotime( $this_day. " ". $this->to_military( $day["start"][$idx] ), $this->local_time )
						&&
						$cce <= $this->str2time( $this_day, $end )
					) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Check if a specific worker is working at this time slot
	 * @return bool
	 * @since 1.2.2
	 */
	function is_working( $ccs, $cse, $w ) {
		if ( $this->is_exceptional_working_day( $ccs, $cse, $w ) )
			return true;
		if ( $this->is_holiday( $ccs, $cse, $w ) )
			return false;
		if ( $this->is_break( $ccs, $cse, $w ) )
			return false;

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
				$result_days = $this->get_work_break($this->location, $this->worker, 'open');
				if ($result_days && is_object($result_days) && !empty($result_days->hours)) $days = maybe_unserialize($result_days->hours);
				if ($days) wp_cache_set('app-open_times-for-' . $this->worker, $days);
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

		// Dont proceed further if capacity is forced
		if ( has_filter( 'app_get_capacity' ) )
			return apply_filters( 'app_get_capacity', 1, $this->service, $this->worker );

		$n = 0;
		$workers = appointments_get_workers_by_service( $this->service );
		if (!$workers) return $this->get_capacity(); // If there are no workers for this service, apply the service capacity

		foreach( $workers as $worker ) {

			// Try getting cached preprocessed hours
			$days = wp_cache_get('app-open_times-for-' . $worker->ID);
			if (!$days) {
				// Preprocess and cache workhours
				// Look where our working hour ends
				$result_days = $this->get_work_break($this->location, $worker->ID, 'open');
				if ($result_days && is_object($result_days) && !empty($result_days->hours)) $days = maybe_unserialize($result_days->hours);
				if ($days) wp_cache_set('app-open_times-for-' . $worker->ID, $days);
			}
			if (!is_array($days) || empty($days)) continue;


			if ( is_array( $days ) ) {
				// What is the name of this day?
				$this_days_name = date("l", $ccs );
				// This days midnight
				$this_day = date("d F Y", $ccs );

				foreach( $days as $day_name=>$day ) {
					if ( $day_name == $this_days_name && isset( $day["active"] ) && 'yes' == $day["active"] ) {
						$end = $this->to_military( $day["end"] );
						// Special case: End is 00:00
						if ( '00:00' == $end )
							$end = '24:00';
						if (
							$ccs >= strtotime( $this_day. " ". $this->to_military( $day["start"] ), $this->local_time )
							&&
							$cce <= $this->str2time( $this_day, $end )
							&&
							!$this->is_break( $ccs, $cce, $worker->ID )
						) $n++;
					}
				}
			}

		}

		// We have to check service capacity too
		$service = appointments_get_service( $this->service );
		if ( $service != null ) {
			if ( !$service->capacity ) {
				$capacity = $n; // No service capacity limit
			}
			else
				$capacity = min( $service->capacity, $n ); // Return whichever smaller
		}
		else
			$capacity = 1; // No service ?? - Not possible but let's be safe

		return $capacity;
	}

	/**
	 * Check if a cell is not available, i.e. all appointments taken OR we dont have workers for this time slot
	 * @return bool
	 */
	function is_busy( $start, $end, $capacity ) {
		$week= date( "W", $start );
		$period = new App_Period($start, $end);

		// If a specific worker is selected, we will look at his schedule first.
		if ( 0 != $this->worker ) {
			$apps = $this->get_reserve_apps_by_worker( $this->location, $this->worker, $week );
			if ( $apps ) {
				foreach ( $apps as $app ) {
					//if ( $start >= strtotime( $app->start ) && $end <= strtotime( $app->end ) ) return true;
					if ($period->contains($app->start, $app->end)) return true;
				}
			}
		}

		// If we're here, no worker is set or (s)he's not busy by default. Let's go for quick filter trip.
		$is_busy = apply_filters('app-is_busy', false, $period, $capacity);
		if ($is_busy) return true;

		// If we are here, no preference is selected (provider_id=0) or selected provider is not busy. There are 2 cases here:
		// 1) There are several providers: Look for reserve apps for the workers giving this service.
		// 2) No provider defined: Look for reserve apps for worker=0, because he will carry out all services
		if ( appointments_get_all_workers() ) {
			$workers = appointments_get_workers_by_service( $this->service );
			$apps = array();
			if ( $workers ) {
				foreach( $workers as $worker ) {
					/** @var Appointments_Worker $worker **/
					if ( $this->is_working( $start, $end, $worker->ID ) ) {
						$app_worker = $this->get_reserve_apps_by_worker( $this->location, $worker->ID, $week );
						if ( $app_worker && is_array( $app_worker ) )
							$apps = array_merge( $apps, $app_worker );

						// Also include appointments by general staff for services that can be given by this worker
						$services_provided = $worker->services_provided;
						if ( $services_provided && is_array( $services_provided ) && !empty( $services_provided ) ) {
							foreach ( $services_provided as $service_ID ) {
								$args = array(
									'location' => $this->location,
									'service' => $service_ID,
									'week' => $week
								);
								$apps_service_0 = appointments_get_appointments_filtered_by_services( $args );
								if ( $apps_service_0 && is_array( $apps_service_0 ) )
									$apps = array_merge( $apps, $apps_service_0 );
							}
						}
					}
				}
				// Remove duplicates
				$apps = $this->array_unique_object_by_ID( $apps );
			}
		}
		else
			$apps = $this->get_reserve_apps_by_worker( $this->location, 0, $week );

		$n = 0;
		foreach ( $apps as $app ) {
// @FIX: this will allow for "only one service and only one provider per time slot"
if ($this->worker && $this->service && ($app->service != $this->service)) {
	continue;
	// This is for the following scenario:
	// 1) any number of providers per service
	// 2) any number of services
	// 3) only one service and only one provider per time slot:
	// 	- selecting one provider+service makes this provider and selected service unavailable in a time slot
	// 	- other providers are unaffected, other services are available
}
// End @FIX
			//if ( $start >= strtotime( $app->start ) && $end <= strtotime( $app->end ) ) $n++;
			if ($period->contains($app->start, $app->end)) $n++;
		}

		if ( $n >= $this->available_workers( $start, $end ) )
			return true;

		// Nothing found, so this time slot is not busy
		return false;
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
	 * @return array
	 */
	function min_max_wh( $worker=0, $location=0 ) {
		$this->get_lsw();
		$result = $this->get_work_break( $this->location, $this->worker, 'open' );
		if ( $result !== null ) {
			$days = maybe_unserialize( $result->hours );
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
		if (empty($this->options['login_required']) || 'yes' !== $this->options['login_required']) return false;

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
	 * Check if Membership plugin is active
	 */
	function check_membership_plugin() {
		if( ( is_admin() && class_exists('membershipadmin') ) || ( !is_admin() && class_exists('membershippublic') ) )
			$this->membership_active = true;
	}

	/**
	* Finds if user is Membership member with sufficient level
	* @return bool
	*/
	function is_member( ) {
		if ( $this->membership_active && isset( $this->options["members"] ) ) {
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
	 */
	function check_marketpress_plugin() {
		global $mp;
		if ( class_exists('MarketPress') && is_object( $mp ) ) {
			$this->marketpress_active = true;
			// Also check if it is activated
			if ( isset( $this->options["use_mp"] ) && $this->options["use_mp"] ) {
				$this->mp = true;
				if (defined('MP_VERSION') && version_compare(MP_VERSION, '3.0', '>=')) {
					require_once('includes/class_app_mp_bridge.php');
					App_MP_Bridge::serve();
				} else {
					require_once('includes/class_app_mp_bridge_legacy.php');
					App_MP_Bridge_Legacy::serve();
				}
				return true;
			}
		}
		return false;
	}




/*******************************
* Methods for inits, styles, js
********************************
*/

	/**
     * Find blogs and install tables for each of them
	 * @since 1.0.2
	 * @until 1.4.1 - omg no, please let's never do this again
     */
	function install() { do_action('app-core-doing_it_wrong', __METHOD__); }
	/**
     * Install database tables
     */
	function _install() { do_action('app-core-doing_it_wrong', __METHOD__); }
	/**
	 * Install tables for new blog
	 * @since 1.0.2
	 * @until 1.4.1
	 */
	function new_blog($blog_id, $user_id, $domain, $path, $site_id, $meta ) { do_action('app-core-doing_it_wrong', __METHOD__); }
	/**
	 * Remove tables for a deleted blog
	 * @since 1.0.2
	 * @until 1.4.1
	 */
	function delete_blog( $blog_id, $drop )  { do_action('app-core-doing_it_wrong', __METHOD__); }

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
				$this->add_to_cache( $post->ID );
				
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

		// wpautop does strange things to cache content, so remove it first and add to output
		if ( $this->use_cache() ) {
			if ( has_filter( 'wpautop' ) ) {
				$this->had_filter = true;
			}
			remove_filter( 'the_content', 'wpautop' );
			remove_filter( 'the_excerpt', 'wpautop' );
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
				'gg_client_id' => $this->options['google-client_id'],
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

		// Don't show Google+ button if openid is not enabled
		if ( !@$this->openid )
			echo '.appointments-login_link-google{display:none !important;}';
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
			'use_cache'					=> 'no',
			'use_mp'					=> false,
			'allow_cancel'				=> 'no',
			'cancel_page'				=> 0
		));

		do_action( 'appointments_init', $this );

		//  Run this code not before 10 mins
		if ( ( time() - get_option( "app_last_update" ) ) < apply_filters( 'app_update_time', 600 ) ) {
			return;
		}

		$this->remove_appointments();

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
		_deprecated_function( __FUNCTION__, '1.7.3', 'Appointments_Notification_Manager::maybe_send_reminders()' );
		$this->notifications->maybe_send_reminders();
	}

	/**
	 * Determine if a page is A+ Product page from the shortcodes used
	 * @param WP_Post $product custom post object
	 * @return bool
	 * @Since 1.0.1
	 */
	function is_app_mp_page( $product ) {
		$result = false;
		if ( is_object( $product ) && strpos( $product->post_content, '[app_' ) !== false )
			$result = true;
		// Maybe required for templates
		return apply_filters( 'app_is_mp_page', $result, $product );
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
			'SERVICE' => $this->escape_backreference($service),
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
     *	Avoid back-reference collisions.
     *  http://us1.php.net/manual/en/function.preg-replace.php#103985
     */
    function escape_backreference($x)
    {
        return preg_replace('/\$(\d)/', '\\\$$1', $x);
    }

	/**
	 *	Email message headers
	 */
	function message_headers () {
		$admin_email = $this->get_admin_email();
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
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

		$clear_secs = 0;
		if ( isset( $this->options["clear_time"] ) && $this->options["clear_time"] > 0 ) {
			$clear_secs = $this->options["clear_time"] * 60;
		}

		$expireds = appointments_get_expired_appointments( $clear_secs );

		if ( $expireds && $process_expired ) {
			foreach ( $expireds as $expired ) {
				if ( 'pending' == $expired->status || 'reserved' == $expired->status ) {
					if ('reserved' == $expired->status && strtotime($expired->end) > $this->local_time) {
						$new_status = $expired->status; // Don't shift the GCal apps until they actually expire (end time in past)
					}
					else {
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
	 * @param text: email text
	 * @param app_id: ID of the appointment to be cancelled
	 * @since 1.2.6
	 */
	function add_cancel_link( $text, $app_id ) {
		if ( isset( $this->options['allow_cancel'] ) && 'yes' == $this->options['allow_cancel'] && $app_id ) {

			$app = appointments_get_appointment( $app_id );
			// The link to be clicked may belong to a formerly created and deleted appointment.
			// Another irrelevant app may have been created after cancel link has been sent. So we will add creation date for check
			if ( $app )
				return str_replace( 'CANCEL', add_query_arg( array( 'app_cancel'=>1, 'app_id'=>$app_id, 'app_nonce'=>md5( $app_id . $this->salt . strtotime( $app->created ) ) ), home_url() ), $text);
			else
				return str_replace( 'CANCEL', '', $text );
		}
		else
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
		$locale = preg_replace('/_/', '-', get_locale());
		$locale = apply_filters( 'app_locale', $locale );

		if (function_exists('glob') && !(defined('APP_FLAG_NO_GLOB') && APP_FLAG_NO_GLOB)) {
			$filename = false;
			$all = glob("{$this->plugin_dir}/js/jquery.datepick-*.js");
			$full_match = preg_quote("{$locale}.js", '/');
			$partial_match = false;
			if (substr_count($locale, '-')) {
				list($main_locale, $rest) = explode('-', $locale, 2);
				if (!empty($main_locale)) $partial_match = preg_quote("{$main_locale}.js", '/');
			}

			foreach ($all as $file) {
				if (preg_match('/' . $full_match . '$/', $file)) {
					$filename = $file;
					break;
				} else if ($partial_match && preg_match('/' . $partial_match . '$/', $file)) {
					$filename = $file;
				}
			}
			return !empty($filename)
				? "/js/" . basename($filename)
				: false
			;
		} else {
			$file = '/js/jquery.datepick-'.$locale.'.js';
			if ( file_exists( $this->plugin_dir . $file ) )
				return $file;

			if ( substr_count( $locale, '-' ) ) {
				$l = explode( '-', $locale );
				$locale = $l[0];
				$file = '/js/jquery.datepick-'.$locale.'.js';
				if ( file_exists( $this->plugin_dir . $file ) )
					return $file;
			}
		}

		return false;
	}

	// Read and return local month names from datepick
	// Since 1.0.6.1
	function datepick_local_months() {
		if ( !$file = $this->datepick_localfile() )
			return false;

		if ( !$file_content = @file_get_contents(  $this->plugin_dir . $file ) )
			return false;

		$file_content = str_replace( array("\r","\n","\t"), '', $file_content );

		if ( preg_match( '/monthNames:(.*?)]/s', $file_content, $matches ) ) {
			$months = str_replace( array('[',']',"'",'"'), '', $matches[1] );
			return explode( ',', $months );
		}
		return false;
	}


	// Read and return abbrevated local month names from datepick
	// Since 1.0.6.3
	function datepick_abb_local_months() {
		if ( !$file = $this->datepick_localfile() )
			return false;

		if ( !$file_content = @file_get_contents(  $this->plugin_dir . $file ) )
			return false;

		$file_content = str_replace( array("\r","\n","\t"), '', $file_content );

		if ( preg_match( '/monthNamesShort:(.*?)]/s', $file_content, $matches ) ) {
			$months = str_replace( array('[',']',"'",'"'), '', $matches[1] );
			return explode( ',', $months );
		}
		return false;
	}

	

	/**
	 * Check if there are more than one shortcodes for certain shortcode types
	 * @since 1.0.5
	 * @return bool
	 */
	function has_duplicate_shortcode( $post_id ) {
		$post = get_post( $post_id );
		if ( is_object( $post) && $post && strpos( $post->post_content, '[app_' ) !== false ) {
			if ( substr_count( $post->post_content, '[app_services' ) > 1 || substr_count( $post->post_content, '[app_service_providers' ) > 1
				|| substr_count( $post->post_content, '[app_confirmation' ) > 1 || substr_count( $post->post_content, '[app_paypal' ) > 1
				|| substr_count( $post->post_content, '[app_login' ) > 1 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if confirmation shortcode missing
	 * @since 1.2.5
	 * @return bool
	 */
	function confirmation_shortcode_missing( $post_id ) {
		$post = get_post( $post_id );
		if ( is_object( $post) && $post && strpos( $post->post_content, '[app_' ) !== false ) {
			if ( !substr_count( $post->post_content, '[app_confirmation' )
				&& ( substr_count( $post->post_content, '[app_monthly' ) || substr_count( $post->post_content, '[app_schedule' ) ) )
				return true;
		}
		return false;
	}

	

	

	

	private function _create_pages () {
		// Add an appointment page
		if ( isset( $_POST["make_an_appointment"] ) ) {
			$tpl = !empty($_POST['app_page_type']) ? $_POST['app_page_type'] : false;
			wp_insert_post(
					array(
						'post_title'	=> 'Make an Appointment',
						'post_status'	=> 'publish',
						'post_type'		=> 'page',
						'post_content'	=> App_Template::get_default_page_template($tpl)
					)
			);
		}

		// Add an appointment product page
		if ( isset( $_POST["make_an_appointment_product"] ) && $this->marketpress_active ) {
			$tpl = !empty($_POST['app_page_type_mp']) ? $_POST['app_page_type_mp'] : false;
			$post_id = wp_insert_post(
					array(
						'post_title'	=> 'Appointment',
						'post_status'	=> 'publish',
						'post_type'		=> 'product',
						'post_content'	=> App_Template::get_default_page_template($tpl)
					)
			);
			if ( $post_id ) {
				// Add a download link, so that app will be a digital product
				$file = get_post_meta($post_id, 'mp_file', true);
				if ( !$file ) add_post_meta( $post_id, 'mp_file', get_permalink( $post_id) );

				// MP requires at least 2 variations, so we add a dummy one
				add_post_meta( $post_id, 'mp_var_name', array( 0 ) );
				add_post_meta( $post_id, 'mp_sku', array( 0 ) );
				add_post_meta( $post_id, 'mp_price', array( 0 ) );
			}
		}
	}



	/**
	 *	Sorts a comma delimited string
	 *	@since 1.2
	 */
	function _sort( $input ) {
		if ( strpos( $input, ',') === false )
			return $input;
		$temp = explode( ',', $input );
		sort( $temp );
		return implode( ',', $temp );
	}

	/**
	 *	Packs an array into a string with : as glue
	 */
	function _implode( $input ) {
		if ( !is_array( $input ) || empty( $input ) )
			return false;
		return ':'. implode( ':', array_filter( $input ) ) . ':';
	}

	/**
	 *	Packs a string into an array assuming : as glue
	 */
	function _explode( $input ){
		if ( !is_string( $input ) )
			return false;
		return array_filter( explode( ':' , ltrim( $input , ":") ) );
	}

	/**
	 * Deletes a worker's database records in case he is deleted
	 * @since 1.0.4
	 */
	function delete_user( $ID ) {
		if ( !$ID )
			return;

		global $wpdb;
		$r1 = appointments_delete_worker( $ID );
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
	 * Prints "Cache cleared" message on top of Admin page
	 */
	function cleared( ) {
		echo '<div class="updated fade"><p><b>[Appointments+]</b> '. __('Cache cleared.','appointments').'</p></div>';
	}

	/**
	 * Prints "settings saved and cache cleared" message on top of Admin page
	 * @since 1.1.7
	 */
	function saved_cleared( ) {
		echo '<div class="updated fade"><p><b>[Appointments+]</b> '. __('Settings saved and cache cleared.','appointments').'</p></div>';
	}

	/**
	 * Prints "saved" message on top of Admin page
	 */
	function saved( ) {
		echo '<div class="updated fade"><p><b>[Appointments+]</b> '. __('Settings saved.','appointments').'</p></div>';
	}

	/**
	 * Prints "deleted" message on top of Admin page
	 */
	function deleted( ) {
		echo '<div class="updated fade"><p><b>[Appointments+]</b> '. __('Selected record(s) deleted.','appointments').'</p></div>';
	}

	/**
	 * Prints "updated" message on top of Admin page
	 */
	function updated( ) {
		echo '<div class="updated fade"><p><b>[Appointments+]</b> '. __('Selected record(s) updated.','appointments').'</p></div>';
	}

	/**
	 * Prints warning message on top of Admin page
	 */
	function warning( ) {
		echo '<div class="updated fade"><p><b>[Appointments+] '. __('You are not authorised to do this.','appointments').'</b></p></div>';
	}



	
	/**
	 *	Add a service
	 *  @param php: True if this will be used in first call, false if this is js
	 *  @param service: Service object that will be displayed (only when php is true)
	 */
	function add_service( $php=false, $service='' ) {
		if ( $php ) {
			if ( is_object($service)) {
				$n = $service->ID;
				$name = $service->name;
				$capacity = $service->capacity;
				$price = $service->price;
			 }
			 else return;
		}
		else {
			$n = "'+n+'";
			$name = '';
			$capacity = '0';
			$price = '';
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
	 *	Add a worker
	 *  @param php: True if this will be used in first call, false if this is js
	 *  @param worker: Worker object that will be displayed (only when php is true)
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
			}
			 else return;
		}
		else {
			$k = "'+k+'";
			$price = '';
			$dummy = '';
			$workers =str_replace( array("\t","\n","\r"), "", str_replace( array("'", "&#039;"), array('"', "'"), wp_dropdown_users( array ( 'echo'=>0, 'show'=>'user_login', 'include'=>0, 'name'=>'workers['.$k.'][user]', 'exclude'=>apply_filters('app_filter_providers', null)) ) ) );
		}
		global $wpdb;

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
		//$_old_time_format = $this->time_format;
		//$this->time_format = "H:i";
		$_required_format = "H:i";

		$this->get_lsw();

		if ( isset( $this->options["admin_min_time"] ) && $this->options["admin_min_time"] )
			$min_time = $this->options["admin_min_time"];
		else
			$min_time = $this->get_min_time();

		$min_secs = 60 * apply_filters( 'app_admin_min_time', $min_time );

		$wb = $this->get_work_break( $this->location, $this->worker, $status );
		if ( $wb )
			$whours = maybe_unserialize( $wb->hours );
		else
			$whours = array();

		$form = '';
		$form .= '<table class="app-working_hours-workhour_form">';
		if ( 'open' == $status )
			$form .= '<tr><th>'.__('Day', 'appointments').'</th><th>'.__('Work?', 'appointments' ).'</th><th>'.__('Start', 'appointments').'</th><th>'.__('End', 'appointments').'</th></tr>';
		else
			$form .= '<tr><th>'.__('Day', 'appointments').'</th><th>'.__('Give break?','appointments').'</th><th>'.__('Start','appointments').'</th><th>'.__('End','appointments').'</th></tr>';
		foreach ( $this->weekdays() as $day_label => $day ) {
			if (!empty($whours[$day]['active']) && is_array($whours[$day]['active'])) {
				$total_whour_segments = count($whours[$day]['active']) - 1;
				// We have multiple breaks for today.
				foreach ($whours[$day]['active'] as $idx => $active) {
					$form .= '<tr ' . ($idx > 0 ? 'class="app-repeated"' : '') . '><td>';
					if (0 == $idx) $form .= $day_label;
					$form .= '</td>';
					$form .= '<td>';
					$form .= '<select name="'.$status.'['.$day.'][active][' . $idx . ']" autocomplete="off">';
					if ( 'yes' == $active )
						$s = " selected='selected'";
					else $s = '';
					$form .= '<option value="no">'.__('No', 'appointments').'</option>';
					$form .= '<option value="yes"'.$s.'>'.__('Yes', 'appointments').'</option>';
					$form .= '</select>';
					$form .= '</td>';
					$form .= '<td>';
					$form .= '<select name="'.$status.'['.$day.'][start][' . $idx . ']">';
					for ( $t=0; $t<3600*24; $t=$t+$min_secs ) {
						$dhours = esc_attr($this->secs2hours($t, $_required_format)); // Hours in 08:30 format - escape, because they're values now.
						$shours = $this->secs2hours($t);
						if ( isset($whours[$day]['start'][$idx]) && strtotime($dhours) == strtotime($whours[$day]['start'][$idx]) )
							$s = "selected='selected'";
						else $s = '';

						$form .= "<option {$s} value='{$dhours}'>";
						$form .= $shours;
						$form .= '</option>';
					}
					$form .= '</select>';
					$form .= '</td>';

					$form .= '<td>';
					$form .= '<select name="'.$status.'['.$day.'][end][' . $idx . ']" autocomplete="off">';
					for ( $t=$min_secs; $t<=3600*24; $t=$t+$min_secs ) {
						$dhours = esc_attr($this->secs2hours($t, $_required_format)); // Hours in 08:30 format - escape, because they're values now.
						$shours = $this->secs2hours($t);
						if ( isset($whours[$day]['end'][$idx]) && strtotime($dhours) == strtotime($whours[$day]['end'][$idx]) )
							$s = "selected='selected'";
						else $s = '';

						$form .= "<option {$s} value='{$dhours}'>";
						$form .= $shours;
						$form .= '</option>';
					}
					$form .= '</select>';
					if ('closed' == $status && $idx == 0 && 'yes' == $active) $form .= '&nbsp;<a href="#add_break" class="app-add_break" title="' . esc_attr(__('Add break', 'appointments')) . '"><span>' . __('Add break', 'appointments') . '</span></a>';
					$form .= '</td>';

					$form .= '</tr>';
				}
			} else {
				// Oh, it's just one break.
				$form .= '<tr><td>';
				$form .= $day_label;
				$form .= '</td>';
				$form .= '<td>';
				$form .= '<select name="'.$status.'['.$day.'][active]" autocomplete="off">';
				if ( isset($whours[$day]['active']) && 'yes' == $whours[$day]['active'] )
					$s = " selected='selected'";
				else $s = '';
				$form .= '<option value="no">'.__('No', 'appointments').'</option>';
				$form .= '<option value="yes"'.$s.'>'.__('Yes', 'appointments').'</option>';
				$form .= '</select>';
				$form .= '</td>';
				$form .= '<td>';
				$form .= '<select name="'.$status.'['.$day.'][start]" autocomplete="off">';
				for ( $t=0; $t<3600*24; $t=$t+$min_secs ) {
					$dhours = esc_attr($this->secs2hours($t, $_required_format)); // Hours in 08:30 format - escape, because they're values now.
					$shours = $this->secs2hours($t);
					if ( isset($whours[$day]['start']) && strtotime($dhours) == strtotime($whours[$day]['start']) )
						$s = "selected='selected'";
					else $s = '';

					$form .= "<option {$s} value='{$dhours}'>";
					$form .= $shours;
					$form .= '</option>';
				}
				$form .= '</select>';
				$form .= '</td>';

				$form .= '<td>';
				$form .= '<select name="'.$status.'['.$day.'][end]" autocomplete="off">';
				for ( $t=$min_secs; $t<=3600*24; $t=$t+$min_secs ) {
					$dhours = esc_attr($this->secs2hours($t, $_required_format)); // Hours in 08:30 format - escape, because they're values now.
					$shours = $this->secs2hours($t);
					if ( isset($whours[$day]['end']) && strtotime($dhours) == strtotime($whours[$day]['end']) )
						$s = " selected='selected'";
					else $s = '';

					$form .= "<option {$s} value='{$dhours}'>";
					$form .= $shours;
					$form .= '</option>';
				}
				$form .= '</select>';
				if ('closed' == $status && isset($whours[$day]['active']) && 'yes' == $whours[$day]['active']) $form .= '&nbsp;<a href="#add_break" class="app-add_break" title="' . esc_attr(__('Add break', 'appointments')) . '"><span>' . __('Add break', 'appointments') . '</span></a>';
				$form .= '</td>';

				$form .= '</tr>';
			}
		}

		$form .= '</table>';

		//$this->time_format = $_old_time_format;

		return $form;
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
	 */
	function get_transactions($type, $startat, $num) {

		switch($type) {

			case 'past':
						$sql = $this->db->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->transaction_table} WHERE transaction_status NOT IN ('Pending', 'Future') ORDER BY transaction_ID DESC  LIMIT %d, %d", $startat, $num );
						break;
			case 'pending':
						$sql = $this->db->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->transaction_table} WHERE transaction_status IN ('Pending') ORDER BY transaction_ID DESC LIMIT %d, %d", $startat, $num );
						break;
			case 'future':
						$sql = $this->db->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->transaction_table} WHERE transaction_status IN ('Future') ORDER BY transaction_ID DESC LIMIT %d, %d", $startat, $num );
						break;

		}

		return $this->db->get_results( $sql );

	}

	/**
	 *	Find if a Paypal transaction is duplicate or not
	 */
	function duplicate_transaction($app_id, $amount, $currency, $timestamp, $paypal_ID, $status, $note,$content=0) {
		$sql = $this->db->prepare( "SELECT transaction_ID FROM {$this->transaction_table} WHERE transaction_app_ID = %d AND transaction_paypal_ID = %s AND transaction_stamp = %d LIMIT 1 ", $app_id, $paypal_ID, $timestamp );

		$trans = $this->db->get_var( $sql );
		if(!empty($trans)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 *	Save a Paypal transaction to the database
	 */
	function record_transaction($app_id, $amount, $currency, $timestamp, $paypal_id, $status, $note) {

		$data = array();
		$data['transaction_app_ID'] = $app_id;
		$data['transaction_paypal_ID'] = $paypal_id;
		$data['transaction_stamp'] = $timestamp;
		$data['transaction_currency'] = $currency;
		$data['transaction_status'] = $status;
		$data['transaction_total_amount'] = (int) round($amount * 100);
		$data['transaction_note'] = $note;

		$existing_id = $this->db->get_var( $this->db->prepare( "SELECT transaction_ID FROM {$this->transaction_table} WHERE transaction_paypal_ID = %s LIMIT 1", $paypal_id ) );

		if(!empty($existing_id)) {
			// Update
			$this->db->update( $this->transaction_table, $data, array('transaction_ID' => $existing_id) );
		} else {
			// Insert
			$this->db->insert( $this->transaction_table, $data );
		}

	}

	function get_total() {
		return $this->db->get_var( "SELECT FOUND_ROWS();" );
	}


	function mytransactions ($type = 'past') {
		App_Template::admin_my_transactions_list($type);
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
require_once APP_PLUGIN_DIR . '/includes/class_app_shortcodes.php';
require_once APP_PLUGIN_DIR . '/includes/class_app_addon_helper.php';

App_Installer::serve();

App_AddonHandler::serve();
App_Shortcodes::serve();

global $appointments;
$appointments = new Appointments();

if (is_admin()) {
	require_once APP_PLUGIN_DIR . '/includes/support/class_app_tutorial.php';
	App_Tutorial::serve();

	require_once APP_PLUGIN_DIR . '/includes/support/class_app_admin_help.php';
	App_AdminHelp::serve();

	// Setup dashboard notices
	if (file_exists(APP_PLUGIN_DIR . '/includes/external/wpmudev-dash-notification.php')) {
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
		require_once APP_PLUGIN_DIR . '/includes/external/wpmudev-dash-notification.php';
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
	global $appointments;
	return trailingslashit( plugin_dir_path( __FILE__ ) );
}

function appointments() {
	global $appointments;
	return $appointments;
}
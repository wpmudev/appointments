<?php

class Appointments_Integration_BuddyPress {

	private $_bp = false;
	private $_bp_script = '';

	private static $_bp_ready = false;

	private function __construct() {}

	public function initialize() {
		global $appointments;
		//add_action('bp_init', array($this->_core, 'cancel'), 99); // Check cancellation of an appointment // Don't double up on this, we're already doing it on init
	}

	public static function serve() {
		$me = new self;
		$me->_add_hooks();
	}

	public static function is_ready() {
		return self::$_bp_ready;
	}

	private function _add_hooks() {
		add_action( 'plugins_loaded', array( $this, 'initialize' ), 1 );
		add_action( 'bp_setup_nav', array( $this, 'setup_nav' ), 20 );
		add_action( 'bp_init', array( $this, 'bp_init' ) );
		add_action( 'template_redirect', array( $this, 'bp_template_redirect' ) ); // bp_template_redirect is not working
		add_action( 'wp_footer', array( $this, 'bp_footer' ) ); // The same action as wp_footer
	}

	/**
	 * Save setting submitted from front end
	 */
	function bp_init() {

		$options = appointments_get_options();
		$appointments = appointments();

		$this->_bp = true;
		self::$_bp_ready = true;

		if ( ! isset( $_POST['app_bp_settings_submit'] ) || ! isset( $_POST['app_bp_settings_user'] ) ) { return; }

		// In the future we may use this function without BP too
		if ( function_exists( 'bp_loggedin_user_id' ) ) {
			$user_id = bp_loggedin_user_id(); } else {
			global $current_user;
			$user_id = $current_user->ID;
			}

			if (
				! $user_id || ! wp_verify_nonce( $_POST['app_bp_settings_submit'],'app_bp_settings_submit' )
				|| $user_id != $_POST['app_bp_settings_user'] || ! appointments_is_worker( $user_id )
				|| ! isset( $options['allow_worker_confirm'] ) || 'yes' != $options['allow_worker_confirm']
			) {
				wp_die( 'You don\'t have the authority to do this.', 'appointments' );
				exit;
			}
			// Checks are ok, let's save settings.
			if ( ! is_object( $appointments->admin ) ) {
				$appointments->load_admin();
			}

			$appointments->admin->user_profile->save_profile( $user_id );
	}

	/**
	 * Determine which page we are on
	 * If it is correct, load necessary scripts and css files
	 */
	function bp_template_redirect() {
		global $bp;

		$appointments = appointments();

		if ( ! is_object( $bp ) ) { return; }

		$scheme = is_ssl() ? 'https://' : 'http://';
		$requested_url = strtolower( $scheme . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

		$page_url = str_replace( $bp->displayed_user->domain . 'appointments/', '', $requested_url );

		// Load datepick if we are on settings page
		if ( strpos( $page_url, 'appointment-settings' ) !== false ) {
			_appointments_enqueue_jquery_ui_datepicker();
			$this->_bp_script = "$('.app-datepick').each( function() { new AppDatepicker( $(this) ); });";
			wp_enqueue_script( 'appointments-admin', appointments_plugin_url()  . '/admin/js/admin.js', array( 'jquery' ), $appointments->version );

			wp_enqueue_script( 'app-multi-datepicker', appointments_plugin_url() . 'admin/js/admin-multidatepicker.js', array( 'jquery-ui-datepicker' ), appointments_get_db_version(), true );
			wp_enqueue_style( 'app-jquery-ui', appointments_plugin_url() . 'admin/css/jquery-ui/jquery-ui.min.css', array(), appointments_get_db_version() );

		}
	}

	/**
	 * Load javascript to the footer
	 */
	function bp_footer() {
		$script = '';
		$this->_bp_script = apply_filters( 'app_bp_footer_scripts', $this->_bp_script );

		if ( $this->_bp_script ) {
			$script .= '<script type="text/javascript">';
			$script .= 'jQuery(document).ready(function($) {';
			$script .= $this->_bp_script;
			$script .= '});</script>';
		}

		$appointments = appointments();
		echo $appointments->esc_rn( $script );
	}

	/**
	 * Add a nav and two subnav items
	 */
	function setup_nav() {
		global $bp;

		$options = appointments_get_options();

		bp_core_new_nav_item(array(
			'name' => __( 'Appointments', 'appointments' ),
			'slug' => 'appointments',
			'default_subnav_slug' => 'my-appointments',
			'show_for_displayed_user' => false,
			'screen_function' => array( $this, 'tab_template' ),
		));

		$link = $bp->loggedin_user->domain . 'appointments/';

		$user_id = bp_loggedin_user_id();
		if ( ! appointments_is_worker( $user_id ) ) { $name = __( 'My Appointments', 'appointments' ); } else { $name = __( 'My Appointments as Provider', 'appointments' ); }

		bp_core_new_subnav_item(array(
			'name' => $name,
			'slug' => 'my-appointments',
			'parent_url' => $link,
			'parent_slug' => 'appointments',
			'screen_function' => array( $this, 'tab_template_my_app' ),
		));

		// Generate this tab only if allowed
		if ( appointments_is_worker( $user_id ) && isset( $options['allow_worker_wh'] ) && 'yes' == $options['allow_worker_wh'] ) {
			bp_core_new_subnav_item(array(
				'name' => __( 'Appointments Settings', 'appointments' ),
				'slug' => 'appointment-settings',
				'parent_url' => $link,
				'parent_slug' => 'appointments',
				'screen_function' => array( $this, 'tab_template_app_settings' ),
			));
		}
	}

	/**
	 * Helper functions that BP requires
	 */
	function tab_template() {
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	function tab_template_my_app() {
		add_action( 'bp_template_content', array( $this, 'screen_content_my_app' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	function tab_template_app_settings() {
		add_action( 'bp_template_content', array( $this, 'screen_content_app_settings' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	/**
	 * Generate content for my apps
	 */
	function screen_content_my_app() {
		$options = appointments_get_options();
		if ( sset( $options['gcal'] ) && 'yes' == $options['gcal'] ) { $gcal = ''; // Default is already enabled
		} else { $gcal = ' gcal="0"'; }

		$user_id = bp_loggedin_user_id();

		do_action( 'app_before_bp_my_appointments', $user_id );

		if ( ! appointments_is_worker( $user_id ) ) {
			echo do_shortcode( '[app_my_appointments ' . $gcal . ']' ); } else { echo do_shortcode( '[app_my_appointments status="paid,confirmed,pending" _allow_confirm=1 provider=1 '.$gcal.']' ); }

		do_action( 'app_after_bp_my_appointments', $user_id );
	}

	/**
	 * Generate content for app settings
	 */
	function screen_content_app_settings() {
		$options = appointments_get_options();
		$appointments = appointments();

		// In the future we may use this function without BP too
		if ( function_exists( 'bp_loggedin_user_id' ) ) { $user_id = bp_loggedin_user_id(); } else {
			global $current_user;
			$user_id = $current_user->ID;
		}

		if ( appointments_is_worker( $user_id ) && isset( $options['allow_worker_wh'] ) && 'yes' == $options['allow_worker_wh'] ) {
			// A little trick to pass correct lsw variables to the related function
			$_REQUEST['app_location_id'] = 0;
			$_REQUEST['app_provider_id'] = $user_id;

			$appointments->get_lsw();

			$result = array();
			$result_open = appointments_get_worker_exceptions( $appointments->worker, 'open', $appointments->location );
			if ( $result_open ) { $result['open'] = $result_open->days; } else { $result['open'] = null; }

			$result_closed = appointments_get_worker_exceptions( $appointments->worker, 'closed', $appointments->location );
			if ( $result_closed ) { $result['closed'] = $result_closed->days; } else { $result['closed'] = null; }

			do_action( 'app_before_bp_app_settings', $user_id );

			?>
			<div class="standard-form">
				<form method="post">
					<h4><?php _e( 'My Working Hours', 'appointments' ); ?></h4>
					<?php $appointments->working_hour_form( 'open' ); ?>
					<h4><?php _e( 'My Break Hours', 'appointments' ); ?></h4>
					<?php $appointments->working_hour_form( 'closed' ); ?>

					<h4><?php _e( 'My Exceptional Working Days', 'appointments' ); ?></h4>

<div class="app-datepick" data-rel="#open_datepick"></div>
<input type="hidden" class="widefat" id="open_datepick" name="open[exceptional_days]" value="<?php echo $result['open']; ?>">
					<h4><?php _e( 'My Holidays', 'appointments' ); ?></h4>
<div class="app-datepick" data-rel="#closed_datepick"></div>
<input type="hidden" class="widefat" id="closed_datepick" name="closed[exceptional_days]" value="<?php echo $result['closed']; ?>">

					<div class="submit">
						<input type="submit" name="app_bp_settings_submit" value="<?php _e( 'Save Changes', 'appointments' )?>" class="auto">
						<input type="hidden" name="app_bp_settings_user" value="<?php echo $user_id ?>">
						<?php wp_nonce_field( 'app_bp_settings_submit','app_bp_settings_submit' ); ?>
					</div>
				</form>
			</div>

			<?php
			do_action( 'app_after_bp_app_settings', $user_id );
		}
	}
}

Appointments_Integration_BuddyPress::serve();

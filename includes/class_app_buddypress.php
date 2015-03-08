<?php

class App_BuddyPress {

	private $_bp = false;
	private $_bp_script = '';
	private $_core = false;

	private static $_bp_ready = false;

	private function __construct () {}

	public function initialize () {
		global $appointments;
		$this->_core = $appointments;
		//add_action('bp_init', array($this->_core, 'cancel'), 99); // Check cancellation of an appointment // Don't double up on this, we're already doing it on init
	}

	public static function serve () {
		$me = new self;
		$me->_add_hooks();
	}

	public static function is_ready () {
		return self::$_bp_ready;
	}

	private function _add_hooks () {
		add_action('plugins_loaded', array($this, 'initialize'), 1);
		add_action('bp_setup_nav', array($this, 'setup_nav'), 20);
		add_action('bp_init', array($this, 'bp_init'));
		add_action('template_redirect', array($this, 'bp_template_redirect')); // bp_template_redirect is not working
		add_action('wp_footer', array($this, 'bp_footer')); // The same action as wp_footer
	}

	/**
     * Save setting submitted from front end
     */
	function bp_init () {

		$this->_bp = true;
		self::$_bp_ready = true;

		if (!isset($_POST["app_bp_settings_submit"] ) || !isset( $_POST["app_bp_settings_user"])) return;

		// In the future we may use this function without BP too
		if ( function_exists( 'bp_loggedin_user_id') )
			$user_id = bp_loggedin_user_id();
		else {
			global $current_user;
			$user_id = $current_user->ID;
		}

		if (
				!$user_id || !wp_verify_nonce($_POST['app_bp_settings_submit'],'app_bp_settings_submit')
				|| $user_id != $_POST["app_bp_settings_user"] || !$this->_core->is_worker($user_id)
				|| !isset($this->_core->options["allow_worker_wh"]) || 'yes' != $this->_core->options["allow_worker_wh"]
		) {
			wp_die('You don\'t have the authority to do this.', 'appointments');
			exit;
		}
		// Checks are ok, let's save settings.
		$this->_core->save_profile( $user_id );
	}

	/**
     * Determine which page we are on
	 * If it is correct, load necessary scripts and css files
     */
	function bp_template_redirect () {
		global $bp;
		if (!is_object($bp)) return;

		$scheme = is_ssl() ? 'https://' : 'http://';
		$requested_url = strtolower($scheme . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

		$page_url = str_replace($bp->displayed_user->domain . 'appointments/', '', $requested_url);

		// Load datepick if we are on settings page
		if (strpos($page_url, 'appointment-settings') !== false) {
			$this->_bp_script ='$("#open_datepick").datepick({dateFormat: "yyyy-mm-dd",multiSelect: 999, monthsToShow: 2});
				$("#closed_datepick").datepick({dateFormat: "yyyy-mm-dd",multiSelect: 999, monthsToShow: 2});';

			wp_enqueue_script('jquery-datepick', $this->_core->plugin_url . '/js/jquery.datepick.min.js', array('jquery'), $this->_core->version);
			wp_enqueue_style("jquery-datepick", $this->_core->plugin_url . "/css/jquery.datepick.css", false, $this->_core->version);
			wp_enqueue_script("appointments-admin", $this->_core->plugin_url . "/js/admin.js", array('jquery'), $this->_core->version);
		}
	}

	/**
	 * Load javascript to the footer
	 */
	function bp_footer () {
		$script = '';
		$this->_bp_script = apply_filters('app_bp_footer_scripts', $this->_bp_script);

		if ( $this->_bp_script ) {
			$script .= '<script type="text/javascript">';
			$script .= "jQuery(document).ready(function($) {";
			$script .= $this->_bp_script;
			$script .= "});</script>";
		}

		echo $this->_core->esc_rn( $script );
	}

	/**
     * Add a nav and two subnav items
     */
	function setup_nav () {
		global $bp;
		bp_core_new_nav_item(array(
			'name' => __('Appointments', 'appointments'),
			'slug' => 'appointments',
			'default_subnav_slug' => 'my-appointments',
			'show_for_displayed_user' => false,
			'screen_function' => array($this, 'tab_template')
		));

		$link = $bp->loggedin_user->domain . 'appointments/';

		$user_id = bp_loggedin_user_id();
		if (!$this->_core->is_worker($user_id)) $name = __('My Appointments', 'appointments');
		else $name = __('My Appointments as Provider', 'appointments');

		bp_core_new_subnav_item(array(
			'name' => $name,
			'slug' => 'my-appointments',
			'parent_url' => $link,
			'parent_slug' => 'appointments',
			'screen_function' => array($this, 'tab_template_my_app')
		));

		// Generate this tab only if allowed
		if ($this->_core->is_worker($user_id) && isset($this->_core->options["allow_worker_wh"]) && 'yes' == $this->_core->options["allow_worker_wh"]) {
			bp_core_new_subnav_item(array(
				'name' => __( 'Appointments Settings', 'appointments' ),
				'slug' => 'appointment-settings',
				'parent_url' => $link,
				'parent_slug' => 'appointments',
				'screen_function' => array($this, 'tab_template_app_settings')
			));
		}
	}

	/**
     * Helper functions that BP requires
     */
	function tab_template () {
		bp_core_load_template(apply_filters('bp_core_template_plugin', 'members/single/plugins'));
	}

	function tab_template_my_app () {
		add_action('bp_template_content', array($this, 'screen_content_my_app'));
		bp_core_load_template(apply_filters('bp_core_template_plugin', 'members/single/plugins'));
	}

	function tab_template_app_settings () {
		add_action('bp_template_content', array($this, 'screen_content_app_settings'));
		bp_core_load_template(apply_filters('bp_core_template_plugin', 'members/single/plugins'));
	}

	/**
     * Generate content for my apps
     */
	function screen_content_my_app () {
		if (isset($this->_core->options["gcal"] ) && 'yes' == $this->_core->options["gcal"]) $gcal = ''; // Default is already enabled
		else $gcal = ' gcal="0"';

		$user_id = bp_loggedin_user_id();

		do_action('app_before_bp_my_appointments', $user_id);

		if (!$this->_core->is_worker($user_id)) echo do_shortcode("[app_my_appointments ".$gcal."]");
		else echo do_shortcode('[app_my_appointments status="paid,confirmed,pending" _allow_confirm=1 provider=1 '.$gcal.']');

		do_action('app_after_bp_my_appointments', $user_id);
	}

	/**
     * Generate content for app settings
     */
	function screen_content_app_settings () {
		// In the future we may use this function without BP too
		if (function_exists('bp_loggedin_user_id')) $user_id = bp_loggedin_user_id();
		else {
			global $current_user;
			$user_id = $current_user->ID;
		}

		if ($this->_core->is_worker( $user_id ) && isset($this->_core->options["allow_worker_wh"]) && 'yes' == $this->_core->options["allow_worker_wh"]) {
			// A little trick to pass correct lsw variables to the related function
			$_REQUEST["app_location_id"] = 0;
			$_REQUEST["app_provider_id"] = $user_id;

			$this->_core->get_lsw();

			$result = array();
			$result_open = $this->_core->get_exception($this->_core->location, $this->_core->worker, 'open');
			if ($result_open) $result["open"] = $result_open->days;
			else $result["open"] = null;

			$result_closed = $this->_core->get_exception($this->_core->location, $this->_core->worker, 'closed');
			if ($result_closed) $result["closed"] = $result_closed->days;
			else $result["closed"] = null;

			do_action('app_before_bp_app_settings', $user_id);

			?>
			<div class="standard-form">
				<form method="post">
					<h4><?php _e('My Working Hours', 'appointments'); ?></h4>
					<?php echo $this->_core->working_hour_form('open'); ?>
					<h4><?php _e('My Break Hours', 'appointments'); ?></h4>
					<?php echo $this->_core->working_hour_form('closed'); ?>

					<h4><?php _e('My Exceptional Working Days', 'appointments'); ?></h4>

					<input class="datepick" id="open_datepick" type="text" style="width:100%" name="open[exceptional_days]" value="<?php if (isset($result["open"])) echo $result["open"]?>" />

					<h4><?php _e('My Holidays', 'appointments'); ?></h4>

					<input class="datepick" id="closed_datepick" type="text" style="width:100%" name="closed[exceptional_days]" value="<?php if (isset($result["closed"])) echo $result["closed"]?>" />


					<div class="submit">
						<input type="submit" name="app_bp_settings_submit" value="<?php _e('Save Changes', 'appointments')?>" class="auto">
						<input type="hidden" name="app_bp_settings_user" value="<?php echo $user_id ?>">
						<?php wp_nonce_field('app_bp_settings_submit','app_bp_settings_submit'); ?>
					</div>
				</form>
			</div>

			<?php
			do_action('app_after_bp_app_settings', $user_id);
		}
	}
}
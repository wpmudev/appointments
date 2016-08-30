<?php
/*
Plugin Name: AJAX shortcode
Description: Combines service, provider and calendar selection into one AJAX-powered interface
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0-BETA-2
AddonType: Shortcodes
Author: WPMU DEV
*/

class App_Shortcodes_AjaxCombo {

	private $_default_shortcodes = array(
		'app_login',
		'app_services',
		'app_service_providers',
		'app_schedule',
		'app_confirmation',
		'app_paypal',
		'app_thank_you',
	);

	private $_known_shortcodes = array();

	private function __construct () {

	}

	public static function serve () {
		$me = new App_Shortcodes_AjaxCombo;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('init', array($this, 'initialize'));

		add_action('wp_footer', array($this, 'tmp_inject_script'));

		add_action('wp_ajax_app_combo_list_providers', array($this, 'ajax_list_providers'));
		add_action('wp_ajax_nopriv_app_combo_list_providers', array($this, 'ajax_list_providers'));

		add_action('wp_ajax_app_combo_list_times', array($this, 'ajax_list_times'));
		add_action('wp_ajax_nopriv_app_combo_list_times', array($this, 'ajax_list_times'));
	}

	public function initialize () {
		add_shortcode('app_combo', array($this, 'process_shortcode'));
	}

	public function process_shortcode ($atts=array(), $content='') {
		$shortcodes = array();
		if ($content) {
			$raw_shortcodes = explode(']', $content);
			foreach ($raw_shortcodes as $raw) {
				$shortcodes[] = strip_tags((preg_replace('/\s*\[(\S+).*$/i', '\1', $raw)));
			}
		} else {
			$content = '[' . join('][', $this->_default_shortcodes) . ']';
			$shortcodes = $this->_default_shortcodes;
		}
		foreach ($shortcodes as $code) {
			if (!trim( $code )) continue;
			remove_shortcode(trim( $code ));
			add_shortcode(trim( $code ), array($this, 'cache_shortcode_data_' . $code));
		}
		do_shortcode($content);
		$data = $this->_get_cached_data();

		$step = isset($data['services'])
			? $this->_list_services($this->_get_cached_data('services'))
			: $this->_list_providers($this->_get_cached_data('service_providers'))
		;
		return '<div class="app_combo">' . $step . '</div>';
	}

	public function cache_shortcode_data_app_login ($atts=array()) { $this->_cache_shortcode_data('login', $atts); }
	public function cache_shortcode_data_app_services ($atts=array()) { $this->_cache_shortcode_data('services', $atts); }
	public function cache_shortcode_data_app_service_providers ($atts=array()) { $this->_cache_shortcode_data('service_providers', $atts); }
	public function cache_shortcode_data_app_schedule ($atts=array()) { $this->_cache_shortcode_data('schedule', $atts); }
	public function cache_shortcode_data_app_monthly_schedule ($atts=array()) { $this->_cache_shortcode_data('monthly_schedule', $atts); }
	public function cache_shortcode_data_app_confirmation ($atts=array()) { $this->_cache_shortcode_data('confirmation', $atts); }
	public function cache_shortcode_data_app_paypal ($atts=array()) { $this->_cache_shortcode_data('paypal', $atts); }
	public function cache_shortcode_data_app_thank_you ($atts=array(), $content='') {
		$atts['content'] = $content;
		$atts['refresh'] = !empty($atts['refresh']) && in_array($atts['refresh'], array('yes', 'true', '1'));
		if (!empty($atts['delay'])) $atts['delay'] = (int)$atts['delay'];
		if (!empty($atts['redirect'])) $atts['redirect'] = $atts['redirect'];
		$this->_cache_shortcode_data('thank_you', $atts); 
	}

	public function ajax_list_providers () {
		$args = $this->_get_cached_data('service_providers');
		$service_id = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : false;
		if ($service_id) $args['service'] = $service_id;
		die($this->_list_providers($args));
	}

	public function ajax_list_times () {
		$all = $this->_get_cached_data();
		$callback = isset($all['monthly_schedule']) ? 'app_monthly_schedule' : 'app_schedule';
		$args = isset($all['monthly_schedule']) ? $all['monthly_schedule'] : $all['schedule'];

		$worker_id = !empty($_POST['provider_id']) ? (int)$_POST['provider_id'] : false;
		if ($worker_id) $args['worker'] = $worker_id;
		
		$service_id = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : false;
		if ($service_id) $args['service'] = $service_id;
		else if (empty($service_id) && !empty($all['service_providers']['service'])) $args['service'] = $all['service_providers']['service'];

		/** @var App_Shortcode $handler */
		$handler = $this->_spawn_shortcode_handler($callback);
		/** @var App_Shortcode $confirmation */
		$confirmation = $this->_spawn_shortcode_handler('app_confirmation');
		/** @var App_Shortcode $paypal */
		$paypal = $this->_spawn_shortcode_handler('app_paypal');

		$response = ($handler && $confirmation && $paypal) 
			? $handler->process_shortcode($args) . $confirmation->process_shortcode() . $paypal->process_shortcode()
			: ''
		;
		die($response);
	}

	private function _cache_shortcode_data ($code, $data) {
		$all = $this->_get_cached_data();
		$all[$code] = $data;
		$this->_set_cached_data($all);
	}

	private function _get_cached_data ($key=false) {
		$all = get_option('app_ajax_combo');
		$all = is_array($all) ? $all : array();
		if ($key) return !empty($all[$key]) ? $all[$key] : array();
		return $all;
	}

	private function _set_cached_data ($data) {
		return update_option('app_ajax_combo', $data);
	}

	private function _generic_shortcode ($atts) {
		$args = shortcode_atts(array(
			'start_with' => 'providers',
		), $atts);
		return '<div class="app_combo">' .
			('providers' == $args['start_with'] 
				? $this->_list_providers($atts)
				: $this->_list_services($atts)
			) .
		'</div>';
	}

	private function _list_providers ($atts) {
		$atts['_noscript'] = true;
		$handler = $this->_spawn_shortcode_handler('app_service_providers');
		/** @var App_Shortcode $handler */
		if ($handler) return $handler->process_shortcode($atts);
		return false;
	}

	private function _list_services ($atts) {
		$atts['_noscript'] = true;
		$handler = $this->_spawn_shortcode_handler('app_services');
		/** @var App_Shortcode $handler */
		if ($handler) return $handler->process_shortcode($atts);
		return false;
	}

	private function _spawn_shortcode_handler ($name) {
		if (empty($this->_known_shortcodes)) $this->_obtain_known_shortcodes_list();
		return !empty($this->_known_shortcodes[$name]) && class_exists($this->_known_shortcodes[$name])
			? new $this->_known_shortcodes[$name]
			: false
		;
	}

	private function _obtain_known_shortcodes_list () {
		$this->_known_shortcodes = apply_filters('app-shortcodes-register', array());
	}



	function tmp_inject_script () {
		global $appointments;
		wp_enqueue_script('app-ajax-shortcode', $appointments->plugin_url . '/js/app-ajax-shortcode.js', array('jquery'), $appointments->version);
		wp_localize_script('app-ajax-shortcode', '_app_ajax_shortcode', array(
			'thank_you' => $this->_get_cached_data('thank_you'),
		));
	}
}
App_Shortcodes_AjaxCombo::serve();
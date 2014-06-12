<?php

abstract class App_Template {
	protected static $_status_names = array();
	protected static $_currencies = array();
	protected static $_currency_symbols = array();

	public static function get_status_name ($status) {
		$status_names = wp_parse_args(
			self::get_status_names(),
			array(
				'active' => __('Active', 'appointments'),
			)
		);
		return !empty($status_names[$status])
			? $status_names[$status]
			: $status
		;
	}

	public static function get_status_names () {
		if (!self::$_status_names) self::$_status_names = apply_filters('app-template-status_names', apply_filters( 'app_statuses', array(
			'pending' => __('Pending', 'appointments'),
			'paid' => __('Paid', 'appointments'),
			'confirmed' => __('Confirmed', 'appointments'),
			'completed' => __('Completed', 'appointments'),
			'reserved' => __('Reserved by GCal', 'appointments'),
			'removed' => __('Removed', 'appointments'),
		)));
		return self::$_status_names;
	}

	public static function get_currencies () {
		if (!self::$_currencies) self::$_currencies = apply_filters('app-template-currencies', array(
			'AUD' => __('AUD - Australian Dollar', 'appointments'),
			'BRL' => __('BRL - Brazilian Real', 'appointments'),
			'CAD' => __('CAD - Canadian Dollar', 'appointments'),
			'CHF' => __('CHF - Swiss Franc', 'appointments'),
			'CZK' => __('CZK - Czech Koruna', 'appointments'),
			'DKK' => __('DKK - Danish Krone', 'appointments'),
			'EUR' => __('EUR - Euro', 'appointments'),
			'GBP' => __('GBP - Pound Sterling', 'appointments'),
			'ILS' => __('ILS - Israeli Shekel', 'appointments'),
			'HKD' => __('HKD - Hong Kong Dollar', 'appointments'),
			'HUF' => __('HUF - Hungarian Forint', 'appointments'),
			'JPY' => __('JPY - Japanese Yen', 'appointments'),
			'MYR' => __('MYR - Malaysian Ringgits', 'appointments'),
			'MXN' => __('MXN - Mexican Peso', 'appointments'),
			'NOK' => __('NOK - Norwegian Krone', 'appointments'),
			'NZD' => __('NZD - New Zealand Dollar', 'appointments'),
			'PHP' => __('PHP - Philippine Pesos', 'appointments'),
			'PLN' => __('PLN - Polish Zloty', 'appointments'),
			'SEK' => __('SEK - Swedish Krona', 'appointments'),
			'SGD' => __('SGD - Singapore Dollar', 'appointments'),
			'TWD' => __('TWD - Taiwan New Dollars', 'appointments'),
			'THB' => __('THB - Thai Baht', 'appointments'),
			'TRY' => __('TRY - Turkish lira', 'appointments'),
			'USD' => __('USD - U.S. Dollar', 'appointments'),
		));
		return self::$_currencies;
	}

	public static function get_currency_symbol ($currency) {
		if (empty($currency)) return false;
		if (!self::$_currency_symbols) self::$_currency_symbols = apply_filters('app-template-currency_symbols', array(
			'AUD' => __('AUD', 'appointments'),
			'BRL' => __('BRL', 'appointments'),
			'CAD' => __('CAD', 'appointments'),
			'CHF' => __('CHF', 'appointments'),
			'CZK' => __('CZK', 'appointments'),
			'DKK' => __('DKK', 'appointments'),
			'EUR' => __('EUR', 'appointments'),
			'GBP' => __('GBP', 'appointments'),
			'ILS' => __('ILS', 'appointments'),
			'HKD' => __('HKD', 'appointments'),
			'HUF' => __('HUF', 'appointments'),
			'JPY' => __('JPY', 'appointments'),
			'MYR' => __('MYR', 'appointments'),
			'MXN' => __('MXN', 'appointments'),
			'NOK' => __('NOK', 'appointments'),
			'NZD' => __('NZD', 'appointments'),
			'PHP' => __('PHP', 'appointments'),
			'PLN' => __('PLN', 'appointments'),
			'SEK' => __('SEK', 'appointments'),
			'SGD' => __('SGD', 'appointments'),
			'TWD' => __('TWD', 'appointments'),
			'THB' => __('THB', 'appointments'),
			'TRY' => __('TRY', 'appointments'),
			'USD' => __('USD', 'appointments'),
		));
		return !empty(self::$_currency_symbols[$currency])
			? self::$_currency_symbols[$currency]
			: false
		;
	}
}



abstract class App_Codec_Instance {

	abstract public function register ($key);

	private $_positive_values = array(
		true, 'true', 'yes', 'on', '1'
	);
	
	private $_negative_values = array(
		false, 'false', 'no', 'off', '0'
	);

	protected function _arg_to_bool ($val) {
		return in_array($val, $this->_positive_values, true);
	}

	protected function _arg_to_int ($val) {
		if (!is_numeric($val)) return 0;
		return (int)$val;
	}

	protected function _arg_to_int_list ($val) {
		if (!strpos($val, ',')) return array();
		return array_filter(array_map('intval', array_map('trim', explode(',', $val))));
	}

	protected function _arg_to_string_list ($val) {
		if (!strpos($val, ',')) return array($val);
		return array_filter(array_map('trim', explode(',', $val)));
	}
	
}

abstract class App_Shortcode extends App_Codec_Instance {

	protected $_defaults = array();
	protected $_key;

	abstract public function process_shortcode ($args=array(), $content='');
	abstract public function get_usage_info ();

	public function register ($key) {
		$this->_key = $key;
		add_shortcode($key, array($this, "process_shortcode"));
		add_action('app-shortcodes-shortcode_help', array($this, "add_shortcode_help"));
		add_filter('app-shortcodes-shortcode_help-string', array($this, 'get_shortcode_help'));
	}

	public function get_shortcode_help ($all='') {
		$help = '';
		$usage = $this->get_usage_info();
		$arguments = $this->_defaults_to_help();
		if (empty($usage) && empty($arguments)) return $all;

		$args_help = '';
		if (!empty($arguments)) {
			$args_help .= '<h4>' . __('Arguments', 'appointments') . '</h4><dl>';
			foreach ($arguments as $key => $help) {
				$args_help .= '' .
					'<dt><code>' . $key . '</code></dt>' .
					'<dd>' . $help . '</dd>' .
				'';
			}
			$args_help .= '</dl>';
		}

		$help = '' .
			'<div class="postbox">' .
				'<h3 class="hndle"><span>' . 
					sprintf(__('Shortcode <code>%s</code>', 'appointments'), $this->_key) . 
				'</span></h3>' .
				'<div class="inside">' .
					'<h4>' . __('Shortcode', 'appointments') . '</h4>' .
					'<pre><code>[' . $this->_key . ']</code></pre>' .
					'<h4>' . __('Description', 'appointments'). '</h4>' .
					$usage .
					$args_help .
				'</div>' .
			'</div>' .
		'';
		return $all . $help;
	}

	public function add_shortcode_help () {
		echo $this->get_shortcode_help();
	}

	protected function _defaults_to_args () {
		$ret = array();
		foreach ($this->_defaults as $key => $item) {
			$ret[$key] = $item['value'];
		}
		return $ret;
	}


	protected function _defaults_to_help () {
		$ret = array();
		foreach ($this->_defaults as $key => $item) {
			$help = $this->_to_help_string($key, $item);
			if (!empty($help)) $ret[$key] = $help;
		}
		return $ret;
	}

	protected function _to_help_string ($arg, $help) {
		$ret = array();
		if (empty($help)) return false;
		if (!empty($help['help'])) {
			$ret[] = $help['help'];
		}
		if (!empty($help['allowed_values'])) {
			$ret[] = __('Allowed values:', 'appointments') . ' <code>' . join('</code>, <code>', $help['allowed_values']) . '</code>';
		}
		if (!empty($help['example'])) {
			$ret[] = __('Example:', 'appointments') . ' <code>[' . $this->_key . ' ... ' . $arg . '="' . $help['example'] . '"]</code>';
		}
		return join('<br />', $ret);
	}

}

abstract class App_Codec {
	
	protected $_instances = array();
	protected $_running = array();
	
	/**
	 * Registers shortcode handlers.
	 */
	protected function _register () {
		foreach ($this->_instances as $key => $codec) {
			if (!class_exists($codec)) continue;
			$code = new $codec;
			$code->register($key);
			$this->_running[$key] = $code;
		}
	}
}

class App_Shortcodes extends App_Codec {

	private static $_me;

	public static function serve () {
		if (!empty(self::$_me)) return false;
		self::$_me = new App_Shortcodes;
		add_action('init', array(self::$_me, 'do_register'));
	}

	public function do_register () {
		$this->_instances = apply_filters('app-shortcodes-register', array());
		$this->_register();
	}

	public static function get_shortcode_instance ($key) {
		if (empty(self::$_me)) return false;
		return !empty(self::$_me->_running[$key])
			? self::$_me->_running[$key]
			: false
		;
	}
}

/**
 * General purpose macro expansion codec class
 */
class App_Macro_GeneralCodec {
	const FILTER_TITLE = 'title';
	const FILTER_BODY = 'body';
	
	protected $_macros = array(
		'LOGIN_PAGE',
		'REGISTRATION_PAGE',
	);

	public function get_macros () {
		return $this->_macros;
	}

	public function expand ($str, $filter=false) {
		if (!$str) return $str;
		foreach ($this->_macros as $macro) {
			$callback = false;
			$method = 'replace_' . strtolower($macro);
			if (is_callable(array($this, $method))) {
				$callback = array($this, $method);
				$str = preg_replace_callback(
					'/(?:^|\b)' . preg_quote($macro, '/') . '(?:\b|$)/', 
					$callback, $str
				);
			} else $str = apply_filters('app-codec-macro_default-' . $method, $str, $this->_appointment, $filter);
		}
		if (!$filter || self::FILTER_TITLE == $filter) $str = wp_strip_all_tags($str);
		if (self::FILTER_BODY == $filter) $str = apply_filters('the_content', $str);
		return apply_filters('app-codec-expand', $str, $this->_appointment);
	}

	public function replace_login_page () {
		return '<a class="appointments-login_show_login" href="' . site_url( 'wp-login.php') . '">' . __('Login','appointments') . '</a>';
	}
	public function replace_registration_page () {
		return '<a class="appointments-login_show_login appointments-register" href="' . wp_registration_url() . '">' . __('Register','appointments') . '</a>';
	}
}


/**
 * Individual Macro expansion codec class.
 */
class App_Macro_Codec extends App_Macro_GeneralCodec {

	protected $_appointment;
	protected $_core;
	protected $_user;

	public function __construct ($app=false, $user_id=false) {
		$this->_macros = apply_filters('app-codec-macros', array_merge($this->_macros, array(
			'START_DATE',
			'END_DATE',
			'SERVICE',
			'WORKER',
		)));
		$this->_appointment = $app ? $app : false;
		$this->_user = $user_id ? get_user_by('id', $user_id) : false;

		global $appointments;
		$this->_core = $appointments;
	}

	public function set_user ($user) {
		if (is_object($user)) $this->_user = $user;
	}


	public function replace_start_date () {
		return mysql2date($this->_core->datetime_format, $this->_appointment->start);
	}

	public function replace_end_date () {
		return mysql2date($this->_core->datetime_format, $this->_appointment->end);
	}

	public function replace_service () {
		return $this->_core->get_service_name($this->_appointment->service);
	}

	public function replace_worker () {
		return $this->_core->get_worker_name($this->_appointment->worker);
	}

	public function replace_user_name () {
		return $this->_user->display_name;
	}

}
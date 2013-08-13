<?php

abstract class App_Template {
	protected static $_status_names = array();

	public static function get_status_name ($status) {
		$status_names = self::get_status_names();
		return !empty($status_names[$status])
			? $status_names[$status]
			: $status
		;
	}

	public static function get_status_names () {
		if (!self::$_status_names) self::$_status_names = apply_filters('app-template-status_names', apply_filters( 'app_statuses', array(
			'pending'	=> __('Pending', 'appointments'),
			'paid'		=> __('Paid', 'appointments'),
			'confirmed'	=> __('Confirmed', 'appointments'),
			'completed'	=> __('Completed', 'appointments'),
			'reserved'	=> __('Reserved by GCal', 'appointments'),
			'removed'	=> __('Removed', 'appointments'),
		)));
		return self::$_status_names;
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
	
	/**
	 * Registers shortcode handlers.
	 */
	protected function _register () {
		foreach ($this->_instances as $key => $codec) {
			if (!class_exists($codec)) continue;
			$code = new $codec;
			$code->register($key);
		}
	}
}

class App_Shortcodes extends App_Codec {

	public static function serve () {
		$me = new App_Shortcodes;
		add_action('init', array($me, 'do_register'));
	}

	public function do_register () {
		$this->_instances = apply_filters('app-shortcodes-register', array());
		$this->_register();
	}
}
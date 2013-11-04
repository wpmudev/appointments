<?php
/*
Plugin Name: Durations
Description: Allows you to make changes to service durations calculus
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Schedule
Author: Ve Bailovity (Incsub)
*/

class App_Schedule_Durations {

	private $_duration_flag_changes_applied = false;
	private $_boundaries_flag_changes_applied = false;
	
	private function __construct () {}

	public static function serve () {
		$me = new App_Schedule_Durations;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('plugins_loaded', array($this, 'initialize'));

		add_filter('init', array($this, 'apply_duration_calculus'), 99);

		add_action('app-settings-time_settings', array($this, 'show_settings'));
		add_filter('app-options-before_save', array($this, 'save_settings'));
	}

	public function initialize () {
		global $appointments;
		$this->_data = $appointments->options;
	}

	public function apply_duration_calculus () {
		if (!defined('APP_USE_LEGACY_DURATION_CALCULUS')) {
			if (!empty($this->_data['duration_calculus']) && 'legacy' == $this->_data['duration_calculus']) {
				define('APP_USE_LEGACY_DURATION_CALCULUS', true, true);
				$this->_duration_flag_changes_applied = true;
			}
		}
		if (!defined('APP_USE_LEGACY_BOUNDARIES_CALCULUS')) {
			if (!empty($this->_data['boundaries_calculus']) && 'legacy' == $this->_data['boundaries_calculus']) {
				define('APP_USE_LEGACY_BOUNDARIES_CALCULUS', true, true);
				$this->_boundaries_flag_changes_applied = true;
			}
		}
	}

	public function save_settings ($options) {
		if (!empty($_POST['duration_calculus'])) $options['duration_calculus'] = $_POST['duration_calculus'];
		if (!empty($_POST['boundaries_calculus'])) $options['boundaries_calculus'] = $_POST['boundaries_calculus'];
		return $options;
	}

	public function show_settings () {
		$this->_show_legacy_duration_settings();
	}

	private function _show_legacy_duration_settings () {
		echo '<tr valign="top">' .
			'<th scope="row" >' . __('Time slot calculus method', 'appointments') . '</th>' .
		'';
		echo '<td colspan="2">';
		
		// Duration
		if (defined('APP_USE_LEGACY_DURATION_CALCULUS') && !$this->_duration_flag_changes_applied) {
			echo '<div class="error below-h2">' .
				'<p>' . __('Your duration calculus will be determined by the define value.', 'appointments') . '</p>' .
			'</div>';
		} else {
			$durations = array(
				'legacy' => __('Minimum time based appointment duration calculus <em>(legacy)</em>', 'appointments'),
				'service' => __('Service duration based calculus', 'appointments'),
			);
			$method = !empty($this->_data['duration_calculus']) ? $this->_data['duration_calculus'] : 'service';
			foreach ($durations as $key => $label) {
				$checked = checked($key, $method, false);
				echo "<input type='radio' name='duration_calculus' id='app-duration_calculus-{$key}' value='{$key}' {$checked} />" .
					'&nbsp;' .
					"<label for='app-duration_calculus-{$key}'>{$label}</label>" .
				'</br >';
			}
		}
		// Boundaries
		echo '<h4>' . __('Boundaries detection', 'appointments') . '</h4>';
		if (defined('APP_USE_LEGACY_BOUNDARIES_CALCULUS') && !$this->_boundaries_flag_changes_applied) {
			echo '<div class="error below-h2">' .
				'<p>' . __('Your boundaries calculus will be determined by the define value.', 'appointments') . '</p>' .
			'</div>';
		} else {
			$boundaries = array(
				'legacy' => __('Exact period matching <em>(legacy)</em>', 'appointments'),
				'detect_overlap' => __('Detect overlap', 'appointments'),
			);
			$method = !empty($this->_data['boundaries_calculus']) ? $this->_data['boundaries_calculus'] : 'detect_overlap';
			foreach ($boundaries as $key => $label) {
				$checked = checked($key, $method, false);
				echo "<input type='radio' name='boundaries_calculus' id='app-boundaries_calculus-{$key}' value='{$key}' {$checked} />" .
					'&nbsp;' .
					"<label for='app-boundaries_calculus-{$key}'>{$label}</label>" .
				'</br >';
			}
		}
		
		echo '</td>';
		echo '</tr>';
	}
}
App_Schedule_Durations::serve();
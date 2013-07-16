<?php
/*
Plugin Name: Service provider names
Description: Allows you to select how a service provider will be introduced to your customers.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Post Types
Author: Ve Bailovity (Incsub)
*/

class App_PostTypes_ServiceProviderNames {

	private $_data;

	private function __construct () {}

	public static function serve () {
		$me = new App_PostTypes_ServiceProviderNames;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('plugins_loaded', array($this, 'initialize'));
		
		add_filter('app_get_worker_name', array($this, 'filter_worker_names'), 10, 2);

		add_action('app-settings-advanced_settings', array($this, 'show_settings'));
		add_filter('app-options-before_save', array($this, 'save_settings'));
	}

	public function initialize () {
		global $appointments;
		$this->_data = $appointments->options;
	}

	public function filter_worker_names ($name, $worker_id) {
		if (!$worker_id) return $name;

		$default = !empty($this->_data['worker_name_format']) ? $this->_data['worker_name_format'] : false;
		$fallback = !empty($this->_data['worker_fallback_name_format']) ? $this->_data['worker_fallback_name_format'] : false;

		if (!$default && !$fallback) return $name;

		$new_name = $this->_format_to_name($default, $worker_id);
		if (empty($new_name)) $new_name = $this->_format_to_name($fallback, $worker_id);

		return !empty($new_name)
			? $new_name
			: $name
		;
	}

	private function _format_to_name ($format, $user_id) {
		$user = get_userdata($user_id);
		if (!is_object($user)) return false;

		$name = false;
		
		if ('display_name' == $format) {
			$name = !empty($user->display_name) ? $user->display_name : false;
		} else if ('nickname' == $format) {
			$name = !empty($user->nickname) ? $user->nickname : false;
		} else if ('first_last' == $format) {
			$name = sprintf(
				'%s %s',
				(!empty($user->first_name) ? $user->first_name : false),
				(!empty($user->last_name) ? $user->last_name : false)
			);
		} else if ('first_last_comma' == $format) {
			$name = sprintf(
				'%s, %s',
				(!empty($user->first_name) ? $user->first_name : false),
				(!empty($user->last_name) ? $user->last_name : false)
			);
		} else if ('last_first' == $format) {
			$name = sprintf(
				'%s %s',
				(!empty($user->last_name) ? $user->last_name : false),
				(!empty($user->first_name) ? $user->first_name : false)
			);
	} else if ('last_first_comma' == $format) {
			$name = sprintf(
				'%s, %s',
				(!empty($user->last_name) ? $user->last_name : false),
				(!empty($user->first_name) ? $user->first_name : false)
			);
		} else if ('appointments' == $format) {
			$name = !empty($user->app_name) ? $user->app_name : false;
		}

		return trim($name, ' ,');
	}

	public function save_settings ($options) {
		if (!empty($_POST['worker_name_format'])) $options['worker_name_format'] = sanitize_text_field($_POST['worker_name_format']);
		if (!empty($_POST['worker_fallback_name_format'])) $options['worker_fallback_name_format'] = sanitize_text_field($_POST['worker_fallback_name_format']);
		return $options;
	}

	public function show_settings () {
		$name_formats = array(
			'appointments' => __('As set in Appointments+ settings in profile (default)', 'appointments'),
			'display_name' => __('User display name', 'appointments'),
			'nickname' => __('Nickname', 'appointments'),
			'first_last' => __('First name, followed by last name, space-separated', 'appointments'),
			'first_last_comma' => __('First name, followed by last name, comma-separated', 'appointments'),
			'last_first' => __('Last name, followed by first name, space-separated', 'appointments'),
			'last_first_comma' => __('Last name, followed by first name, comma-separated', 'appointments'),
		);
		$default = !empty($this->_data['worker_name_format']) ? $this->_data['worker_name_format'] : false;
		$fallback = !empty($this->_data['worker_fallback_name_format']) ? $this->_data['worker_fallback_name_format'] : false;
		?>
		<tr valign="top">
			<th scope="row" ><?php _e('Worker display names', 'appointments')?></th>
			<td>
				<h5><?php _e('Default', 'appointments'); ?></h5>
				<select name="worker_name_format">
				<?php foreach ($name_formats as $format => $label) { ?>
					<option value="<?php esc_attr_e($format); ?>" <?php selected($format, $default); ?> >
						<?php echo $label; ?>
					</option>
				<?php } ?>
				</select>
				<br />
				<span class="description"><?php _e('This is the name format that will be used by default for your service providers.', 'appointments') ?></span>
			</td>
			<td>
				<h5><?php _e('Fallback', 'appointments'); ?></h5>
				<select name="worker_fallback_name_format">
				<?php foreach ($name_formats as $format => $label) { ?>
					<option value="<?php esc_attr_e($format); ?>" <?php selected($format, $fallback); ?> >
						<?php echo $label; ?>
					</option>
				<?php } ?>
				</select>
				<br />
				<span class="description"><?php _e('This is the name format that will be used as fallback, in case the default value is not set.', 'appointments') ?></span>
			</td>
		</tr>
		<?php
	}
}
App_PostTypes_ServiceProviderNames::serve();
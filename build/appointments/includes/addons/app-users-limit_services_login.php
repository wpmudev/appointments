<?php
/*
Plugin Name: Limit Services Login
Description: Allows you to choose which social services you allow your users to log in with.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Users
Requires: "Login required" setting to be set to "Yes".
Author: WPMU DEV
*/

class App_Users_LimitServicesLogin {

	private function __construct () {}

	public static function serve () {
		$me = new App_Users_LimitServicesLogin;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('plugins_loaded', array($this, 'initialize'));
		add_filter('app-scripts-api_l10n', array($this, 'inject_scripts'));
		add_action('app-settings-accessibility_settings', array($this, 'show_settings'));
		add_filter('app-options-before_save', array($this, 'save_settings'));
	}

	public function inject_scripts ($l10n) {
		$services = $this->_get_services();
		$selected = empty($this->_data['show_login_button'])
			? array_keys($services)
			: $this->_data['show_login_button']
		;
		$l10n['show_login_button'] = $selected;

		if (!empty($l10n['wordpress']) && !empty($this->_data['use_blogname_for_login'])) {
			$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
			$l10n['wordpress'] = sprintf(__('Login with %s', 'appointments'), $blogname);
			if (!empty($l10n['register'])) {
				$l10n['register'] = sprintf(__('Register with %s', 'appointments'), $blogname);
			}
		}
		return $l10n;
	}

	public function initialize () {
		global $appointments;
		$this->_data = $appointments->options;
	}

	public function save_settings ($options) {
		if (!empty($_POST['show_login_button'])) $options['show_login_button'] = stripslashes_deep($_POST['show_login_button']);
		$options['use_blogname_for_login'] = !empty($_POST['use_blogname_for_login']);
		return $options;
	}

	public function show_settings ($style) {
		$services = $this->_get_services();
		$selected = empty($this->_data['show_login_button'])
			? array_keys($services)
			: $this->_data['show_login_button']
		;
		?>
<tr valign="top" class="api_detail" <?php echo $style?>>
	<th scope="row" ><?php _e('Show login buttons', 'appointments')?></th>
	<td colspan="2">
	<?php foreach ($services as $service => $label) { ?>
		<input type="checkbox" name="show_login_button[]" id="slb-<?php esc_attr_e($service); ?>" value="<?php esc_attr_e($service); ?>" <?php echo (in_array($service, $selected) ? 'checked="checked"' : ''); ?> />
		<label for="slb-<?php esc_attr_e($service); ?>"><?php echo $label; ?></label>
		<br />
	<?php } ?>
	<hr />
	<div class="app-lsl-other_options">
		<label for="app-use_blogname_for_login">
			<input type="checkbox" name="use_blogname_for_login" id="app-use_blogname_for_login" value="1" <?php checked($this->_data['use_blogname_for_login'], 1); ?> />
			<?php printf(__('Use %s name for login button', 'appointments'), (is_multisite() ? __('network', 'appointments') : __('site', 'appointments'))); ?>
		</label>
	</div>
	</td>
</tr>
		<?php
	}

	private function _get_services () {
		return array(
			'facebook' => 'Facebook',
			'twitter' => 'Twitter',
			'google' => 'Google',
			'wordpress' => 'WordPress',
		);
	}
}
App_Users_LimitServicesLogin::serve();
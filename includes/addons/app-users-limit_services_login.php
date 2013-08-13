<?php
/*
Plugin Name: Limit Services Login
Description: Allows you to choose which social services you allow your users to log in with.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Users
Requires: "Login required" setting to be set to "Yes".
Author: Ve Bailovity (Incsub)
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
		$selected = empty($this->_data['show_login_button'])
			? array_keys()
			: $this->_data['show_login_button']
		;
		$l10n['show_login_button'] = $selected;
		return $l10n;
	}

	public function initialize () {
		global $appointments;
		$this->_data = $appointments->options;
	}

	public function save_settings ($options) {
		if (!empty($_POST['show_login_button'])) $options['show_login_button'] = stripslashes_deep($_POST['show_login_button']);
		return $options;
	}

	public function show_settings ($style) {
		$services = array(
			'facebook' => 'Facebook',
			'twitter' => 'Twitter',
			'google' => 'Google',
			'wordpress' => 'WordPress',
		);
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
	</td>
</tr>
		<?php
	}
}
App_Users_LimitServicesLogin::serve();
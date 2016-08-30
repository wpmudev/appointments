<?php
/*
Plugin Name: Administrative Permissions
Description: Allows you to select who can do what in your admin backend.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Users
Author: WPMU DEV
*/

class App_Users_AdminPermissions {

	private $_data;

	private function __construct () {}

	public static function serve () {
		$me = new App_Users_AdminPermissions;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('plugins_loaded', array($this, 'initialize'));

		add_action('appointments_settings_tab-main-section-advanced', array($this, 'show_settings'), 100);
		add_filter('app-options-before_save', array($this, 'save_settings'));

		// Do the job
		add_filter('app-capabilities-requested_capability', array($this, 'filter_requested_caps'), 10, 2);
	}

	public function initialize () {
		global $appointments;
		$this->_data = $appointments->options;
	}

	public function filter_requested_caps ($capability, $context) {
		if (is_super_admin()) return $capability;
		$context = App_Roles::get_context($context);

		if (!empty($this->_data['roles'][$context])) {
			$context_roles = $this->_data['roles'][$context];
			if (count($context_roles) == 1 && empty($context_roles[0])) return $capability;
			return $context_roles;
		}
		return $capability;
	}

	function save_settings ($options) {
		if (!empty($_POST['roles'])) $options['roles'] = $_POST['roles'];
		return $options;
	}

	public function show_settings () {
		$roles = App_Roles::get_all_wp_roles();
		$contexts = App_Roles::get_all_contexts();
		?>
		<h3><?php _e('Appointments role access', 'appointments') ?></h3>
		<table class="form-table">
			<?php foreach($contexts as $ctx => $ctx_label): ?>
				<?php if (App_Roles::CTX_GLOBAL == $ctx) continue; ?>
				<tr>
					<?php $context_roles = !empty($this->_data['roles'][$ctx]) ? $this->_data['roles'][$ctx] : array(); ?>
					<th><label for="roles-<?php esc_attr_e($ctx);?>"><?php echo $ctx_label; ?></label></th>
					<td scope="row">
						<select id="roles-<?php esc_attr_e($ctx);?>" name="roles[<?php esc_attr_e($ctx);?>][]" multiple="multiple">
							<option value="" <?php echo (empty($context_roles) ? 'selected="selected"' : ''); ?> ><?php _e('Default', 'appointments'); ?></option>
						<?php foreach ($roles as $role => $label) { ?>
							<option value="<?php esc_attr_e($role); ?>"
								<?php echo (in_array($role, $context_roles) ? 'selected="selected"' : ''); ?>
							><?php echo $label; ?></option>
						<?php } ?>
						</select>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}
}
if (is_admin()) App_Users_AdminPermissions::serve();
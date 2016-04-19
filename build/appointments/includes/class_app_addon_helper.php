<?php

class App_AddonHandler {
	
	private function __construct () {
		define('APP_PLUGIN_ADDONS_DIR', APP_PLUGIN_DIR . '/includes/addons');
		$this->_load_active_plugins();
	}
	
	public static function serve () {
		$me = new App_AddonHandler;
		$me->_add_hooks();
	}
	
	private function _add_hooks () {
		add_action('wp_ajax_app_activate_plugin', array($this, 'json_activate_plugin'));
		add_action('wp_ajax_app_deactivate_plugin', array($this, 'json_deactivate_plugin'));
	}
	
	private function _load_active_plugins () {
		$active = $this->get_active_plugins();

		foreach ($active as $plugin) {
			$path = self::plugin_to_path($plugin);
			if (!file_exists($path)) continue;
			else @require_once($path);
		}
	}
	
	function json_activate_plugin () {
		$status = $this->_activate_plugin($_POST['plugin']);
		echo json_encode(array(
			'status' => $status ? 1 : 0,
		));
		exit();
	}

	function json_deactivate_plugin () {
		$status = $this->_deactivate_plugin($_POST['plugin']);
		echo json_encode(array(
			'status' => $status ? 1 : 0,
		));
		exit();
	}

	public static function get_active_plugins () {
		$active = get_option('app_activated_plugins');
		$active = $active ? $active : array();

		return $active;
	}
	
	public static function is_plugin_active ($plugin) {
		$active = self::get_active_plugins();
		return in_array($plugin, $active);
	}

	public static function get_all_plugins () {
		$all = glob(APP_PLUGIN_ADDONS_DIR . '/*.php');
		$all = $all ? $all : array();
		$ret = array();
		foreach ($all as $path) {
			$ret[] = pathinfo($path, PATHINFO_FILENAME);
		}
		return $ret;
	}

	public static function plugin_to_path ($plugin) {
		$plugin = str_replace('/', '_', $plugin);
		return APP_PLUGIN_ADDONS_DIR . '/' . "{$plugin}.php";
	}

	public static function get_plugin_info ($plugin) {
		$path = self::plugin_to_path($plugin);
		$default_headers = array(
			'Name' => 'Plugin Name',
			'Author' => 'Author',
			'Description' => 'Description',
			'Plugin URI' => 'Plugin URI',
			'Version' => 'Version',
			'Requires' => 'Requires',
			'Detail' => 'Detail'
		);
		return get_file_data($path, $default_headers, 'plugin');
	}

	private function _activate_plugin ($plugin) {
		$active = self::get_active_plugins();
		if (in_array($plugin, $active)) return true; // Already active

		$active[] = $plugin;

		appointments_clear_cache();
		return update_option('app_activated_plugins', $active);
	}

	private function _deactivate_plugin ($plugin) {
		$active = self::get_active_plugins();
		if (!in_array($plugin, $active)) return true; // Already deactivated

		$key = array_search($plugin, $active);
		if ($key === false) return false; // Haven't found it

		unset($active[$key]);
		appointments_clear_cache();
		return update_option('app_activated_plugins', $active);
	}

	private static function to_plugin_requirements ($plugin, $req_string) {
		$requirements = array_map('trim', explode(',', $req_string));
		return $requirements;
	}
	
	public static function create_addon_settings () {
		$all = self::get_all_plugins();
		$active = self::get_active_plugins();
		$sections = array('thead');

		echo "<table class='widefat' id='app_addons_hub'>";
		echo '<thead>';
		echo '<tr>';
		echo '<th width="30%">' . __('Name', 'appointments') . '</th>';
		echo '<th>' . __('Description', 'appointments') . '</th>';
		echo '</tr>';
		echo '<thead>';
		echo "<tbody>";
		foreach ($all as $plugin) {
			$plugin_data = self::get_plugin_info($plugin);
			if (!@$plugin_data['Name']) continue; // Require the name
			$is_active = in_array($plugin, $active);
			$is_beta = false;
			if (!empty($plugin_data['Version']) && preg_match('/BETA/i', $plugin_data['Version'])) {
				$plugin_data['Version'] = '<span class="app-beta-version">' . $plugin_data['Version'] . '</span>';
				$is_beta = true;
			}
			echo "<tr>";
			echo "<td width='30%'>";
			echo '<b id="' . esc_attr($plugin) . '">' . $plugin_data['Name'] . '</b>';
			echo "<br />";
			echo ($is_active
				?
				'<a href="#deactivate" class="app_deactivate_plugin" app:plugin_id="' . esc_attr($plugin) . '">' . __('Deactivate', 'appointments') . '</a>'
				:
				'<a href="#activate" class="app_activate_plugin ' . ($is_beta ? "app-beta" : '') . '" app:plugin_id="' . esc_attr($plugin) . '">' . __('Activate', 'appointments') . '</a>'
			);
			echo "</td>";
			echo '<td>' .
				$plugin_data['Description'] .
				'<br />' .
				sprintf(__('Version %s', 'appointments'), $plugin_data['Version']) .
				'&nbsp;|&nbsp;' .
				sprintf(__('by %s', 'appointments'), '<a href="' . $plugin_data['Plugin URI'] . '">' . $plugin_data['Author'] . '</a>');
			/*
			if ( $plugin_data['Detail'] )
				echo '&nbsp;' . $tips->add_tip( $plugin_data['Detail'] );
			*/
			if (!empty($plugin_data['Requires'])) {
				echo '<div class="app-addon-requires">' . __('Requires:', 'appointments') . ' ';
				$requirements = self::to_plugin_requirements($plugin, $plugin_data['Requires']);
				echo join(', ', $requirements);
				echo '</div>';
			}
			echo '</td>';
			echo "</tr>";
		}
		echo "</tbody>";
		echo "</table>";

		$beta_warning_nag = esc_js(__("The add-on you are about to activate is in Beta state. This means that it's still under active development and might be rough around the edges.\nAre you sure you want to proceed?", 'appointments'));

		echo <<<EOWdcpPluginJs
<script type="text/javascript">
(function ($) {
$(function () {
	$(".app_activate_plugin").click(function () {
		var me = $(this);
		if (me.is(".app-beta")) {
			if (!confirm("{$beta_warning_nag}")) return false;
		}
		var plugin_id = me.attr("app:plugin_id");
		$.post(ajaxurl, {"action": "app_activate_plugin", "plugin": plugin_id}, function (data) {
			window.location = window.location;
		});
		return false;
	});
	$(".app_deactivate_plugin").click(function () {
		var me = $(this);
		var plugin_id = me.attr("app:plugin_id");
		$.post(ajaxurl, {"action": "app_deactivate_plugin", "plugin": plugin_id}, function (data) {
			window.location = window.location;
		});
		return false;
	});
});
})(jQuery);
</script>
EOWdcpPluginJs;
	}
}

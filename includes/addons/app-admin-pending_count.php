<?php
/*
Plugin Name: Pending Appointments count notification
Description: Adds a visual notification to your Appointments menu item and periodically syncs this count with the server.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Admin
Author: WPMU DEV
*/

class App_Admin_PendingCount {

	const HB_KEY = 'aapc-get_count';
	const UPPER_LIMIT = 10;
	
	private $_core;

	private function __construct () {}

	public static function serve () {
		$me = new App_Admin_PendingCount;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('plugins_loaded', array($this, 'initialize'));

		add_action('admin_footer', array($this, 'add_script'));
		add_filter('heartbeat_received', array($this, 'hb_send_response'), 10, 3);

		add_action('app-admin-admin_pages_added', array($this, 'update_menu_item'));
	}

	public function update_menu_item ($page) {
		if (!current_user_can(App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_APPOINTMENTS))) return false;

		$count = $this->_get_pending_items_count();
		if (empty($count)) return false;

		global $menu;
		foreach ($menu as $idx => $item) {
			if (empty($item[5])) continue;
			if ($page !== $item[5]) continue;
			$menu[$idx][0] .= sprintf($this->_get_pending_template(), $count);
		}
		return $menu;
	}

	public function hb_send_response ($response, $data, $screen_id) {
		if (!current_user_can(App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_APPOINTMENTS))) return $response;
		if (empty($data[self::HB_KEY])) return $response;
		
		$count = $this->_get_pending_items_count();

		$response[self::HB_KEY] = array(
			'count' => (int)$count,
		);
		return $response;
	}

	public function initialize () {
		global $appointments;
		$this->_core = $appointments;
	}

	public function add_script () {
		if (!current_user_can(App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_APPOINTMENTS))) return false;

		$key = esc_js(self::HB_KEY);
		$tpl = json_encode($this->_get_pending_template());
		echo <<<EO_AAPC_JS
<script>
;(function ($) {

function update_interface (data) {
	var root = $("#toplevel_page_appointments"),
		target = root.find(".wp-menu-name"),
		count = data.count || 0
	;
	if (!target.length) return false;
	target.find(".awaiting-mod").remove();
	if (count > 0) target.append({$tpl}.replace(/%d/g, count));
}

function set_heartbeat () {
	wp.heartbeat.enqueue('{$key}', {count: "pending"}, false);
}

function init () {
	set_heartbeat();
	$(document).on('heartbeat-tick.{$key}', function (e, data) {
		set_heartbeat();
		if (data && data.hasOwnProperty && data.hasOwnProperty('{$key}')) {
			update_interface(data['{$key}']);
		}
	});
}
$(init);
})(jQuery);
</script>
EO_AAPC_JS;
	}

	private function _get_pending_items_count () {
		return count($this->_core->get_admin_apps('pending', 0, self::UPPER_LIMIT));
	}

	private function _get_pending_template () {
		return '<span class="awaiting-mod"><span class="pending-count">%d</span></span>';
	}
}
if (is_admin()) App_Admin_PendingCount::serve();
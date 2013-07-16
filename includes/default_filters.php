<?php

if (!(defined('APP_DISALLOW_META_WRAPPERS') && APP_DISALLOW_META_WRAPPERS)) {
	function _app_wrap_meta_output ($out) {
		if (!$out) return $out;
		return '<div class="app-settings-column_meta_info">' .
			'<div class="app-settings-column_meta_info-content" style="display:none">' . $out . '</div>' .
			'<div>' .
				'<a href="#toggle" class="app-settings-column_meta_info-toggle" ' .
					'data-off="' . esc_attr(__('More Info', 'appointments')) . '" ' .
					'data-on="' . esc_attr(__('Less Info', 'appointments')) . '" ' .
				'>' . __('More Info', 'appointments') . '</a>' .
			'</div>' .
		'</div>';
	}
	add_filter('app-settings-services-service-name', '_app_wrap_meta_output', 9991);
	add_filter('app-settings-workers-worker-name', '_app_wrap_meta_output', 9991);
}
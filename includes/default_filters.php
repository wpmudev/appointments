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

if (defined('APP_GCAL_CLIENT_TEMP_DIR_AUTO_LOOKUP') && APP_GCAL_CLIENT_TEMP_DIR_AUTO_LOOKUP) {
	/**
	 * Wrapper for Google Client cache filepath + open_basedir restriction resolution.
	 */
	function _app_gcal_client_temp_dir_lookup ($params) {
		if (!function_exists('get_temp_dir')) return $params;
		$params['ioFileCache_directory'] = get_temp_dir() . 'Google_Client';
		return $params;
	}
	add_filter('app-gcal-client_parameters', '_app_gcal_client_temp_dir_lookup');
}
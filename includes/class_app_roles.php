<?php

class App_Roles {

	const CTX_GLOBAL = 'global';
	const CTX_STAFF = 'staff';
	const CTX_TUTORIAL = 'tutorial';
	const CTX_PAGE_APPOINTMENTS = 'page_appointments';
	const CTX_PAGE_TRANSACTIONS = 'page_transactions';
	const CTX_PAGE_SETTINGS = 'page_settings';
	const CTX_PAGE_SHORTCODES = 'page_shortcodes';
	const CTX_PAGE_FAQ = 'page_faq';
	const CTX_DASHBOARD = 'dashboard';

	private static $_contexts = array();

	public static function get_all_contexts () {
		return apply_filters('app-capabilities-contexts', array(
			self::CTX_STAFF => __('Staff view options', 'appointments'),
			self::CTX_TUTORIAL => __('Tutorial', 'appointments'),
			self::CTX_PAGE_APPOINTMENTS => __('Appointments page', 'appointments'),
			self::CTX_PAGE_TRANSACTIONS => __('Transactions page', 'appointments'),
			self::CTX_PAGE_SETTINGS => __('Settings page', 'appointments'),
			self::CTX_PAGE_SHORTCODES => __('Settings page', 'appointments'),
			self::CTX_PAGE_FAQ => __('FAQ page', 'appointments'),
			self::CTX_DASHBOARD => __('Dashboard widget', 'appointments'),
			self::CTX_GLOBAL => __('Misc', 'appointments'),
		));
	}

	public static function get_all_wp_roles () {
		global $wp_roles;
		if (!isset($wp_roles)) $wp_roles = new WP_Roles();
		return $wp_roles->get_names();
	}

	public static function get_context ($ctx, $fallback=false) {
		$fallback = $fallback ? $fallback : self::CTX_GLOBAL;
		$all = array_keys(self::get_all_contexts());
		return in_array($ctx, $all)
			? $ctx
			: $fallback
		;
	}

	public static function current_user_can ($role, $context=false) {
		$context = self::get_context($context);
		$requested_caps = apply_filters(
			"app-capabilities-requested_capability",
			$role,
			$context
		);
		$user_can = false;

		if (!empty($requested_caps)) $user_can = is_array($requested_caps)
			? self::_current_user_can_any($requested_caps)
			: current_user_can($requested_caps)
		;

		return apply_filters('app-capabilities-current_user_can', $user_can, $role, $requested_caps, $context);
	}

	public static function get_capability ($capability, $context=false) {
		$context = self::get_context($context);
		$requested_caps = apply_filters(
			"app-capabilities-requested_capability",
			$capability,
			$context
		);

		if (empty($requested_caps)) return $capability; // Something went wrong, bail out
		if ($capability == $requested_caps) return $capability; // All the same, give it a rest already
		
		if (!is_array($requested_caps)) return $requested_caps; // It's a regular WP cap, let's fall through

		// We're here? Cap array. Let's see if we have anything that fits.
		$user_cap = self::_current_user_can_any($requested_caps);
		return $user_cap
			? $user_cap
			: $capability
		;
	}

	private static function _current_user_can_any ($caps) {
		foreach ($caps as $cap) if ($cap && current_user_can($cap)) return $cap;
		return false;
	}
}
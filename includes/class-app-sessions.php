<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Appointments_Sessions
 *
 * Manages session data for users
 */
class Appointments_Sessions {

	/**
	 * Get the appointments IDs for the current visitor
	 *
	 * A visitor is a user that is logged out
	 *
	 * @return array
	 */
	public static function get_current_visitor_appointments() {

		if ( ! self::is_visitor_appointments_cookie_set() ) {
			return array();
		}

		$apps = maybe_unserialize( stripslashes( $_COOKIE['wpmudev_appointments'] ) );
		if ( ! is_array( $apps ) ) {
			return array();
		}

		return $apps;
	}

	/**
	 * Check if the appointments cookie is set for the visitor
	 *
	 * @return bool
	 */
	public static function is_visitor_appointments_cookie_set() {
		return isset( $_COOKIE['wpmudev_appointments'] );
	}

	/**
	 * Set a list of Appointments IDs for the current visitor
	 *
	 * @param $apps
	 */
	public static function set_visitor_appointments( $apps ) {
		if ( ! is_array( $apps ) ) {
			return;
		}

		// Prevent duplicates
		$apps = array_unique( $apps );

		@setcookie("wpmudev_appointments", maybe_serialize( $apps ), self::get_cookie_expiration_time(), self::get_cookie_path(), self::get_cookie_domain() );
	}

	/**
	 * Set a current visitor data like address, name...
	 *
	 * @param array $data
	 */
	public static function set_visitor_personal_data( $data ) {
		if ( ! is_array( $data ) ) {
			return;
		}

		@setcookie("wpmudev_appointments_userdata", maybe_serialize( $data ), self::get_cookie_expiration_time(), self::get_cookie_path(), self::get_cookie_domain() );
	}

	/**
	 * Get the current visitor data like address, name...
	 *
	 * @return array
	 */
	public static function get_visitor_personal_data() {
		if ( ! self::is_visitor_personal_data_cookie_set() ) {
			return array();
		}

		$data = maybe_unserialize( stripslashes( $_COOKIE['wpmudev_appointments_userdata'] ) );
		if ( ! is_array( $data ) ) {
			return array();
		}

		return $data;
	}

	/**
	 * Check if the personal data cookie is set for the visitor
	 *
	 * @return bool
	 */
	public static function is_visitor_personal_data_cookie_set() {
		return isset( $_COOKIE['wpmudev_appointments_userdata'] );
	}

	/**
	 * Clear visitor appointments list and personal data cookies
	 */
	public static function clear_visitor_data() {
		$drop = current_time( 'timestamp' ) - 3600;
		@setcookie("wpmudev_appointments", "", $drop, self::get_cookie_path(), self::get_cookie_domain());
		@setcookie( "wpmudev_appointments_userdata", "", $drop, self::get_cookie_path(), self::get_cookie_domain() );
	}


	/**
	 * Get cookie expiration time for Appointments Sessions in seconds
	 *
	 * @return integer
	 */
	public static function get_cookie_expiration_time() {
		// 365 days by default
		$expire = current_time( 'timestamp' ) + 3600 * 24 * ( appointments_get_option( 'app_limit' ) + 365 );
		return apply_filters( 'app_cookie_time', $expire );
	}

	/**
	 * Get the cookie domain fro the current site
	 */
	public static function get_cookie_domain() {
		return defined( 'COOKIEDOMAIN' ) ? COOKIEDOMAIN : '';
	}

	/**
	 * Get the cookie domain fro the current site
	 */
	public static function get_cookie_path() {
		return defined( 'COOKIEPATH' ) ? COOKIEPATH : '/';
	}

}
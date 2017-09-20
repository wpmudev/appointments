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

	public static function get_current_visitor_key() {
		$key = $set_cookie = false;
		$length = 64;
		if ( ! self::is_visitor_appointments_cookie_set() ) {
			$key = wp_generate_password( $length, false, false );
				$set_cookie = true;
		}
		if ( false === $key ) {
			$key = $_COOKIE['wpmudev_appointments'];
			if ( $length != strlen( $key ) ) {
				$key = wp_generate_password( $length, false, false );
				$set_cookie = true;
			}
		}
		if ( $set_cookie ) {
			@setcookie( 'wpmudev_appointments', $key, self::get_cookie_expiration_time(), self::get_cookie_path(), self::get_cookie_domain() );
		}
		return $key;
	}

	/**
	 * Get the appointments IDs for the current visitor
	 *
	 * A visitor is a user that is logged out
	 *
	 * @return array
	 */
	public static function get_current_visitor_appointments() {
		$key = 'appointments_'.self::get_current_visitor_key();
		$apps = get_transient( $key );
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
		if ( isset( $_COOKIE['wpmudev_appointments_userdata'] ) ) {
			@setcookie( 'wpmudev_appointments_userdata', null, -1, self::get_cookie_path(), self::get_cookie_domain() );
		}
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
		$key = 'appointments_'.self::get_current_visitor_key();
		// Prevent duplicates
		$apps = array_unique( $apps );
		set_transient( $key, $apps, self::get_cookie_expiration_time() );
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
		$key = 'userdata_'.self::get_current_visitor_key();
		set_transient( $key, $data, self::get_cookie_expiration_time() );
	}

	/**
	 * Get the current visitor data like address, name...
	 *
	 * @return array
	 */
	public static function get_visitor_personal_data() {
		$key = 'userdata_'.self::get_current_visitor_key();
		$data = get_transient( $key );
		if ( ! is_array( $data ) ) {
			return array();
		}
		return $data;
	}

	/**
	 * Clear visitor appointments list and personal data cookies
	 */
	public static function clear_visitor_data() {
		@setcookie( 'wpmudev_appointments', null, -1, self::get_cookie_path(), self::get_cookie_domain() );
	}


	/**
	 * Get cookie expiration time for Appointments Sessions in seconds
	 *
	 * @return integer
	 */
	public static function get_cookie_expiration_time() {
		// 365 days by default
		$expire = current_time( 'timestamp' ) + DAY_IN_SECONDS * ( appointments_get_option( 'app_limit' ) + 365 );
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

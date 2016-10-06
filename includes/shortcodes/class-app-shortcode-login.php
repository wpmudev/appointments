<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'App_Shortcode_Login' ) ) {
	/**
	 * Front-end login.
	 */
	class App_Shortcode_Login extends App_Shortcode {
		public function __construct () {
			$this->name = _x( 'Login', 'Login Shortcode', 'appointments' );
			$this->_defaults = array(
				'login_text' => array(
					'type' => 'text',
					'name' => __( 'Login text', 'appointments' ),
					'value' => __('Please click here to login:', 'appointments'),
					'help' => __('Text above the login buttons, proceeded by a login link. Default: "Please click here to login:"', 'appointments'),
				),
				'redirect_text' => array(
					'type' => 'text',
					'name' => __( 'Redirect text', 'appointments' ),
					'value' => __('Login required to make an appointment. Now you will be redirected to login page.', 'appointments'),
					'help' => __('Javascript text if front end login is not set and user is redirected to login page', 'appointments'),
				),
			);
		}

		public function get_usage_info () {
			return __('Inserts front end login buttons for Facebook, Twitter and WordPress.', 'appointments');
		}

		public function process_shortcode ($args=array(), $content='') {
			extract(wp_parse_args($args, $this->_defaults_to_args()));

			global $appointments;

			$ret  = '';
			$ret .= '<div class="appointments-login">';
			if ( !is_user_logged_in() && $appointments->options["login_required"] == 'yes' ){
				$ret .= $login_text. " ";
				$ret .= '<a href="javascript:void(0)" class="appointments-login_show_login" >'. __('Login', 'appointments') . '</a>';
			}
			$ret .= '<div class="appointments-login_inner">';
			$ret .= '</div>';
			$ret .= '</div>';

			$script  = '';
			$script .= "$('.appointments-login_show_login').click(function(){";
			if ( !@$appointments->options["accept_api_logins"] ) {
				$script .= 'var app_redirect=confirm("'.esc_js($redirect_text).'");';
				$script .= ' if(app_redirect){';
				$script .= 'window.location.href= "'.wp_login_url( ).'";';
				$script .= '}';
			}
			else {
				$script .= '$(".appointments-login_link-cancel").focus();';
			}
			$script .= "});";

			$appointments->add2footer( $script );

			return $ret;
		}
	}
}
<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'App_Shortcode_Paypal' ) ) {
	/**
	 * Adds PayPal payment forms.
	 */
	class App_Shortcode_Paypal extends App_Shortcode {
		public function __construct() {
			$this->name = __( 'Paypal', 'appointments' );
			$this->_defaults = array(
				'item_name'   => array(
					'type' => 'text',
					'name' => __( 'Item Name', 'appointments' ),
					'value'   => __( 'Payment for SERVICE', 'appointments' ),
					'help'    => __( 'Item name that will be seen on Paypal. Default: "Payment for SERVICE" if deposit is not asked, "Deposit for SERVICE" if deposit is asked', 'appointments' ),
				),
				'button_text' => array(
					'type' => 'text',
					'name' => __( 'Button Text', 'appointments' ),
					'value'   => __( 'Please confirm PRICE CURRENCY payment for SERVICE', 'appointments' ),
					'help'    => __( 'Text that will be displayed on Paypal button. Default: "Please confirm PRICE CURRENCY payment for SERVICE"', 'appointments' ),
				),
			);
		}

		public function get_usage_info() {
			return '' .
			       __( 'Inserts PayPal Pay button and form.', 'appointments' ) .
			       '<br />' .
			       __( 'For the shortcode parameters, you can use SERVICE, PRICE, CURRENCY placeholders which will be replaced by their real values.', 'appointments' ) .
			       '';
		}

		public function process_shortcode( $args = array(), $content = '' ) {
			extract( wp_parse_args( $args, $this->_defaults_to_args() ) );

			global $post, $current_user, $appointments;

			if ( 'Payment for SERVICE' == $item_name && ( ( isset( $appointments->options["percent_deposit"] ) && $appointments->options["percent_deposit"] )
			                                              || ( isset( $appointments->options["fixed_deposit"] ) && $appointments->options["fixed_deposit"] ) )
			) {
				$item_name = __( 'Deposit for SERVICE', 'appointments' );
			}

			$item_name = apply_filters( 'app_paypal_item_name', $item_name );

			// Let's be on the safe side and select the default currency
			if ( empty( $appointments->options['currency'] ) ) {
				$appointments->options['currency'] = 'USD';
			}

			if ( ! isset( $appointments->options["return"] ) || ! $return = get_permalink( $appointments->options["return"] ) ) {
				$return = get_permalink( $post->ID );
			}
			// Never let an undefined page, just in case
			if ( ! $return ) {
				$return = home_url();
			}

			$return = apply_filters( 'app_paypal_return', $return );

			$cancel_return = apply_filters( 'app_paypal_cancel_return', get_option( 'home' ) );

			$form = '';
			$form .= '<div class="appointments-paypal">';

			if ( $appointments->options['mode'] == 'live' ) {
				$form .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">';
			} else {
				$form .= '<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">';
			}
			$form .= '<input type="hidden" name="business" value="' . esc_attr( $appointments->options['merchant_email'] ) . '" />';
			$form .= '<input type="hidden" name="cmd" value="_xclick">';
			$form .= '<input type="hidden" class="app_item_name" name="item_name" value="' . $item_name . '" />';
			$form .= '<input type="hidden" name="no_shipping" value="1" />';
			$form .= '<input type="hidden" name="currency_code" value="' . $appointments->options['currency'] . '" />';
			$form .= '<input type="hidden" name="return" value="' . $return . '" />';
			$form .= '<input type="hidden" name="cancel_return" value="' . $cancel_return . '" />';
			$form .= '<input type="hidden" name="notify_url" value="' . admin_url( 'admin-ajax.php?action=app_paypal_ipn' ) . '" />';
			$form .= '<input type="hidden" name="src" value="0" />';
			$form .= '<input class="app_custom" type="hidden" name="custom" value="" />';
			$form .= '<input class="app_amount" type="hidden" name="amount" value="" />';
			$form .= '<input class="app_submit_btn';
			// Add a class if user not logged in. May be required for addons.
			if ( ! is_user_logged_in() ) {
				$form .= ' app_not_loggedin';
			}

			$display_currency = App_Template::get_currency_symbol( $appointments->options["currency"] );
			$form .= '" type="submit" name="submit_btn" value="' . str_replace( array( "CURRENCY" ), array( $display_currency ), $button_text ) . '" />';

			// They say Paypal uses this for tracking. I would prefer to remove it if it is not mandatory.
			$form .= '<img style="display:none" alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" />';

			$form = apply_filters( 'app_paypal_additional_fields', $form, $appointments->location, $appointments->service, $appointments->worker );

			$form .= '</form>';

			$form .= '</div>';

			return $form;
		}
	}
}
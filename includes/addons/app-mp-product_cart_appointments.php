<?php
/*
Plugin Name: Appointments in product cart
Description: Control how your appointments show in the product cart.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Integration
Requires: <a href="https://premium.wpmudev.org/project/e-commerce/">MarketPress</a>
Author: WPMU DEV
*/

class App_Mp_ProductCartDisplay {

	/** @var  Appointments */
	private $_core;
	private $_data = array();
	private $_has_marketpress = false;

	private function __construct () {}

	public static function serve () {
		$me = new App_Mp_ProductCartDisplay;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('plugins_loaded', array($this, 'initialize'));

		add_filter('app_mp_product_name_in_cart', array($this, 'apply_changes'), 10, 5);
	}

	public function apply_changes ($name, $service, $worker, $start, $app) {
		$options = appointments_get_options();
		if ( empty( $options['cart_name_format'] ) ) {
			return $name;
		}
		$codec = new App_Macro_Codec($app);
		return $codec->expand($options['cart_name_format'], App_Macro_Codec::FILTER_TITLE);
	}

	public function auto_add_to_cart () {
			global $post;
			if (!$this->_core->is_app_mp_page($post)) return false;
			?>
<script>
(function ($) {

$(document).on("app-confirmation-response_received", function (e, response) {
	if (!(response && response.mp && 1 == response.mp)) return false;
	$(".mp_buy_form").hide().submit();
});

})(jQuery);
</script>
		<?php
	}
	
	public function initialize () {
		global $appointments;
		$this->_core            = $appointments;
		$this->_has_marketpress = class_exists( 'MarketPress' );

		$options = appointments_get_options();

		if ( $this->_has_marketpress && ! empty( $options['auto_add_to_cart'] ) ) {
			if ( defined( 'MP_VERSION' ) && version_compare( MP_VERSION, '3.0', '<' ) ) {
				add_action( 'wp_footer', array( $this, 'auto_add_to_cart' ) );
			}
		}
	}

}
App_Mp_ProductCartDisplay::serve();
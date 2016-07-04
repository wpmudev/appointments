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
	private $_has_marketpress = false;

	private function __construct () {}

	public static function serve () {
		$me = new App_Mp_ProductCartDisplay;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('plugins_loaded', array($this, 'initialize'));

		add_filter('app_mp_product_name_in_cart', array($this, 'apply_changes'), 10, 5);
		add_action( 'app-settings-payment_settings-marketpress', array( $this, 'show_settings' ) );
	}

	/**
	 * Add default MP options to Appointments options
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public function default_options( $options ) {
		$mp_options = array(
			'cart_name_format' => '',
			'auto_add_to_cart' => false,
		);
		return array_merge( $options, $mp_options );
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

	public function show_settings() {
		$options = appointments_get_options();
		$codec  = new App_Macro_Codec;
		$macros = join( '</code>, <code>', $codec->get_macros() );
		?>
		<tr>
			<th scope="row"><label for="app-cart_name_format"><?php _e('Appointment in shopping cart format', 'appointments'); ?></label></th>
			<td colspan="2">
				<input type="text" class="widefat" name="cart_name_format" id="app-cart_name_format" value="<?php echo esc_attr( $options['cart_name_format'] ); ?>" />
				<span class="description"><?php printf(__('You can use these macros: <code>%s</code>', 'appointments'), $macros); ?></span>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="app-auto_add_to_cart"><?php _e('Auto-add appointments into cart', 'appointments'); ?></label></th>
			<td colspan="2">
				<input type="hidden" name="auto_add_to_cart" value="" />
				<input type="checkbox" name="auto_add_to_cart" id="app-auto_add_to_cart" value="1" <?php checked( $options['auto_add_to_cart'] ); ?> />
			</td>
		</tr>
		<?php
	}

}
App_Mp_ProductCartDisplay::serve();
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

		add_action('app-settings-payment_settings-marketpress', array($this, 'show_settings'));
		add_filter('app-options-before_save', array($this, 'save_settings'));

		add_action( 'wp_ajax_mp_update_cart', array( $this, 'update_apps_on_cart_change' ) );
		add_action( 'wp_ajax_nopriv_mp_update_cart', array( $this, 'update_apps_on_cart_change' ) );

		add_action( 'app_remove_expired', array( $this, 'remove_app_from_cart_when_expired' ), 10, 2 );

	}

	public function apply_changes ($name, $service, $worker, $start, $app) {
		if (empty($this->_data['cart_name_format'])) return $name;
		$codec = new App_Macro_Codec($app);
		return $codec->expand($this->_data['cart_name_format'], App_Macro_Codec::FILTER_TITLE);
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
		$this->_core = $appointments;
		$this->_data = $appointments->options;
		$this->_has_marketpress = class_exists('MarketPress');

		if ($this->_has_marketpress && !empty($this->_data['auto_add_to_cart'])) {
			if (defined('MP_VERSION') && version_compare(MP_VERSION, '3.0', '<')) add_action('wp_footer', array($this, 'auto_add_to_cart'));
		}
	}

	public function save_settings ($options) {
		if (!empty($_POST['cart_name_format'])) $options['cart_name_format'] = wp_strip_all_tags(stripslashes_deep($_POST['cart_name_format']));
		$options['auto_add_to_cart'] = !empty($_POST['auto_add_to_cart']);
		return $options;
	}

	public function show_settings () {
		if ($this->_has_marketpress) {
			$codec = new App_Macro_Codec;
			$macros = join('</code>, <code>', $codec->get_macros());

			$cart_name_format = isset( $this->_data['cart_name_format'] ) ? $this->_data['cart_name_format'] : '';
			?>
<tr class="payment_row" <?php if ( $this->_data['payment_required'] != 'yes' ) echo 'style="display:none"';?>>
	<th scope="row"><?php _e('Appointment in shopping cart format', 'appointments'); ?></th>
	<td colspan="2">
		<input type="text" class="widefat" name="cart_name_format" id="app-cart_name_format" value="<?php echo $cart_name_format; ?>" />
		<span class="description"><?php printf(__('You can use these macros: <code>%s</code>', 'appointments'), $macros); ?></span>
	</td>
</tr>
<tr class="payment_row" <?php if ( $this->_data['payment_required'] != 'yes' ) echo 'style="display:none"';?>>
	<th scope="row"><?php _e('Auto-add appointments into cart', 'appointments'); ?></th>
	<td colspan="2">
		<input type="hidden" name="auto_add_to_cart" value="" />
		<input type="checkbox" name="auto_add_to_cart" id="app-auto_add_to_cart" value="1" <?php echo (!empty($this->_data['auto_add_to_cart']) ? 'checked="checked"' : ''); ?> />
	</td>
</tr>
			<?php
		}
	}


	public function update_apps_on_cart_change(){

		if( mp_get_post_value( 'cart_action' ) != 'remove_item' && mp_get_post_value( 'cart_action' ) != 'undo_remove_item' ) return;

		$product_id = mp_get_post_value( 'product', null );
			
		if ( mp_get_post_value( 'cart_action' ) != 'empty_cart' && is_null( $product_id ) ) {
			wp_send_json_error();
		}


		if ( is_array( $product_id ) ) {
			
			$product_id = mp_arr_get_value( 'product_id', $product_id );

		}


		$app_id = get_post_meta( $product_id, 'name', true );

		if( !is_numeric( $app_id ) ) return;

		$cart_action = mp_get_post_value( 'cart_action' );

		switch ( $cart_action ){
			case 'remove_item': appointments_update_appointment_status( $app_id, 'removed' ); break;
			case 'undo_remove_item': appointments_update_appointment_status( $app_id, 'pending' ); break;

		}

	}


	public function remove_app_from_cart_when_expired( $appointment, $new_status ){

		if( !class_exists('Marketpress') ) return;

		//Check if appointment exists in cart		
		$cart_products = mp_cart()->get_items_as_objects();

		foreach( $cart_products as $product ){

			if( get_post_meta( $product->ID, 'name', true ) == $appointment->ID ){

				mp_cart()->remove_item( $product->ID );

			}

		}

	}

}
App_Mp_ProductCartDisplay::serve();

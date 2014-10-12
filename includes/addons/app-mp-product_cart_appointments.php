<?php
/*
Plugin Name: Appointments in product cart
Description: Control how your appointments show in the product cart.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Integration
Requires: MarketPress
Author: WPMU DEV
*/

class App_Mp_ProductCartDisplay {

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
			add_action('wp_footer', array($this, 'auto_add_to_cart'));
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
			?>
<tr class="payment_row" <?php if ( $this->_data['payment_required'] != 'yes' ) echo 'style="display:none"';?>>
	<th scope="row"><?php _e('Appointment in shopping cart format', 'appointments'); ?></th>
	<td colspan="2">
		<input type="text" class="widefat" name="cart_name_format" id="app-cart_name_format" value="<?php echo ($this->_data['cart_name_format'] ? esc_attr($this->_data['cart_name_format']) : ''); ?>" />
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
}
App_Mp_ProductCartDisplay::serve();
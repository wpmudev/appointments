<?php

class App_MP_Bridge {
	private $_core;

	private function __construct () {
		global $appointments;
		$this->_core = $appointments;
	}

	public static function serve () {
		$me = new self;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_filter('mp_product', array($this, 'filter_product'), 10, 2);
		add_filter('app-appointment-appointment_created', array($this, 'appointment_created'), 10, 7);
		add_action('app_remove_pending', array($this, 'remove_from_cart'));

		add_action('mp_order_order_paid', array($this, 'mp_product_order_paid'));
		add_filter('mp_cart/column_html', array($this, 'filter_cart_column'), 10, 4);
	}

	public function filter_product ($html, $product_id) {
		if ( ! $this->_is_app_mp_page( $product_id ) ) {
			return $html;
		}

		$product = get_post( $product_id );
		$this->_add_footer_script();

		return apply_filters( 'the_content', $product->post_content );
	}

	public function filter_cart_column ($html, $column, $product) {
		if ( 'title' !== $column ) {
			return $html;
		}
		if ( MP_Product::get_variations_post_type() !== $product->post_type ) {
			return $html;
		}
		if ( ! $this->_is_app_mp_page( $product->post_parent ) ) {
			return $html;
		}

		$app_id = MP_Product::get_variation_meta( $product->ID, 'name' );
		$app    = appointments_get_appointment( $app_id );
		if ( $app ) {
			$name = get_the_title( $product->ID ) . " (" . date_i18n( $this->_core->datetime_format, strtotime( $app->start ) ) . ")";
			$name = apply_filters(
				'app_mp_product_name_in_cart',
				$name,
				$this->_core->get_service_name( $app->service ),
				appointments_get_worker_name( $app->worker ),
				$app->start,
				$app
			);
			$html = '<h2 class="mp_cart_item_title">' .
			        '<a href="' . esc_url( get_permalink( $product->post_parent ) ) . '">' .
			        $name .
			        '</a>' .
			        '<h2>';
		}

		return $html;
	}

	public function appointment_created ($additional, $insert_id, $post_id, $service, $worker, $start, $end) {
		if ( ! $this->_is_app_mp_page( $post_id ) ) {
			return $additional;
		}

		$variation = $this->_add_variation( $insert_id, $post_id, $service, $worker, $start, $end );
		if ( empty( $variation ) ) {
			return $additional;
		}

		$cart  = MP_Cart::get_instance();
		$items = $cart->get_items();
		if ( is_array( $items ) && false === array_search( $variation, array_keys( $items ) ) ) {
			// Only add once, not if it's already in the cart
			$cart->add_item( $variation );
		}
		$additional['mp']        = 1;
		$additional['variation'] = $variation;

		return $additional;
	}

	public function remove_from_cart ($expired_app) {
		$cart = MP_Cart::get_instance();
		$items = $cart->get_items();
		foreach ($items as $variation => $amount) {
			$app_id = MP_Product::get_variation_meta($variation, 'name');
			if ($app_id == $expired_app->ID) $cart->remove_item($variation);
		}
	}

	public function mp_product_order_paid ($order) {
		$cart_info = is_object($order) && is_callable(array($order, 'get_cart'))
			? $order->get_cart()->get_items()
			: (isset($order->mp_cart_info) ? $order->mp_cart_info : array())
		;

		if( !is_object( $cart_info ) || epmty( $cart_info ) ){
			global $mp_cart;
			$cart_info = $mp_cart->get_items();
		}

		$variation_type = MP_Product::get_variations_post_type();
		$appointment_ids = array();

		if (is_array($cart_info) && count($cart_info)) foreach ($cart_info as $cart_id => $count) {
			$variation = get_post($cart_id);
			if (!empty($variation->post_type) && $variation_type === $variation->post_type && $this->_is_app_mp_page($variation->post_parent)) {
				$app_id = MP_Product::get_variation_meta($variation->ID, 'name');
				if (is_numeric($app_id)) $appointment_ids[] = $app_id;
			}
		}
		
		$do_send = !empty($this->_core->options["send_confirmation"]) && 'yes' == $this->_core->options["send_confirmation"];
		foreach ($appointment_ids as $aid) {
			if (!$this->_core->change_status('paid', $aid)) continue;

			do_action('app_mp_order_paid', $aid, $order);
		}
	}

	private function _add_variation ($app_id, $post_id, $service, $worker, $start, $end) {
		// Yeah, let's just go off with creating the variations
		$variation_id = wp_insert_post(array(
			'post_parent' => $post_id,
			'post_title' => get_the_title($post_id),
			'post_status' => 'publish',
			'post_type' => MP_Product::get_variations_post_type(),
		), false);
		if (empty($variation_id)) return false;

		$price = false;
		$raw_price = apply_filters('app_mp_price', $this->_core->get_price( true ), $service, $worker, $start, $end); // Filter added at V1.2.3.1
		if (function_exists('filter_var') && defined('FILTER_VALIDATE_FLOAT') && defined('FILTER_FLAG_ALLOW_THOUSAND')) {
			$price = filter_var($raw_price, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND); // Undo the problematic number formatting issue
		}
		if (false === $price) {
			$price = str_replace(',', '', $raw_price);
		}

        $thumbnail_id = apply_filters('app_variation_thumbnail', false, $service);
		update_post_meta($variation_id, 'name', $app_id);
		update_post_meta($variation_id, 'sku', $this->_core->service);
		update_post_meta($variation_id, 'regular_price', $price);
		if ( $thumbnail_id ) {
			update_post_meta( $variation_id, '_thumbnail_id', $thumbnail_id );
		}

		return $variation_id;
	}

	private function _add_footer_script () {
		$href = esc_url(mp_cart_link(false, true));
		$message = esc_js(__('Please, proceed to checkout.', 'appointments'));
		$js = <<<EO_MP_JS
;(function ($) {
	$(document).on('app-confirmation-response_received', function (e, response) {
		if (response && response.mp && 1 == response.mp) {
			$(".appointments-confirmation-wrapper").replaceWith('<p class="app-confirmation-marketpress"><a href="{$href}">{$message}</a></p>');
		}
	});
})(jQuery);
EO_MP_JS;
		$this->_core->add2footer($js);
	}

	/**
	 * Determine if a page is A+ Product page from the shortcodes used
	 * @param mixed $product Custom post object or ID
	 * @return bool
	 */
	private function _is_app_mp_page ($product) {
		$product = get_post($product);
		$result = is_object( $product ) && strpos( $product->post_content, '[app_' ) !== false
			? true
			: false
		;
		// Maybe required for templates
		return apply_filters( 'app_is_mp_page', $result, $product );
	}
}

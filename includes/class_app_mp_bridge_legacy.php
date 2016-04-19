<?php

class App_MP_Bridge_Legacy {

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
		add_action('manage_posts_custom_column', array($this, 'edit_products_custom_columns'), 1);
		add_action('wp_ajax_nopriv_mp-update-cart', array($this, 'pre_update_cart'), 1);
		add_action('wp_ajax_mp-update-cart', array($this, 'pre_update_cart'), 1);
		add_action('wp', array($this, 'remove_from_cart_manual'), 1);
		add_filter('the_content', array($this, 'product_page'), 18);
		add_action('mp_order_paid', array($this, 'handle_mp_payment'));
		add_filter('mp_product_list_meta', array($this, 'mp_product_list_meta'), 10, 2);
		add_filter('mp_order_notification_body', array($this, 'modify_email'), 10, 2);
		add_filter('mp_product_name_display_in_cart', array($this, 'modify_name'), 10, 2);
		add_filter('mp_buy_button_tag', array($this, 'mp_buy_button_tag'), 10, 3);

		add_action('app-shortcodes-shortcode_found', array($this, 'shortcode_found'));
		add_filter('app-appointment-appointment_created', array($this, 'appointment_created'), 10, 7);

		add_action('app_remove_pending', array($this, 'remove_from_cart'));
	}

	public function appointment_created ($additional, $insert_id, $post_id, $service, $worker, $start, $end) {
		$post = get_post( $post_id );
		$mp = $variation = 0;
		if ('product' == $post->post_type && $this->is_app_mp_page($post)) {
			$additional['mp'] = 1;
			$additional['variation'] = $this->add_variation( $insert_id, $post_id, $service, $worker, $start, $end );
		}
		return $additional;
	}

	public function shortcode_found ($post) {
		if (empty($post->post_type) || empty($post->ID)) return false;
		if ('product' === $post->post_type) $this->add_to_mp($post->ID);
	}

	/**
	 * Remove duplicate buttons on Product List page and modify button text, also replace form with a link
	 * @param $button, $product_id, $context: See MarketPress
	 * @return string
	 * @Since 1.2.5
	 */
	function mp_buy_button_tag( $button, $product_id, $context ) {

		$book_now = apply_filters( 'app_mp_book_now', __('Choose Option &raquo;','appointments') );

		$product = get_post( $product_id );
		if ( 'list' != $context || !$this->is_app_mp_page( $product ) )
			return $button;

		if ( isset($_REQUEST['order'] ) ) {
			$button = preg_replace(
				'%<input class="mp_button_buynow"(.*?)value="(.*?)" />%is',
				'<input class="mp_button_buynow" type="submit" name="buynow" value="'.$book_now.'" />',
				$button
			);
			$button = preg_replace(
				'%<input class="mp_button_addcart"(.*?)value="(.*?)" />%is',
				'<input class="mp_button_buynow" type="submit" name="buynow" value="'.$book_now.'" />',
				$button
			);
			$button = preg_replace(
				'%<form(.*?)></form>%is',
				'<a class="mp_link_buynow" href="'.get_permalink($product_id).'">'.$book_now.'</a>',
				$button
			);

			return $button;
		}
		else return '';
	}

	/**
	 * Determine if a page is A+ Product page from the shortcodes used
	 * @param $product custom post object
	 * @return bool
	 * @Since 1.0.1
	 */
	function is_app_mp_page( $product ) {
		$result = false;
		if ( is_object( $product ) && strpos( $product->post_content, '[app_' ) !== false )
			$result = true;
		// Maybe required for templates
		return apply_filters( 'app_is_mp_page', $result, $product );
	}

	/**
	 * Hide column details for A+ products
	 * @Since 1.0.1
	 */
	function edit_products_custom_columns( $column ) {
		global $post, $mp;
		if (!$this->is_app_mp_page($post)) return;
		$hook = version_compare($mp->version, '2.8.8', '<')
			? 'manage_posts_custom_column'
			: 'manage_product_posts_custom_column'
		;
		if ('variations' == $column || 'sku' == $column || 'pricing' == $column) {
			remove_action($hook, array($mp, 'edit_products_custom_columns'));
			echo '-';
		} else {
			add_action($hook, array($mp, 'edit_products_custom_columns'));
		}
	}

	/**
	 * Remove download link from confirmation email
	 * @Since 1.0.1
	 */
	function modify_email( $body, $order ) {

		if ( !is_object( $order ) || !is_array( $order->mp_cart_info ) )
			return $body;

		$order_id = $order->post_title; // Strange, but true :)

		foreach ( $order->mp_cart_info as $product_id=>$product_detail ) {
			$product = get_post( $product_id );
			// Find if this is an A+ product and change link if it is
			if ( $this->is_app_mp_page( $product ) )
				$body = str_replace( get_permalink( $product_id ) . "?orderid=$order_id", '-', $body );
		}

		// Addons may want to modify MP email
		return apply_filters( 'app_mp_email', $body, $order );
	}

	/**
	 * Modify display name in the cart
	 * @Since 1.0.1
	 */
	function modify_name( $name, $product_id ) {
		$product = get_post( $product_id );
		$var_names = get_post_meta( $product_id, 'mp_var_name', true );
		if ( !$this->is_app_mp_page( $product ) || !is_array( $var_names ) )
			return $name;

		list( $app_title, $app_id ) = split( ':', $name );
		if ( $app_id ) {
			$result = appointments_get_appointment( $app_id );
			if ( $result ) {
				$name = $name . " (". date_i18n( $this->_core->datetime_format, strtotime( $result->start ) ) . ")";
				$name = apply_filters( 'app_mp_product_name_in_cart', $name, $this->_core->get_service_name( $result->service ), appointments_get_worker_name( $result->worker ), $result->start, $result );
			}
		}
		return $name;
	}

	/**
	 * Handle after a successful Marketpress payment
	 * @Since 1.0.1
	 */
	function handle_mp_payment( $order ) {

		if ( !is_object( $order ) || !is_array( $order->mp_cart_info ) )
			return;

		foreach ( $order->mp_cart_info as $product_id=>$product_detail ) {
			$product = get_post( $product_id );
			// Find if this is an A+ product
			if ( $this->is_app_mp_page( $product ) && is_array( $product_detail ) ) {
				foreach( $product_detail as $var ) {
					// Find variation = app id which should also be downloadable
					if ( isset( $var['name'] ) && isset( $var['download'] ) ) {
						list( $product_name, $app_id ) = split( ':', $var['name'] );
						$app_id = (int)trim( $app_id );
						if ( $this->_core->change_status( 'paid', $app_id ) ) {
							do_action( 'app_mp_order_paid', $app_id, $order ); // FIRST do the action
						}
					}
				}
			}
		}
	}

	/**
	 * Add to array of product pages where we have A+ shortcodes
	 * @Since 1.0.1
	 */
	function add_to_mp( $post_id ) {
		$this->mp_posts[] = $post_id;
	}


	/**
	 * If this is an A+ product page add js codes to footer to hide some MP fields
	 * @param content: post content
	 * @Since 1.0.1
	 */
	function product_page( $content ) {

		global $post;
		if ( is_object( $post ) && in_array( $post->ID, $this->mp_posts ) )
			$this->_core->add2footer( '$(".mp_quantity,.mp_product_price,.mp_buy_form,.mp_product_variations,.appointments-paypal").hide();' );

		return $content;
	}

	/**
	 * Hide meta (Add to chart button, price) for an A+ product
	 * @Since 1.0.1
	 */
	function mp_product_list_meta( $meta, $post_id) {

		if ( in_array( $post_id, $this->mp_posts ) )
			return '<a class="mp_link_buynow" href="' . get_permalink($post_id) . '">' . __('Choose Option &raquo;', 'appointments') . '</a>';
		else
			return	$meta;
	}

	/**
	 * Adds and returns a variation to the app product
	 * @Since 1.0.1
	 */
	function add_variation( $app_id, $post_id, $service, $worker, $start, $end ) {

		$meta = get_post_meta( $post_id, 'mp_var_name', true );
		// MP requires at least 2 variations, so we add a dummy one	if there is none
		if ( !$meta || !is_array( $meta ) ) {
			add_post_meta( $post_id, 'mp_var_name', array( 0 ) );
			add_post_meta( $post_id, 'mp_sku', array( 0 ) );

			// Find minimum service price here:
			global $wpdb;
			$min_price = appointments_get_services_min_price();

			add_post_meta( $post_id, 'mp_price', array( $min_price ) );
			// Variation ID
			$meta = array( 0 );
		}

		$max = count( $meta );
		$meta[$max] = $app_id;
		update_post_meta( $post_id, 'mp_var_name', $meta );

		$sku = get_post_meta( $post_id, 'mp_sku', true );
		$sku[$max] = $this->_core->service;
		update_post_meta( $post_id, 'mp_sku', $sku );

		$price = get_post_meta( $post_id, 'mp_price', true );
		$price[$max] = apply_filters( 'app_mp_price', $this->_core->get_price( true ), $service, $worker, $start, $end ); // Filter added at V1.2.3.1
		update_post_meta( $post_id, 'mp_price', $price );

		// Add a download link, so that app will be a digital product
		$file = get_post_meta($post_id, 'mp_file', true);
		if ( !$file )
			add_post_meta( $post_id, 'mp_file', get_permalink( $post_id ) );

		return $max;
	}

	/**
	 * If a pending app is removed automatically, also remove it from the cart
	 * @Since 1.0.1
	 */
	function remove_from_cart( $app ) {
		global $mp;
		$changed = false;
		$cart = $mp->get_cart_cookie();

		if ( is_array( $cart ) ) {
			foreach ( $cart as $product_id=>$product_detail ) {
				$product = get_post( $product_id );
				$var_names = get_post_meta( $product_id, 'mp_var_name', true );
				// Find if this is an A+ product
				if ( $this->is_app_mp_page( $product ) && is_array( $product_detail ) && is_array( $var_names ) ) {
					foreach( $product_detail as $var_id=>$var_val ) {
						// Find variation = app id
						if ( isset( $var_names[$var_id] ) && $var_names[$var_id] == $app->ID ) {
							unset( $cart[$product_id] );
							$changed = true;
						}
					}
				}
			}
		}
		// Update cart only if something has changed
		if ( $changed )
			$mp->set_cart_cookie($cart);
	}

	/**
	 * Clear appointment that is removed from the cart also from the database
	 * This is called before MP
	 * @Since 1.0.1
	 */
	function remove_from_cart_manual( ) {

		if (isset($_POST['update_cart_submit'])) {
			if (isset($_POST['remove']) && is_array($_POST['remove'])) {
				foreach ($_POST['remove'] as $pbid) {
					list($bid, $product_id, $var_id) = split(':', $pbid);
					$product = get_post( $product_id );
					// Check if this is an app product page
					if ( $this->is_app_mp_page( $product ) ) {
						// We need to find var name = app_id
						$var_names = get_post_meta( $product_id, 'mp_var_name', true );
						if ( isset( $var_names[$var_id] ) ) {
							$this->change_status( 'removed', (int)trim( $var_names[$var_id] ) );
						}
					}
				}
			}
		}
	}

	/**
	 * Add the appointment to the cart
	 * This is called before MP
	 * @Since 1.0.1
	 */
	function pre_update_cart( ) {
		global $mp;

		if ( isset( $_POST['product_id'] )  && isset( $_POST['variation'] ) && $_POST['product_id'] && $_POST['variation'] ) {
			$product_id = $_POST['product_id'];
			$product = get_post( $product_id );
			// Check if this is an app product page
			if ( $this->is_app_mp_page( $product ) ) {
				$variation = $_POST['variation'];

				$cart = $mp->get_cart_cookie();
				if ( !is_array( $cart ) )
					$cart = array();

				// Make quantity 0 so that MP can set it to 1
				$cart[$product_id][$variation] = 0;

				//save items to cookie
				$mp->set_cart_cookie($cart);

				// Set email to SESSION variables if not set before
				if ( !isset( $_SESSION['mp_shipping_info']['email'] ) && isset( $_COOKIE["wpmudev_appointments_userdata"] ) ) {
					$data = unserialize( stripslashes( $_COOKIE["wpmudev_appointments_userdata"] ) );
					if ( is_array( $data ) && isset( $data["e"] ) )
						@$_SESSION['mp_shipping_info']['email'] = $data["e"];
				}
			}
		}
	}
}
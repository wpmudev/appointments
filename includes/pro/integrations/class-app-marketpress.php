<?php

class Appointments_Integrations_MarketPress {

	public function __construct() {

		if ( ! $this->is_mp_active() ) {
			return;
		}
		// Add MP Default options
		add_filter( 'appointments_default_options', array( $this, 'default_options' ) );

		add_action( 'appointments_settings_tab-main-section-payments', array( $this, 'show_settings' ) );
		add_filter( 'app-options-before_save', array( $this, 'save_settings' ) );
	}

	private function is_mp_active() {
		// class_app_mp_bridge (for MP>3.0)
		// class_app_mp_bridge_legacy (for MP < 3.0)
		global $mp;
		return class_exists('MarketPress') && is_object( $mp );
	}

	private function is_integration_active() {
		$options = appointments_get_options();
		return $this->is_mp_active() && $options['use_mp'];
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
			'use_mp' => false,
			'make_an_appointment_product' => false,
			'app_page_type_mp' => 'one_month'
		);
		return array_merge( $options, $mp_options );
	}

	public function show_settings() {
		$options = appointments_get_options();
		$product_page = get_page_by_title( 'Appointment', OBJECT, 'product' );
		$codec  = new App_Macro_Codec;
		$macros = join( '</code>, <code>', $codec->get_macros() );

		?>
		<h3 class="mp-integration"><?php _e( 'MarketPress Integration', 'appointments' ); ?></h3>
		<table class="form-table mp-integration">
			<tr>
				<th scope="row"><label for="use_mp"><?php _e( 'Activate MarketPress integration', 'appointments' ); ?></label></th>
				<td>
					<input type="checkbox" name="use_mp" id="use_mp" <?php checked( $options["use_mp"] ); ?> />
					<p class="description"><?php _e( 'Appointments can be set as products. Any appointment shortcode added to a product page will make that page an "Appointment Product Page". For details, please see FAQ.', 'appointments' ) ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php _e('Appointment in shopping cart format', 'appointments'); ?></th>
				<td colspan="2">
					<input type="text" class="widefat" name="cart_name_format" id="app-cart_name_format" value="<?php echo esc_attr( $options['cart_name_format'] ); ?>" />
					<span class="description"><?php printf(__('You can use these macros: <code>%s</code>', 'appointments'), $macros); ?></span>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Auto-add appointments into cart', 'appointments'); ?></th>
				<td colspan="2">
					<input type="hidden" name="auto_add_to_cart" value="" />
					<input type="checkbox" name="auto_add_to_cart" id="app-auto_add_to_cart" value="1" <?php checked( $options['auto_add_to_cart'] ); ?> />
				</td>
			</tr>

			<?php do_action( 'app-settings-payment_settings-marketpress' ); ?>

			<tr>
				<th scope="row"><label for="make_an_appointment_product"><?php _e( 'Product Page', 'appointments' ); ?></label></th>
				<td>
					<input type="checkbox" name="make_an_appointment_product" id="make_an_appointment_product" <?php checked( $options['make_an_appointment_product'] ); ?> />
					<?php _e( 'Create an Appointment Product Page with', 'appointments' ) ?>

					<label for="app_page_type_mp" class="screen-reader-text"><?php _e( 'Select Product Page Type', 'appointments' ); ?></label>
					<select name="app_page_type_mp" id="app_page_type_mp">
						<option value="one_month"><?php _e( 'current month\'s schedule', 'appointments' ) ?></option>
						<option value="two_months" <?php selected( $options['app_page_type_mp'], 'two_months' ); ?>><?php _e( 'current and next month\'s schedules', 'appointments' ) ?></option>
						<option value="one_week" <?php selected( $options['app_page_type_mp'], 'one_week' ); ?>><?php _e( 'current week\'s schedule', 'appointments' ) ?></option>
						<option value="two_weeks" <?php selected( $options['app_page_type_mp'], 'two_weeks' ); ?>><?php _e( 'current and next week\'s schedules', 'appointments' ) ?></option>
					</select>
					<p class="description"><?php _e( 'Same as the above "Create an Appointment Page", but this time appointment shortcodes will be inserted in a new Product page and page title will be "Appointment". This is also the product name.', 'appointments' ) ?></p>

					<?php if ( $product_page ): ?>
						<p class="description"><?php _e( '<b>Note:</b> You already have such a page. If you check this checkbox, another page with the same title will be created. ', 'appointments' ) ?>
							<a href="<?php echo admin_url( 'post.php?post=' . $product_page->ID . '&action=edit' ) ?>" target="_blank"><?php _e( 'Edit Page', 'appointments' ) ?></a> |
							<a href="<?php echo get_permalink( $product_page->ID ) ?>" target="_blank"><?php _e( 'View Page', 'appointments' ) ?></a>
						</p>
					<?php endif; ?>
				</td>
			</tr>

		</table>

		<script>
			(function ($) {
				var payment_required = $('#payment_required');
				payment_required.change( function() {
					var value = $(this).val();
					if ( 'no' === value ) {
						$('.mp-integration').hide();
					}
					else {
						$('.mp-integration').show();
					}
				});
				payment_required.trigger( 'change' );
			})(jQuery);
		</script>

		<?php
	}

	public function save_settings( $options ) {
		$options['use_mp']           = isset( $_POST['use_mp'] );
		$options["app_page_type_mp"] = @$_POST["app_page_type_mp"];

		if ( ! empty( $_POST['cart_name_format'] ) ) {
			$options['cart_name_format'] = wp_strip_all_tags( stripslashes_deep( $_POST['cart_name_format'] ) );
		}
		$options['auto_add_to_cart'] = ! empty( $_POST['auto_add_to_cart'] );

		if ( isset( $_POST['make_an_appointment_product'] ) ) {
			$tpl     = ! empty( $_POST['app_page_type_mp'] ) ? $_POST['app_page_type_mp'] : false;
			$post_id = wp_insert_post(
				array(
					'post_title'   => 'Appointment',
					'post_status'  => 'publish',
					'post_type'    => 'product',
					'post_content' => App_Template::get_default_page_template( $tpl )
				)
			);

			if ( $post_id ) {
				// Add a download link, so that app will be a digital product
				$file = get_post_meta( $post_id, 'mp_file', true );
				if ( ! $file ) {
					add_post_meta( $post_id, 'mp_file', get_permalink( $post_id ) );
				}

				// MP requires at least 2 variations, so we add a dummy one
				add_post_meta( $post_id, 'mp_var_name', array( 0 ) );
				add_post_meta( $post_id, 'mp_sku', array( 0 ) );
				add_post_meta( $post_id, 'mp_price', array( 0 ) );
			}
		}

		return $options;
	}


}
<?php

class Appointments_Integrations_MarketPress {

	public function __construct() {
		if ( ! $this->is_mp_active() ) {
			return;
		}
		// Add MP Default options
		add_filter( 'appointments_default_options', array( $this, 'default_options' ) );
		// Add settings section
		add_action( 'appointments_settings_tab-main-section-payments', array( $this, 'show_settings' ) );
		add_filter( 'app-options-before_save', array( $this, 'save_settings' ) );
		if ( ! $this->is_integration_active() ) {
			return;
		}
		if ( defined( 'MP_VERSION' ) && version_compare( MP_VERSION, '3.0', '>=' ) ) {
			require_once( 'marketpress/class_app_mp_bridge.php' );
			App_MP_Bridge::serve();
		} else {
			require_once( 'marketpress/class_app_mp_bridge_legacy.php' );
			App_MP_Bridge_Legacy::serve();
		}
		add_action( 'wp_ajax_make_an_appointment_mp_page', array( $this, 'create_page' ) );
	}

	private function is_mp_active() {
		// class_app_mp_bridge (for MP>3.0)
		// class_app_mp_bridge_legacy (for MP < 3.0)
		global $mp;
		return class_exists( 'MarketPress' ) && is_object( $mp );
	}

	private function is_integration_active() {
		$options = appointments_get_options();
		return $this->is_mp_active() && $options['use_mp'] && $options['payment_required'] === 'yes';
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
			'use_mp' => false,
			'make_an_appointment_product' => false,
			'app_page_type_mp' => 'one_month',
		);
		return array_merge( $options, $mp_options );
	}

	public function show_settings() {
		$options = appointments_get_options();
		$product_page = get_page_by_title( 'Appointment', OBJECT, 'product' );
?>
<div class="payment_row">
		<h3 class="mp-integration"><?php _e( 'MarketPress Integration', 'appointments' ); ?></h3>
		<table class="form-table mp-integration">
			<tr>
				<th scope="row"><label for="use_mp"><?php _e( 'Activate integration', 'appointments' ); ?></label></th>
				<td>
                    <?php _appointments_html_chceckbox( $options, 'use_mp', 'payment_use_mp' ) ?>
					<p class="description"><?php _e( 'Appointments can be set as products. Any appointment shortcode added to a product page will make that page an "Appointment Product Page". For details, please see FAQ.', 'appointments' ) ?></p>
				</td>
			</tr>
			<?php do_action( 'app-settings-payment_settings-marketpress' ); ?>
			<tr class="payment_use_mp">
				<th scope="row"><label for="make_an_appointment_product"><?php _e( 'Create Product Page', 'appointments' ); ?></label></th>
				<td class="appointment-create-page">
					<?php _e( 'with', 'appointments' ) ?>
					<label for="app_page_type_mp" class="screen-reader-text"><?php _e( 'Select Product Page Type', 'appointments' ); ?></label>
					<select>
						<option value="one_month"><?php _e( 'current month\'s schedule', 'appointments' ) ?></option>
						<option value="two_months" <?php selected( $options['app_page_type_mp'], 'two_months' ); ?>><?php _e( 'current and next month\'s schedules', 'appointments' ) ?></option>
						<option value="one_week" <?php selected( $options['app_page_type_mp'], 'one_week' ); ?>><?php _e( 'current week\'s schedule', 'appointments' ) ?></option>
						<option value="two_weeks" <?php selected( $options['app_page_type_mp'], 'two_weeks' ); ?>><?php _e( 'current and next week\'s schedules', 'appointments' ) ?></option>
					</select>
                    <a href="#" data-action="make_an_appointment_mp_page" class="button" data-nonce="<?php echo wp_create_nonce( 'appointment-create-page' ); ?>"><?php esc_html_e( 'Create page!', 'appointments' ); ?></a>
					<p class="description"><?php _e( 'Same as the above "Create an Appointment Page", but this time appointment shortcodes will be inserted in a new Product page and page title will be "Appointment". This is also the product name.', 'appointments' ) ?></p>
					<?php if ( $product_page ) :  ?>
						<p class="description"><?php _e( '<b>Note:</b> You already have such a page. If you check this checkbox, another page with the same title will be created. ', 'appointments' ) ?>
							<a href="<?php echo admin_url( 'post.php?post=' . $product_page->ID . '&action=edit' ) ?>" target="_blank"><?php _e( 'Edit Page', 'appointments' ) ?></a> |
							<a href="<?php echo get_permalink( $product_page->ID ) ?>" target="_blank"><?php _e( 'View Page', 'appointments' ) ?></a>
						</p>
					<?php endif; ?>
				</td>
			</tr>
        </table>
</div>
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
		$options['app_page_type_mp'] = @$_POST['app_page_type_mp'];
		if ( ! empty( $_POST['cart_name_format'] ) ) {
			$options['cart_name_format'] = wp_strip_all_tags( stripslashes_deep( $_POST['cart_name_format'] ) );
		}
		return $options;
	}

	/**
	 * Allow to create Appointment product in MarketPress.
	 *
	 * @since 2.3.0
	 */
	public function create_page() {
		$data = array(
			'message' => __( 'Something went wrong!', 'appointments' ),
		);
		if (
			! isset( $_POST['_wpnonce'] )
			|| ! isset( $_POST['app_page_type'] )
			|| ! wp_verify_nonce( $_POST['_wpnonce'], 'appointment-create-page' )
		) {
			wp_send_json_error( $data );
		}
		$tpl = ! empty( $_POST['app_page_type'] ) ? $_POST['app_page_type'] : false;
		$page_id = wp_insert_post(
			array(
				'post_title'   => _x( 'Appointment', 'Default page name for MarketPress integration.', 'appointments' ),
				'post_status'  => 'publish',
				'post_type'    => 'product',
				'post_content' => App_Template::get_default_page_template( $tpl ),
				'meta_input'   => array(
					'product_type' => 'digital',
					// Set a meta to define this is an app mp product
					'mp_app_product' => 1,
				),
			)
		);
		if ( $page_id ) {
			add_post_meta( $page_id, 'file_url', get_permalink( $page_id ) );
			add_post_meta( $page_id, 'sku', 'appointment-'. $page_id );
			$data = array(
				'message' => sprintf(
					__( 'Page was created successfuly: %s', 'appointments' ),
					sprintf(
						'<a href="%s">%s</a>',
						esc_url( get_page_link( $page_id ) ),
						esc_html__( 'View page', 'appointments' )
					)
				),
			);
			wp_send_json_success( $data );
		}
		wp_send_json_error( $data );
	}
}

new Appointments_Integrations_MarketPress();

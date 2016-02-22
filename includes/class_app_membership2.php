<?php
/**
 * Integrates the Membership2 plugin into Appointments Plus.
 *
 * Effect:
 * New Settings displayed in the Appointments Plus Payment settings form where
 * a special discount can be selected for Members of certain Memberships.
 */
class App_Membership2 {

	/**
	 * A reference to the Membership2 API instance.
	 * The property is set by $this::init()
	 *
	 * @var MS_Controller_Api
	 */
	protected $api = null;

	/**
	 * Create and setup the Membership2 integration.
	 *
	 * @since  1.0.0
	 */
	public static function setup() {
		static $Inst = null;

		if ( null === $Inst ) {
			$Inst = new App_Membership2();
		}
	}

	/**
	 * Protected constructor is run once only.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		add_action( 'ms_init', array( $this, 'init' ) );
	}

	/**
	 * Adds the hooks to integrate with Membership2.
	 *
	 * This function is called when Membership2 is active and initializtion
	 * is done.
	 *
	 * @since  1.0.0
	 * @param  MS_Controller_Api $api The API instance.
	 */
	public function init( $api ) {
		$this->api = $api;

		// Apply a discount to logged-in members.
		add_filter(
			'app_get_price_prepare',
			array( $this, 'get_price' ), 10, 3
		);

		// Add CSS/JS to the top of the settings form
		add_action(
			'app-settings-after_advanced_settings',
			array( $this, 'settings_scripts' )
		);

		// Display integration settings on the payment settings page.
		add_action(
			'app_settings_form_payment',
			array( $this, 'settings_form' ), 10, 2
		);

		// Save the custom payment settings.
		add_filter(
			'app-options-before_save',
			array( $this, 'settings_save' )
		);
	}

	/**
	 * Filter that can apply a discount to the appointment price for logged in
	 * members.
	 *
	 * We will check all memberships of the current user to find the highest
	 * discount available for him.
	 *
	 * @since  1.0.0
	 * @param  float $price Default price
	 * @param  bool $want_advance If we need the total price or deposit price.
	 * @param  Appointments $appointments Caller object.
	 * @return float Modified price
	 */
	public function get_price( $price, $want_advance, $appointments ) {
		$member = $this->api->get_current_member();
		$max_discount = 0;
		$need_deposit = true;

		foreach ( $member->subscriptions as $subscription ) {
			$membership = $subscription->get_membership();

			// Get the custom data
			$discount = $membership->get_custom_data( 'app_discount_value' );
			$type = $membership->get_custom_data( 'app_discount_type' );
			$no_advance = $membership->get_custom_data( 'app_no_advance_payment' );
			$discount = floatval( $discount );

			// Find the effective discount amount (i.e. convert percentage)
			switch ( $type ) {
				case 'per':
					$discount = $price * $discount / 100;
					break;

				case 'abs':
					// no calculation needed.
					break;

				default:
					// Unrecognized discount types are ignored
					$discount = 0;
					break;
			}

			if ( $max_discount < $discount ) {
				$max_discount = $discount;
			}

			if ( $no_advance ) {
				$need_deposit = false;
			}
		}

		if ( $want_advance && ! $need_deposit ) {
			/*
			 * If we calculate the Advance deposit price but the member is
			 * allowed to book without advance payment then set price to 0.
			 */
			$price = 0;
		} elseif ( $max_discount > 0 ) {
			/*
			 * If the user is eligable for a Membership discount then calculate
			 * new price. Make sure the price does not go below 0
			 */
			$price -= $max_discount;
			if ( $price < 0 ) {
				$price = 0;
			}
		}

		return $price;
	}

	/**
	 * Output the payment settings form used by this integration.
	 *
	 * @since  1.0.0
	 * @param  array $options List of all Appintments options.
	 * @param  bool $use_payments Current state of the "require payments" flag.
	 */
	public function settings_form( $options, $use_payments ) {
		$row_style = '';
		if ( ! $use_payments ) {
			$row_style = 'display:none;';
		}
		$memberships = $this->api->list_memberships( $list_all = true );
		?>
		<tr class="payment_row" style="<?php echo $row_style; ?>border-top: 1px solid lightgrey;">
			<th scope="row">&nbsp;</th>
			<td colspan="2">
			<span class="description"><?php
			printf(
				__( 'The below fields require %s plugin.', 'appointments' ),
				'<a href="http://premium.wpmudev.org/project/membership/" target="_blank">Membership 2</a>'
			);
			?></span>
			</td>
		</tr>
		<tr class="payment_row" style="<?php echo $row_style; ?>">
			<th scope="row"><?php _e( 'Membership 2 Integration', 'appointments' ); ?></th>
			<td colspan="2">
			<table class="ms-membership-list">
				<tr>
					<th class="ms-row-label">
						<?php _e( 'Membership', 'appointments' ); ?>
					</th>
					<th class="ms-row-discount">
						<?php _e( 'Discount', 'appointments' ); ?>
					</th>
					<th class="ms-row-advance">
						<?php _e( 'No Advance Payment', 'appointments' ); ?>
					</th>
				</tr>
				<?php foreach ( $memberships as $membership ) : ?>
				<?php if ( $membership->is_guest() ) { continue; } ?>

				<?php $the_id = 'ms-item-' . $membership->id; ?>
				<?php $name_prefix = 'ms_appointment_data[' . $membership->id . ']'; ?>
				<?php $val_discount = $membership->get_custom_data( 'app_discount_value' ); ?>
				<?php $val_type = $membership->get_custom_data( 'app_discount_type' ); ?>
				<?php $val_no_advance = $membership->get_custom_data( 'app_no_advance_payment' ); ?>
				<tr class="ms-membership-row">
					<td class="ms-row-label">
						<label for="<?php echo esc_attr( $the_id ); ?>">
						<?php $membership->name_tag(); ?>
						</label>
					</td>
					<td class="ms-row-discount">
						<input type="number"
							id="<?php echo esc_attr( $the_id ); ?>"
							name="<?php echo esc_attr( $name_prefix ); ?>[discount]"
							class="ms-discount"
							placeholder="0"
							min="0"
							step="any"
							value="<?php echo esc_attr( $val_discount ); ?>"
							/>
						<select
							name="<?php echo esc_attr( $name_prefix ); ?>[type]"
							>
							<option value="per" <?php selected( $val_type, 'per' ); ?>>
								%
							</option>
							<option value="abs" <?php selected( $val_type, 'abs' ); ?>>
								<?php _e( 'fixed', 'appointments' ); ?>
							</option>
						</select>
					</td>
					<td class="ms-row-advance">
						<input type="checkbox"
							id="<?php echo esc_attr( $the_id ); ?>_advance"
							class="ms-advance"
							name="<?php echo esc_attr( $name_prefix ); ?>[no_advance]"
							<?php checked( $val_no_advance ); ?>
							/>
						<label for="<?php echo esc_attr( $the_id ); ?>_advance">
							<span class="off"><?php _e( 'Requires advance payment', 'affiliates' ); ?></span>
							<span class="on"><?php _e( 'No advance payment', 'affiliates' ); ?></span>
						</label>
					</td>
				</tr>
				<?php endforeach; ?>
			</table>
			</td>
		</tr>
		<?php
	}

	/**
	 * Filter that is called when Appointment settings are saved.
	 *
	 * We use this filter to save all custom settings that we added via the
	 * settings_form() function.
	 * Note that we store the payment settings in the Membership object as
	 * custom_data and not in the Appointments option structure!
	 *
	 * @since  1.0.0
	 * @param  array $settings Appointments Settings collection.
	 * @return array Modified Appointments Settings collection.
	 */
	public function settings_save( $settings ) {
		$data = false;
		if ( isset( $_POST['ms_appointment_data'] ) ) {
			$data = $_POST['ms_appointment_data'];
		}

		if ( is_array( $data ) ) {
			foreach ( $data as $id => $item ) {
				$item['discount'] = empty( $item['discount'] ) ? 0 : $item['discount'];
				$item['type'] = empty( $item['type'] ) ? 'per' : $item['type'];
				$item['no_advance'] = ! empty( $item['no_advance'] );

				$membership = $this->api->get_membership( $id );
				$membership->set_custom_data( 'app_discount_value', $item['discount'] );
				$membership->set_custom_data( 'app_discount_type', $item['type'] );
				$membership->set_custom_data( 'app_no_advance_payment', $item['no_advance'] );
				$membership->save();
			}
		}

		return $settings;
	}

	/**
	 * Output some CSS and JS code on the settings page.
	 *
	 * @since  1.0.0
	 * @param  array $options List of all Appointments options.
	 */
	public function settings_scripts( $options ) {
		?>
		<style>
		.ms-membership-list th,
		.ms-membership-list td {
			padding: 4px;
		}
		.ms-membership-list .ms-row-label {
			width: 120px;
		}
		.ms-membership-list .ms-row-discount {
			width: 170px;
		}
		.ms-membership-list .ms-row-advance {
			width: auto;
		}
		.ms-membership-list .ms-discount {
			width: 70px;
		}
		.ms-membership {
			display: inline-block;
			border-radius: 3px;
			color: #FFF;
			background: #888;
			padding: 1px 5px;
			font-size: 12px;
			height: 20px;
			line-height: 20px;
			margin-bottom: 1px;
			max-width: 120px;
			text-overflow: ellipsis;
			white-space: nowrap;
			overflow: hidden;
		}
		.ms-advance + label .on {
			display: none;
		}
		.ms-advance + label .off {
			display: inline-block;
			color: #AAA;
		}
		.ms-advance:checked + label .on {
			display: inline-block;
		}
		.ms-advance:checked + label .off {
			display: none;
		}
		</style>
		<?php
	}

}

App_Membership2::setup();
<?php
if ( empty( $options['payment_required'] ) ) {
	$options['payment_required'] = 'no';
}
$use_payments = ( 'yes' == $options['payment_required'] );
?>
<table class="form-table">

	<tr>
		<th scope="row"><label for="payment_required"><?php _e( 'Payment required', 'appointments' ) ?></label></th>
		<td>
			<select name="payment_required" id="payment_required">
				<option value="no" <?php if ( ! $use_payments ) echo "selected='selected'" ?>><?php _e( 'No', 'appointments' ) ?></option>
				<option value="yes" <?php if ( $use_payments ) echo "selected='selected'" ?>><?php _e( 'Yes', 'appointments' ) ?></option>
			</select>
			<p class="description"><?php printf( __( 'Whether you require a payment to accept appointments. If selected Yes, client is asked to pay through Paypal and the appointment will be in pending status until the payment is confirmed by Paypal IPN. If selected No, appointment will be in pending status until you manually approve it using the %s unless Auto Confirm is not set as Yes.', 'appointments' ), '<a href="' . admin_url( 'admin.php?page=appointments' ) . '">' . __( 'Appointments page', 'appointments' ) . '</a>' ) ?></p>
		</td>
	</tr>

	<tr class="payment_row" <?php if ( ! $use_payments ) echo 'style="display:none"' ?>>
		<th scope="row"><label for="currency"><?php _e( 'Paypal Currency', 'appointments' ) ?></label></th>
		<td colspan="2">
			<select name="currency" id="currency">
				<?php
				$sel_currency = ( $options['currency'] ) ? $options['currency'] : $options['currency'];
				$currencies   = App_Template::get_currencies();

				foreach ( $currencies as $k => $v ) {
					echo '<option value="' . $k . '"' . ( $k == $sel_currency ? ' selected' : '' ) . '>' . esc_html( $v, true ) . '</option>' . "\n";
				}
				?>
			</select>
		</td>
	</tr>
	<tr class="payment_row" <?php if ( ! $use_payments ) echo 'style="display:none"' ?>>
		<th scope="row"><label for="mode"><?php _e( 'PayPal Mode', 'appointments' ) ?></label></th>
		<td>
			<select name="mode" id="mode">
				<option value="sandbox"<?php selected( $options['mode'], 'sandbox' ) ?>><?php _e( 'Sandbox', 'appointments' ) ?></option>
				<option value="live"<?php selected( $options['mode'], 'live' ) ?>><?php _e( 'Live', 'appointments' ) ?></option>
			</select>
		</td>
	</tr>

	<tr class="payment_row" <?php if ( ! $use_payments ) echo 'style="display:none"' ?>>
		<th scope="row"><label for="merchant_email"><?php _e( 'PayPal Merchant E-mail', 'appointments' ) ?></label></th>
		<td colspan="2">
			<input value="<?php echo esc_attr( $options['merchant_email'] ); ?>" size="30" name="merchant_email" id="merchant_email" type="text"/>
			<p class="description"> <?php printf( __( 'Just for your information, your IPN link is: <b>%s </b>. You may need this information in some cases.', 'appointments' ), admin_url( 'admin-ajax.php?action=app_paypal_ipn' ) ); ?> </p>
		</td>
	</tr>

	<tr class="payment_row" <?php if ( ! $use_payments ) echo 'style="display:none"' ?>>
		<th scope="row"><label for="return"><?php _e( 'Thank You Page', 'appointments' ) ?></label></th>
		<td colspan="2">
			<?php wp_dropdown_pages( array(
				"show_option_none"   => __( 'Home page', 'appointments' ),
				"option_none_value " => 0,
				"name"               => "return",
				"selected"           => @$options["return"]
			) ) ?>
			<p class="description"><?php _e( 'The page that client will be returned when he clicks the return link on Paypal website.', 'appointments' ) ?></p>
		</td>
	</tr>

	<tr class="payment_row" <?php if ( ! $use_payments ) echo 'style="display:none"' ?>>
		<th scope="row"><label for="percent_deposit"><?php _e( 'Deposit (%)', 'appointments' ) ?></label></th>
		<td colspan="2">
			<input value="<?php echo esc_attr( @$options['percent_deposit'] ); ?>" style="width:50px" name="percent_deposit" id="percent_deposit" type="text"/>
			<p class="description"><?php _e( 'You may want to ask a certain percentage of the service price as deposit, e.g. 25. Leave this field empty to ask for full price.', 'appointments' ) ?></p>
		</td>
	</tr>

	<tr class="payment_row" <?php if ( ! $use_payments ) echo 'style="display:none"' ?>>
		<th scope="row"><label for="fixed_deposit"><?php _e( 'Deposit (fixed)', 'appointments' ) ?></label></th>
		<td colspan="2">
			<input value="<?php echo esc_attr( @$options['fixed_deposit'] ); ?>" style="width:50px" name="fixed_deposit" id="fixed_deposit" type="text"/>
			<p class="description"><?php _e( 'Same as above, but a fixed deposit will be asked from the client per appointment. If both fields are filled, only the fixed deposit will be taken into account.', 'appointments' ) ?></p>
		</td>
	</tr>

	<tr class="payment_row" <?php if ( ! $use_payments ) echo 'style="display:none"' ?>>
		<th scope="row"><label for="allow_free_autoconfirm"><?php _e( 'Allow zero-priced appointments auto-confirm', 'appointments' ) ?></label></th>
		<td colspan="2">
			<input value="1" <?php checked( true, @$options['allow_free_autoconfirm'] ); ?> name="allow_free_autoconfirm" id="allow_free_autoconfirm" type="checkbox"/>
			<p class="description"><?php _e( 'Allow auto-confirm for zero-priced appointments in a paid environment.', 'appointments' ) ?></p>
		</td>
	</tr>
	
	<?php
	/**
	 * Integrations or add-ons can use this action to add their own payment
	 * settings to the form.
	 */
	do_action( 'app_settings_form_payment', $options, $use_payments );
	?>

</table>
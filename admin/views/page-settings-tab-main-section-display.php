<table class="form-table">
	<tr class="appointment-create-page">
		<th scope="row"><label for="make_an_appointment"><?php _e( 'Create an Appointment Page', 'appointments' ) ?></label></th>
		<td>
			&nbsp;<?php _e( 'with', 'appointments' ) ?>&nbsp;
			<label for="app_page_type" class="screen-reader-text"><?php _e( 'Create an appointments date with this format', 'appointments' ); ?></label>
			<select>
				<option value="one_month"><?php _e( 'current month\'s schedule', 'appointments' ) ?></option>
				<option value="two_months" <?php selected( 'two_months' == @$options['app_page_type'] ); ?>><?php _e( 'current and next month\'s schedules', 'appointments' ) ?></option>
				<option value="one_week" <?php selected( 'one_week' == @$options['app_page_type'] ); ?>><?php _e( 'current week\'s schedule', 'appointments' ) ?></option>
				<option value="two_weeks" <?php selected( 'two_weeks' == @$options['app_page_type'] ); ?>><?php _e( 'current and next week\'s schedules', 'appointments' ) ?></option>
            </select>
            <a href="#" data-action="make_an_appointment_page" class="button" data-nonce="<?php echo wp_create_nonce( 'appointment-create-page' ); ?>"><?php esc_html_e( 'Create page!', 'appointments' ); ?></a>
		</td>
	</tr>

	<tr>
		<th scope="row"><label for="show_legend"><?php _e( 'Show Legend', 'appointments' ) ?></label></th>
		<td>
            <?php _appointments_html_chceckbox( $options, 'show_legend' ) ?>
			<p class="description"><?php _e( 'Whether to display description fields above the pagination area.', 'appointments' ) ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row"><label for="color_set"><?php _e( 'Color Set', 'appointments' ) ?></label></th>
		<td>
			<select name="color_set" id="color_set">
				<option value="1" <?php selected( @$options['color_set'] == 1 ); ?>><?php _e( 'Preset 1', 'appointments' ) ?></option>
				<option value="2" <?php selected( @$options['color_set'] == 2 ); ?>><?php _e( 'Preset 2', 'appointments' ) ?></option>
				<option value="3" <?php selected( @$options['color_set'] == 3 ); ?>><?php _e( 'Preset 3', 'appointments' ) ?></option>
				<option value="0" <?php selected( @$options['color_set'] == 0 ); ?>><?php _e( 'Custom', 'appointments' ) ?></option>
			</select>

			<p class="preset_samples" <?php if ( @$options['color_set'] == 0 ) { echo 'style="display:none"'; } ?>>
				<?php foreach ( $appointments->get_classes() as $class => $name ) :  ?>
					<label>
						<span> <?php echo $name ?>: </span>
								<span>
									<a href="javascript:void(0)" class="pickcolor <?php echo $class ?> hide-if-no-js" <?php if ( @$options['color_set'] != 0 ) {
										echo 'style="background-color:#' . $appointments->get_preset( $class, $options['color_set'] ) . '"'; } ?>>
									</a>
								</span>
					</label>
				<?php endforeach; ?>
			</p>
		</td>
	</tr>


	<tr class="custom_color_row" <?php if ( @$options['color_set'] != 0 ) { echo 'style="display:none"'; } ?>>
		<th scope="row"><?php _e( 'Custom Color Set', 'appointments' ) ?></th>
        <td colspan="2">
            <span style="display: flex">
			<?php foreach ( $appointments->get_classes() as $class => $name ) :  ?>
				<label>
                    <span><?php echo $name ?>:</span>
                    <span>
                        <input style="width:50px" type="text" class="colorpicker_input" name="<?php echo $class ?>_color" id="<?php echo $class ?>_color" value="<?php if ( isset( $options[ $class . '_color' ] ) ) { echo $options[ $class . '_color' ]; } ?>"/>
                    <span>
				</label>
            <?php endforeach; ?>
            </span>
			<span class="description"><?php _e( 'If you have selected Custom color set, for each cell enter 3 OR 6-digit Hex code of the color manually without # in front or use the colorpicker.', 'appointments' ) ?></span>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row"><?php _e( 'Require these from the client:', 'appointments' ) ?></th>
		<td colspan="2">
			<input type="checkbox" id="ask_name" name="ask_name" <?php if ( isset( $options['ask_name'] ) && $options['ask_name'] ) { echo 'checked="checked"'; } ?> />&nbsp;<label for="ask_name"><?php echo $appointments->get_field_name( 'name' ) ?></label>
			&nbsp;&nbsp;&nbsp;
			<input type="checkbox" id="ask_email" name="ask_email" <?php if ( isset( $options['ask_email'] ) && $options['ask_email'] ) { echo 'checked="checked"'; } ?> />&nbsp;<label for="ask_email"><?php echo $appointments->get_field_name( 'email' ) ?></label>
			&nbsp;&nbsp;&nbsp;
			<input type="checkbox" id="ask_phone" name="ask_phone" <?php if ( isset( $options['ask_phone'] ) && $options['ask_phone'] ) { echo 'checked="checked"'; } ?> />&nbsp;<label for="ask_phone"><?php echo $appointments->get_field_name( 'phone' ) ?></label>
			&nbsp;&nbsp;&nbsp;
			<input type="checkbox" id="ask_address" name="ask_address" <?php if ( isset( $options['ask_address'] ) && $options['ask_address'] ) { echo 'checked="checked"'; } ?> />&nbsp;<label for="ask_address"><?php echo $appointments->get_field_name( 'address' ) ?></label>
			&nbsp;&nbsp;&nbsp;
			<input type="checkbox" id="ask_city" name="ask_city" <?php if ( isset( $options['ask_city'] ) && $options['ask_city'] ) { echo 'checked="checked"'; } ?> />&nbsp;<label for="ask_city"><?php echo $appointments->get_field_name( 'city' ) ?></label>
			&nbsp;&nbsp;&nbsp;
			<input type="checkbox" id="ask_note" name="ask_note" <?php if ( isset( $options['ask_note'] ) && $options['ask_note'] ) { echo 'checked="checked"'; } ?> />&nbsp;<label for="ask_note"><?php echo $appointments->get_field_name( 'note' ) ?></label>
			&nbsp;&nbsp;&nbsp;
			<br/>
			<p class="description"><?php _e( 'The selected fields will be available in the confirmation area and they will be asked from the client. If selected, filling of them is mandatory (except note field).', 'appointments' ) ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row"><label for="additional_css"><?php _e( 'Additional css Rules', 'appointments' ) ?></label></th>
		<td colspan="2">
			<textarea class="widefat" rows="6" name="additional_css" id="additional_css"><?php echo esc_textarea( $options['additional_css'] ); ?></textarea>
			<p class="description"><?php _e( 'You can add css rules to customize styling. These will be added to the front end appointment page only.', 'appointments' ) ?></p>
		</td>
	</tr>
	<?php do_action( 'app-settings-display_settings' ); ?>
</table>

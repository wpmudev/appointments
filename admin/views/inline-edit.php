<tr class="inline-edit-row inline-edit-row-post quick-edit-row-post">
	<td colspan="<?php echo $columns; ?>" class="colspanchange">
		<fieldset class="inline-edit-col-left" style="width:33%">
			<div class="inline-edit-col">
				<h4><?php esc_html_e( 'CLIENT', 'appointments' ); ?></h4>
				<label>
					<span class="title"><?php esc_html_e('User', 'appointments'); ?></span>
					<?php echo $dropdown_users; ?>
				</label>
				<?php foreach ( $user_fields as $field ): ?>
					<?php $name = 'name' === $field ? 'cname' : $field; ?>
					<label>
						<span class="title"><?php echo $appointments->get_field_name($field); ?></span>
						<span class="input-text-wrap">
									<input type="text" name="<?php echo esc_attr( $name ); ?>" class="ptitle" value="<?php echo esc_attr(stripslashes($app->$field)); ?>" />
								</span>
					</label>
				<?php endforeach; ?>
				<?php do_action( 'app-appointments_list-edit-client', '', $app ); ?>
			</div>
		</fieldset>
		<fieldset class="inline-edit-col-center" style="width:28%">
			<div class="inline-edit-col">
				<h4><?php esc_html_e('SERVICE', 'appointments'); ?></h4>
				<label>
					<span class="title"><?php esc_html_e('Name', 'appointments'); ?></span>
					<select name="service">
						<?php foreach ( $services as $service ): ?>
							<option value="<?php echo $service->ID; ?>" <?php selected( $app->service, $service->ID ); ?>><?php echo esc_html( $service->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<span class="title"><?php esc_html_e('Provider', 'appointments'); ?></span>
					<select name="worker">
						<option value="0"><?php esc_html_e('No specific provider', 'appointments'); ?></option>
						<?php foreach ( $workers as $worker ): ?>
							<option value="<?php echo $worker->ID; ?>" <?php selected( $app->worker, $worker->ID ); ?>><?php echo esc_html( appointments_get_worker_name( $worker->ID, false ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<span class="title"><?php esc_html_e('Price', 'appointments'); ?></span>
					<span class="input-text-wrap">
								<input type="text" name="price" style="width:50%" class="ptitle" value="<?php echo esc_attr($app->price); ?>" />
							</span>
				</label>
				<?php do_action('app-appointments_list-edit-services', '', $app); ?>
			</div>
		</fieldset>
		<fieldset class="inline-edit-col-right" style="width:38%">
			<div class="inline-edit-col">
				<h4><?php esc_html_e('APPOINTMENT', 'appointments'); ?></h4>
				<?php if ( $app_id ): ?>
					<label>
						<span class="title"><?php esc_html_e('Created', 'appointments'); ?></span>
						<span class="input-text-wrap" style="height:26px;padding-top:4px;">
                            <?php echo $app->get_formatted_created_date(); ?>
                        </span>
					</label>
				<?php endif; ?>
				<label style="float:left;width:65%">
					<span class="title"><?php esc_html_e('Start', 'appointments'); ?></span>
					<span class="input-text-wrap" >
								<input type="text" name="date" class="datepicker" size="12" placeholder="YYYY-MM-DD" value="<?php echo esc_attr( $start_date_timestamp ); ?>"  />
							</span>
				</label>
				<label style="float:left;width:30%; padding-left:5px;">
                    <select name="time" >
                        <option><?php esc_html_e( 'Unknown', 'appointments' ); ?></option>
<?php
						$_start_time = $app_id ? $app->get_start_time() : '';
$slots = $appointments->_get_timetable_slots( $app->start, 0 );
foreach( $slots as $slot ) {
    printf(
        '<option value="%s" %s>%s</option>',
        esc_attr( $slot['hours'] ),
        selected( $_start_time, $slot['hours'], false ),
        esc_html( $slot['hours'] )
    );
}
?>
					</select>
				</label>
				<div style="clear:both; height:0"></div>
				<?php if ( $app_id ): ?>
					<label style="margin-top:8px">
						<span class="title"><?php  esc_html_e('End', 'appointments'); ?></span>
						<span class="input-text-wrap" style="height:26px;padding-top:4px;">
									<?php echo $end_datetime; ?>
								</span>
					</label>
				<?php endif; ?>
				<label>
					<span class="title"><?php echo $appointments->get_field_name('note'); ?></span>
					<textarea name="note" cols="22" rows=1"><?php echo esc_textarea( stripslashes( $app->note ) ); ?></textarea>
				</label>
				<label>
					<span class="title"><?php esc_html_e( 'Status', 'appointments'); ?></span>
					<span class="input-text-wrap">
								<select name="status">
									<?php foreach ( appointments_get_statuses() as $status => $status_name ): ?>
										<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $app->status, $status ); ?>><?php echo esc_html( $status_name ); ?></option>
									<?php endforeach; ?>
								</select>
							</span>
				</label>
				<label>
					<span class="title"><?php esc_html_e( 'Confirm', 'appointments' ); ?></span>
					<span class="input-text-wrap">
								<?php if ( $app_id && $confirmation_sent ): ?>
									<input type="checkbox" name="resend" value="1" />&nbsp;<?php esc_html_e('(Re)send confirmation email', 'appointments' ); ?>
								<?php else: ?>
									<input type="checkbox" name="resend" value="1" checked="checked" />&nbsp;<?php esc_html_e('Send confirmation email', 'appointments' ); ?>
								<?php endif; ?>
							</span>
				</label>
			</div>
		</fieldset>
		<p class="submit inline-edit-save">
			<a href="javascript:void(0)" title="<?php echo esc_attr_x('Cancel', 'Drop current action', 'appointments'); ?>" class="button-secondary cancel alignleft"><?php echo esc_html_x('Cancel', 'Drop current action', 'appointments'); ?></a>
			<?php if ( 'reserved' == $app->status ): ?>
				<a style="display:none" title="<?php esc_attr_e('GCal reserved appointments cannot be edited here. Edit them in your Google calendar.', 'appointments'); ?>" class="button-primary save alignright" data-app-id="<?php echo $app->ID; ?>"><?php esc_html_e('Save / Update','appointments'); ?></a>
			<?php else: ?>
				<a href="javascript:void(0)" title="<?php esc_attr_e('Click to save or update', 'appointments'); ?>" class="button-primary save alignright" data-app-id="<?php echo $app->ID; ?>"><?php esc_html_e('Save / Update','appointments'); ?></a>
			<?php endif; ?>
			<img class="waiting" style="display:none;" src="<?php echo esc_url( admin_url('images/spinner.gif') ); ?>" alt="">
			<input type="hidden" name="app_id" value="<?php echo esc_attr($app->ID); ?>">
			<span class="error" style="display:none"></span>
			<br class="clear">
		</p>
	</td>
</tr>

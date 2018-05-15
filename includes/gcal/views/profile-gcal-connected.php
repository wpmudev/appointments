<p><?php _e( 'Select the Calendar you want to work with Appointments.', 'appointments' ); ?></p>

<table class="form-table">
	<tr>
		<th scope="row">
			<label for="app-calendar"><?php _e( 'Calendar', 'appointments' ); ?></label>
		</th>
		<td>
            <?php if ( is_array( $calendars ) ) { ?>
			<select name="gcal_selected_calendar" id="app-calendar">
				<option value=""><?php _e( '-- Select a Calendar --', 'appointments' ); ?></option>
				<?php foreach ( $calendars as $calendar ) :  ?>
					<option value="<?php echo esc_attr( $calendar['id'] ); ?>" <?php selected( $selected_calendar, $calendar['id'] ); ?>>
						<?php echo $calendar['summary']; ?>
					</option>
				<?php endforeach; ?>
            </select>
<?php } else { ?>
            <div class="notice notice-error inline">
                <p><?php _e( 'There was an error loading your calendars.', 'appointments' ); ?></p>
            </div>
<?php } ?>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="app-api-mode"><?php _e( 'API Mode', 'appointments' ); ?></label>
		</th>
		<td>
			<select name="gcal_api_mode" id="app-api-mode">
				<option value="none"><?php _e( 'Integration disabled', 'appointments' ) ?></option>
				<option value="gcal2app" <?php selected( $api_mode, 'gcal2app' ); ?>>
					<?php _e( 'A+ <- GCal (Only import appointments)', 'appointments' ) ?>
				</option>
				<option value="app2gcal" <?php selected( $api_mode, 'app2gcal' ); ?>>
					<?php _e( 'A+ -> GCal (Only export appointments)', 'appointments' ) ?>
				</option>
				<option value="sync" <?php selected( $api_mode == 'sync' ); ?>>
					<?php _e( 'A+ <-> GCal (Synchronization)', 'appointments' ) ?>
				</option>
			</select>
			<br />
			<span class="description"><?php _e( 'Select method of integration. A+ -> GCal setting sends appointments to your selected Google calendar, but events in your Google Calendar account are not imported to Appointments+ and thus they do not reserve your available working times. A+ <-> GCal setting works in both directions.', 'appointments' ) ?></span>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="gcal_summary"><?php _e( 'Event summary (name)', 'appointments' ) ?></label></th>
		<td>
			<input id="gcal_summary" value="<?php echo esc_attr( $gcal_summary ); ?>" size="90" name="gcal_summary" type="text"/>
		</td>
	</tr>

	<tr>
		<th scope="row"><label for="gcal_description"><?php _e( 'Event description', 'appointments' ) ?></label></th>
		<td>
			<textarea rows="6" cols="30" class="widefat" name="gcal_description" id="gcal_description"><?php echo esc_textarea( $gcal_description ); ?></textarea>
			<br />
						<span class="description">
							<?php _e( 'For the above 2 fields, you can use the following placeholders which will be replaced by their real values:', 'appointments' ) ?>&nbsp;SITE_NAME, CLIENT, SERVICE, SERVICE_PROVIDER, DATE_TIME, PRICE, DEPOSIT, PHONE, NOTE, ADDRESS, EMAIL <?php _e( "(Client's email)", 'appointments' )?>
							<br />
							<?php _e( 'Please be careful about privacy if your calendar is public.', 'appointments' ); ?>
						</span>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<?php _e( 'Reset Calendar Credentials', 'appointments' ); ?>
		</th>
		<td>
			<a class="button" href="<?php echo esc_url( add_query_arg( 'reset-user-gcal', 'true' ) ); ?>"><?php _e( 'Reset', 'appointments' ); ?></a>
		</td>
	</tr>
</table>
<input type="hidden" name="gcal_action" value="gcal-settings">

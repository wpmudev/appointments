<h3><?php _e( 'Google Calendar API: Select Your Calendar', 'appointments' ); ?></h3>
<p><?php _e( 'Select the Calendar you want to work with Appointments. This setting is optional as every Service Provider can select their own calendar from their Profile Settings.', 'appointments' ); ?></p>

<table class="form-table">
	<tr>
		<th scope="row">
			<label for="app-calendar"><?php _e( 'Calendar', 'appointments' ); ?></label>
		</th>
		<td>
			<?php if ( is_array( $calendars ) ): ?>
				<select name="gcal_selected_calendar" id="app-calendar">
					<option value=""><?php _e( '-- Select a Calendar --', 'appointments' ); ?></option>
					<?php foreach ( $calendars as $calendar ): ?>
						<option value="<?php echo esc_attr( $calendar['id'] ); ?>" <?php selected( $selected_calendar, $calendar['id'] ); ?>>
							<?php echo $calendar['summary']; ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php else: ?>
				<p style="color:#dc3232"><?php _e( 'There was an error loading your calendars.', 'appointments' ); ?></p>
			<?php endif; ?>
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
			<span class="description"><?php _e('Select method of integration. A+ -> GCal setting sends appointments to your selected Google calendar, but events in your Google Calendar account are not imported to Appointments+ and thus they do not reserve your available working times. A+ <-> GCal setting works in both directions.', 'appointments') ?></span>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row" ><label for="gcal_api_allow_worker"><?php _e('Allow Service Providers for Google Calendar API Integration', 'appointments')?></label></th>
		<td colspan="2">
			<select name="gcal_api_allow_worker" id="gcal_api_allow_worker">
				<option value="no" <?php selected( $allow_worker, false ); ?>><?php _e('No', 'appointments')?></option>
				<option value="yes" <?php selected( $allow_worker ); ?>><?php _e('Yes', 'appointments')?></option>
			</select>
			<br />
			<span class="description"><?php _e('Whether you let your service providers to integrate with their own Google Calendar account using their profile page.', 'appointments') ?></span>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row" ><label for="gcal_api_scope"><?php _e('Appointments will be sent to Google Calendar for', 'appointments')?></label></th>
		<td colspan="2">
			<select name="gcal_api_scope">
				<option value="all" <?php selected( $api_scope, 'all' ); ?>><?php _e('All', 'appointments')?></option>
				<option value="no_preference" <?php echo ( $api_scope != 'all' ) ? "selected='selected'" : ''; ?>><?php _e('No preference case', 'appointments')?></option>
			</select>
			<br />
			<span class="description"><?php _e('If you select "All", any appointment made from this website will be sent to the selected calendar. If you select "No preference case", only appointments which do not have an assigned service provider will be sent.', 'appointments') ?></span>
		</td>
	</tr>

	<tr>
		<th scope="row"><label for="gcal_summary"><?php _e('Event summary (name)', 'appointments') ?></label></th>
		<td>
			<input id="gcal_summary" value="<?php echo esc_attr( $gcal_summary ); ?>" size="90" name="gcal_summary" type="text"/>
		</td>
	</tr>

	<tr>
		<th scope="row"><label for="gcal_description"><?php _e('Event description', 'appointments') ?></label></th>
		<td>
			<textarea rows="6" cols="30" class="widefat" name="gcal_description" id="gcal_description"><?php echo esc_textarea( $gcal_description ); ?></textarea>
			<br />
			<span class="description">
				<?php _e('For the above 2 fields, you can use the following placeholders which will be replaced by their real values:', 'appointments') ?>&nbsp;SITE_NAME, CLIENT, SERVICE, SERVICE_PROVIDER, DATE_TIME, PRICE, DEPOSIT, PHONE, NOTE, ADDRESS, EMAIL <?php _e("(Client's email)", "appointments")?>
				<br />
				<?php _e('Please be careful about privacy if your calendar is public.', 'appointments'); ?>
			</span>
		</td>
	</tr>

	<?php if ( ( 'sync' == $api_mode ) && $selected_calendar ): ?>
		<tr>
			<th scope="row">
				<?php _e( 'Import and Update', 'appointments' ); ?>
			</th>
			<td>
				<a id="app-gcal-import" class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'gcal_import', 1 ), 'app-gcal-import-export' ) ); ?>"><?php _e( 'Import and Update Events from GCal', 'appointments' ); ?></a>
				<span id="app-gcal-import-result"></span>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php _e( 'Export and Update', 'appointments' ); ?>
			</th>
			<td>
				<a id="app-gcal-export" class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'gcal_export', 1 ), 'app-gcal-import-export' ) ); ?>"><?php _e( 'Export and Update Events to GCal', 'appointments' ); ?></a>
				<span id="app-gcal-export-result"></span>
			</td>
		</tr>
	<?php endif; ?>
</table>

<style>
	#app-gcal-export-result,
	#app-gcal-import-result {
		display: inline-block;
		line-height: 25px;
		margin-left: 10px;
	}
</style>
<script>
	( function( $ ) {
		var exportTotal = <?php echo $apps_count; ?>;
		var exportButton = $( '#app-gcal-export');
		var exportResult = $('#app-gcal-export-result');

		var importButton = $( '#app-gcal-import' );
		var importResult = $('#app-gcal-import-result');

		function export_apps( offset ) {
			if ( ! offset ) {
				offset = 0;
			}

			exportButton.attr( 'disabled', true );
			exportResult.text( Math.min( offset, exportTotal ) + ' / ' + exportTotal );

			$.ajax({
					url: ajaxurl,
					method: 'post',
					data: {
						offset: offset,
						action: 'app_gcal_export'
					}
				})
				.always( function( data ) {
					console.log(data);
					if ( ! data.success ) {
						export_apps( data.data.offset );
					}
					else {
						exportButton.attr( 'disabled', false );
						exportResult.hide();
					}
				});
		}

		function import_apps() {
			importButton.attr( 'disabled', true );
			importResult.text( '<?php _e( "Importing Appointments...", "appointments" ); ?>' );

			$.ajax({
					url: ajaxurl,
					method: 'post',
					data: {
						action: 'app_gcal_import'
					}
				})
				.always( function( data ) {
					importButton.attr( 'disabled', false );
					importResult.text( data.message );
				});
		}

		exportButton.click( function(e) {
			e.preventDefault();
			export_apps();
		} );

		importButton.click( function( e ) {
			e.preventDefault();

			if ( confirm( '<?php _e( "Are you sure? Appointments in Appointments > Reserved by GCal that do not exist anymore in your calendar will be deleted (Only those with \'Reserved by GCal\' status)", "appointments" ); ?>' ) ) {
				import_apps();
			}

		})

	}( jQuery ));
</script>

<?php wp_nonce_field( 'app-submit-gcalendar' ); ?>
<input type="hidden" name="action" value="step-3">
<?php submit_button( __( 'Reset API Credentials', 'appointments' ), 'secondary', 'app-reset-gcalendar' ); ?>
<?php submit_button( __( 'Save Changes', 'appointments' ), 'primary', 'app-submit-gcalendar' ); ?>
<?php
global $wpdb;

$appointments = appointments();
$workers     = appointments_get_workers();
$services = appointments_get_services();
$min_time     = $appointments->get_min_time();

$pages = apply_filters( 'app-biography_pages-get_list', array() );
if ( empty( $pages ) ) {
	$pages = get_pages( apply_filters( 'app_pages_filter', array() ) );
}
?>

<p><?php _e( '<i>Here you should define your workers for which your client will be making appointments. <b>There must be at least one service defined.</b> Capacity is the number of customers that can take the service at the same time. Enter 0 for no specific limit (Limited to number of service providers, or to 1 if no service provider is defined for that service). Price is only required if you request payment to accept appointments. You can define a description page for the service you are providing.</i>', 'appointments') ?></p>


<form method="post" action="">

	<table class="widefat fixed" id="workers-table" >
		<tr>
			<th style="width:5%"><?php _e( 'ID', 'appointments') ?></th>
			<th style="width:25%"><?php _e( 'Service Provider', 'appointments') ?></th>
			<th style="width:10%"><?php _e( 'Dummy?', 'appointments') ?></th>
			<th style="width:10%"><?php  echo __( 'Additional Price', 'appointments') . ' ('. $appointments->options['currency']. ')' ?></th>
			<th style="width:25%"><?php _e( 'Services Provided*', 'appointments') ?></th>
			<th style="width:25%"><?php _e( 'Bio page', 'appointments') ?></th>
		</tr>
		<tr>
			<td colspan="6">
				<span class="description" style="font-size:11px"><?php _e('* <b>You must select at least one service, otherwise provider will not be saved!</b>', 'appointments') ?></span>
			</td>
		</tr>
		<?php if ( $workers ): ?>
			<?php foreach ( $workers as $worker ): ?>
				<?php $workers_dropdown = wp_dropdown_users( array(
					'echo'     => 0,
					'show'     => 'user_login',
					'selected' => $worker->ID,
					'name'     => 'workers[' . $worker->ID . '][user]',
					'exclude'  => apply_filters( 'app_filter_providers', null )
				) ); ?>
				<tr>
					<td>
						<?php echo $worker->ID; ?>
					</td>
					<td>
						<label for="worker-<?php echo $worker->ID; ?>-name" class="screen-reader-text"><?php _e( 'Service Provider', 'appointments' ); ?></label>
						<?php echo $workers_dropdown; ?>
						<?php echo stripslashes( apply_filters( 'app-settings-workers-worker-name', '', $worker->ID, $worker ) ); ?>
					</td>
					<td>
						<label for="workers-<?php echo $worker->ID; ?>-dummy" class="screen-reader-text"><?php _e( 'Dummy?', 'appointments' ); ?></label>
						<input type="checkbox" id="workers-<?php echo $worker->ID; ?>-dummy" name="workers[<?php echo $worker->ID; ?>][dummy]" <?php checked( $worker->is_dummy() ); ?> />
					</td>
					<td>
						<label for="worker-<?php echo $worker->ID; ?>-price" class="screen-reader-text"><?php _e( 'Additional Price', 'appointments' ); ?></label>
						<input id="worker-<?php echo $worker->ID; ?>-price" class="widefat" type="text" name="workers[<?php echo $worker->ID; ?>][price]" value="<?php echo esc_attr( $worker->price ); ?>" />
					</td>
					<td>
						<?php if ( $services ): ?>
							<label for="workers-<?php echo $worker->ID; ?>-services_provided" class="screen-reader-text"><?php _e( 'Services Provided', 'appointments' ); ?></label>
							<select class="add_worker_multiple" style="width:280px" multiple="multiple" name="workers[<?php echo $worker->ID; ?>][services_provided][]" id="workers-<?php echo $worker->ID; ?>-services_provided">
								<?php foreach ( $services as $service ): ?>
									<?php
										$services_provided = $worker->services_provided;
										$title = stripslashes( $service->name );
									?>
									<option value="<?php echo $service->ID; ?>" <?php selected( in_array( $service->ID, $services_provided ) ); ?>><?php echo esc_html( $title ); ?></option>
								<?php endforeach; ?>
							</select>
						<?php else: ?>
							<?php _e( 'No services defined', 'appointments' ); ?>
						<?php endif; ?>
					</td>
					<td>
						<label for="worker-<?php echo $worker->ID; ?>-page" class="screen-reader-text"><?php _e( 'Description Page', 'appointments' ); ?></label>
						<select id="worker-<?php echo $worker->ID; ?>-page" name="workers[<?php echo $worker->ID; ?>][page]">
							<option value="0"><?php esc_html_e( 'None', 'appointments' ); ?></option>
							<?php foreach( $pages as $page ): ?>
								<option value="<?php echo $page->ID; ?>" <?php selected( $worker->page == $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php else: ?>
			<tr class="no_workers_defined"><td colspan="4"><?php _e( 'No service providers defined', 'appointments' ); ?></td></tr>
		<?php endif; ?>

	</table>

	<?php _e( '<i>Tip: To remove a service provider, uncheck all "Services Provided" selections and save.</i>', 'appointments' ); ?>

	<?php if ( $workers ): ?>
		<?php _appointments_settings_submit_block( $tab ); ?>
	<?php endif; ?>

	<?php wp_nonce_field( 'update_app_settings', 'app_nonce' ); ?>

</form>
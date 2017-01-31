<?php
global $wpdb;

$appointments = appointments();
$currency     = appointments_get_option( 'currency' );
$services     = appointments_get_services();
$min_time     = $appointments->get_min_time();
$k_max        = apply_filters( 'app_selectable_durations', min( 24, (int) ( 1440 / $min_time ) ) );

$pages = apply_filters( 'app-service_description_pages-get_list', array() );
if ( empty( $pages ) ) {
	$pages = get_pages( apply_filters( 'app_pages_filter', array() ) );
}
?>

<p><?php _e( '<i>Here you should define your services for which your client will be making appointments. <b>There must be at least one service defined.</b> Capacity is the number of customers that can take the service at the same time. Enter 0 for no specific limit (Limited to number of service providers, or to 1 if no service provider is defined for that service). Price is only required if you request payment to accept appointments. You can define a description page for the service you are providing.</i>', 'appointments') ?></p>


<form method="post" action="">

	<table class="widefat fixed" id="services-table" >
		<tr>
			<th style="width:5%"><?php _e( 'ID', 'appointments') ?></th>
			<th style="width:35%"><?php _e( 'Name', 'appointments') ?></th>
			<th style="width:10%"><?php _e( 'Capacity', 'appointments') ?></th>
			<th style="width:15%"><?php _e( 'Duration (mins)', 'appointments') ?></th>
			<th style="width:10%"><?php echo __( 'Price', 'appointments') . ' ('. $currency. ')' ?></th>
			<th style="width:25%"><?php _e( 'Description page', 'appointments') ?></th>
		</tr>
		<?php if ( $services ): ?>
			<?php foreach ( $services as $service ): ?>
				<tr>
					<td>
						<?php echo $service->ID; ?>
					</td>
					<td>
						<label for="service-<?php echo $service->ID; ?>-name" class="screen-reader-text"><?php _e( 'Service Name', 'appointments' ); ?></label>
						<input id="service-<?php echo $service->ID; ?>-name" class="widefat" type="text" name="services[<?php echo $service->ID; ?>][name]" value="<?php echo esc_attr( stripslashes( $service->name ) ); ?>"/>
						<?php echo stripslashes( apply_filters( 'app-settings-services-service-name', '', $service->ID ) ); ?>
					</td>
					<td>
						<label for="service-<?php echo $service->ID; ?>-capacity" class="screen-reader-text"><?php _e( 'Service Capacity', 'appointments' ); ?></label>
						<input id="service-<?php echo $service->ID; ?>-capacity" class="widefat" type="text" name="services[<?php echo $service->ID; ?>][capacity]" value="<?php echo esc_attr( $service->capacity ); ?>" />
					</td>
					<td>
						<label for="service-<?php echo $service->ID; ?>-duration" class="screen-reader-text"><?php _e( 'Service Duration', 'appointments' ); ?></label>
						<select id="service-<?php echo $service->ID; ?>-duration" name="services[<?php echo $service->ID; ?>][duration]">
							<?php for ( $k=1; $k<=$k_max; $k++ ): ?>
								<option <?php selected( ( $k * $min_time ) == $service->duration ); ?>><?php echo $k * $min_time; ?></option>
							<?php endfor; ?>
						</select>
					</td>
					<td>
						<label for="service-<?php echo $service->ID; ?>-price" class="screen-reader-text"><?php _e( 'Service Price', 'appointments' ); ?></label>
						<input id="service-<?php echo $service->ID; ?>-price" class="widefat" type="text" name="services[<?php echo $service->ID; ?>][price]" value="<?php echo esc_attr( $service->price ); ?>" />
					</td>
					<td>
						<label for="service-<?php echo $service->ID; ?>-page" class="screen-reader-text"><?php _e( 'Description Page', 'appointments' ); ?></label>
						<select id="service-<?php echo $service->ID; ?>-page" name="services[<?php echo $service->ID; ?>][page]">
							<option value="0"><?php esc_html_e( 'None', 'appointments' ); ?></option>
							<?php foreach( $pages as $page ): ?>
								<option value="<?php echo $page->ID; ?>" <?php selected( $service->page == $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php else: ?>
			<tr class="no_services_defined"><td colspan="4"><?php _e( 'No services defined', 'appointments' ); ?></td></tr>
		<?php endif; ?>

	</table>

	<p><?php _e( '<i>Tip: To delete a service, just clear its name and save.</i>', 'appointments' ); ?></p>

	<?php if ( $services ): ?>
		<?php _appointments_settings_submit_block( $tab ); ?>
	<?php endif; ?>

	<?php wp_nonce_field( 'update_app_settings', 'app_nonce' ); ?>

</form>
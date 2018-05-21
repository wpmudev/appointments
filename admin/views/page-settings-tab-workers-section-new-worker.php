<?php
global $wpdb;

$appointments = appointments();
$workers     = appointments_get_workers();
$services     = appointments_get_services();

$pages = apply_filters( 'app-biography_pages-get_list', array() );
if ( empty( $pages ) ) {
	$pages = get_pages( apply_filters( 'app_pages_filter', array() ) );
}

$exclude = array();
foreach( $workers as $worker ) {
    $exclude[] = $worker->ID;
}
$workers_dropdown = wp_dropdown_users( array(
	'echo'    => 0,
	'show'    => 'user_login',
	'name'    => 'user',
	'id'      => 'worker-user',
	'exclude' => apply_filters( 'app_filter_providers', $exclude )
) );

?>
<form action="" method="post" class="add-new-service-provider">
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="worker-user"><?php _e( 'Service Provider', 'appointments' ); ?></label>
			</th>
			<td>
				<?php echo $workers_dropdown; ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="worker-dummy"><?php _e( 'Dummy?', 'appointments' ); ?></label>
			</th>
			<td>
				<input id="worker-dummy" class="widefat" type="checkbox" name="dummy"/>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="worker-price"><?php _e( 'Additional Price', 'appointments' ); ?></label>
			</th>
			<td>
				<input id="worker-price" type="text" name="price" value="" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="worker-services"><?php _e( 'Services Provided', 'appointments' ); ?></label>
			</th>
			<td>
				<?php if ( $services ): ?>
					<label for="services_provided" class="screen-reader-text"><?php _e( 'Services Provided', 'appointments' ); ?></label>
					<select class="add_worker_multiple" style="width:280px" multiple="multiple" name="services_provided[]" id="services_provided">
						<?php foreach ( $services as $service ): ?>
							<?php $title = stripslashes( $service->name ); ?>
							<option value="<?php echo $service->ID; ?>"><?php echo esc_html( $title ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php else: ?>
					<?php _e( 'No services defined', 'appointments' ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="worker-page"><?php _e( 'Description Page', 'appointments' ); ?></label>
			</th>
			<td>
				<select id="worker-page" name="worker_page">
					<option value="0"><?php esc_html_e( 'None', 'appointments' ); ?></option>
					<?php foreach( $pages as $page ): ?>
						<option value="<?php echo $page->ID; ?>"><?php echo esc_html( get_the_title( $page->ID ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php do_action( 'appointments_add_new_worker_form' ); ?>
	</table>

	<input type="hidden" name="action_app" value="add_new_worker">
	<?php wp_nonce_field( 'update_app_settings', 'app_nonce' ); ?>
	<?php submit_button( __( 'Add new Service Provider', 'appointments' ) ); ?>
</form>

<?php
$services     = appointments_get_services();
$pages = apply_filters( 'app-biography_pages-get_list', array() );
if ( empty( $pages ) ) {
	$pages = get_pages( apply_filters( 'app_pages_filter', array() ) );
}

?>
<form action="" method="post" class="add-new-service-provider">
    <input type="hidden" name="worker_user" id="worker-user" value="" />
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="worker-user"><?php esc_html_e( 'Service Provider', 'appointments' ); ?></label>
			</th>
			<td id="worker-user-display-name"></td>
		</tr>
		<tr>
			<th scope="row">
				<label for="worker-dummy"><?php esc_html_e( 'Dummy?', 'appointments' ); ?></label>
			</th>
			<td>
            <input id="worker-dummy" class="switch-button" type="checkbox" name="worker_dummy" data-on="<?php esc_html_e( 'Yes', 'appointments' ); ?>" data-off="<?php esc_html_e( 'No', 'appointments' ); ?>" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="worker-price"><?php esc_html_e( 'Additional Price', 'appointments' ); ?></label>
			</th>
			<td>
				<input id="worker-price" type="text" name="worker_price" value="" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="worker-services"><?php esc_html_e( 'Services Provided', 'appointments' ); ?></label>
			</th>
			<td>
<?php
if ( $services ) {
?>
                    <label for="services_provided" class="screen-reader-text"><?php _e( 'Services Provided', 'appointments' ); ?></label>
                    <select class="add_worker_multiple" style="width:280px" multiple="multiple" name="services_provided[]" id="services_provided">
<?php
foreach ( $services as $service ) {
	$title = stripslashes( $service->name );
	printf(
		'<option value="%s">%s</option>',
		esc_attr( $service->ID ),
		esc_html( $title )
	);
}
	echo '</select>';
} else {
	esc_html_e( 'No services defined', 'appointments' );
}
?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="worker-page"><?php esc_html_e( 'Description Page', 'appointments' ); ?></label>
			</th>
			<td>
				<select id="worker-page" name="worker_page">
					<option value="0"><?php esc_html_e( 'None', 'appointments' ); ?></option>
					<?php foreach ( $pages as $page ) :  ?>
						<option value="<?php echo esc_attr( $page->ID ); ?>"><?php echo esc_html( get_the_title( $page->ID ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php do_action( 'appointments_add_new_worker_form' ); ?>
	</table>

	<input type="hidden" name="action_app" value="update_worker">
	<?php wp_nonce_field( 'update_app_settings', 'app_nonce' ); ?>
	<?php submit_button( esc_html__( 'Save', 'appointments' ) ); ?>
</form>

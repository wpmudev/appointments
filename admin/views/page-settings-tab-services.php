<?php
global $appointments, $wpdb;
?>

<?php if ( isset( $_GET['added'] ) ): ?>
	<div class="updated">
		<p><?php _e( 'Service Added', 'appointments' ); ?></p>
	</div>
<?php endif; ?>

<?php foreach ( $sections as $section => $name ): ?>
	<?php $section_file = _appointments_get_settings_section_view_file_path( $tab, $section ); ?>
	<?php if ( $section_file ): ?>
		<div class="app-settings-section" id="app-settings-section-<?php echo $section; ?>">
			<h3><?php echo esc_html( $name ); ?></h3>
			<?php include_once( $section_file ); ?>
			<?php do_action( "appointments_settings_tab-$tab-section-$section" ); ?>
		</div>
	<?php endif; ?>
<?php endforeach; ?>

<?php do_action( "appointments_settings-$tab" ); ?>
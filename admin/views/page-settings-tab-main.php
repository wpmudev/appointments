<?php
global $appointments, $wpdb;

$options = appointments_get_options();
$base_times = $appointments->time_base();
$min_time_setting = isset( $options["min_time"] ) ? $options["min_time"] : '';
?>


<form method="post" action="">

	<?php foreach ( $sections as $section => $name ): ?>
		<?php $section_file = _appointments_get_settings_section_view_file_path( $tab, $section ); ?>
		<?php if ( $section_file ): ?>
			<div class="app-settings-section" id="app-settings-section-<?php echo $section; ?>">
				<h3><?php printf( _x( '%s Settings', 'Settings section', 'appointments' ), $name ); ?></h3>
				<?php include_once( $section_file ); ?>
				<?php do_action( "appointments_settings_tab-$tab-section-$section" ); ?>
			</div>
		<?php endif; ?>
	<?php endforeach; ?>

	<?php do_action( 'app-settings-after_advanced_settings' ); ?>
	<?php do_action( "appointments_settings-$tab" ); ?>

	<?php _appointments_settings_submit_block( $tab ); ?>
</form>
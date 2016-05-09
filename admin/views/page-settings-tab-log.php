<?php global $appointments; ?>
<div class="postbox">
	<div class="inside" id="app_log">
	<?php
		if ( is_writable( $appointments->uploads_dir ) ) {
			if ( file_exists( $appointments->log_file ) )
				echo nl2br( file_get_contents( $appointments->log_file ) );
			else
				echo __( 'There are no log records yet.', 'appointments' );
		}
		else
			echo __( 'Uploads directory is not writable.', 'appointments' );
		?>
	</div>
</div>

<form action="" method="post" id="clear-log-form">
	<?php _appointments_settings_submit_block( $tab, __( 'Clear log file', 'appointments' ), 'secondary' ); ?>
</form>

<script type="text/javascript">
jQuery(document).ready(function ($) {
	$('#clear-log-form').submit(function (e) {
		return confirm('<?php _e( "Are you sure to clear the log file?", "appointments" ); ?>');
	});
});
</script>
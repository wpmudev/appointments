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
<table class="form-table">
	<tr valign="top">
		<th scope="row" >
		<input type="button" id="log_clear_button" class="button-secondary" value="<?php esc_attr_e('Clear Log File', 'appointments') ?>" title="<?php esc_attr_e('Clicking this button deletes logs saved on the server', 'appointments') ?>" />
		</th>
	</tr>
</table>
<script type="text/javascript">
(function ($) {
$(function () {
	$('#log_clear_button').click(function() {
		if ( !confirm('<?php echo esc_js( __("Are you sure to clear the log file?","appointments") ) ?>') ) {return false;}
		else{
			$('.add-new-waiting').show();
			var data = {action: 'delete_log', nonce: '<?php echo wp_create_nonce() ?>'};
			$.post(ajaxurl, data, function(response) {
				$('.add-new-waiting').hide();
				if ( response && response.error ) {
					alert(response.error);
				}
				else{
					$("#app_log").html('<?php echo esc_js( __("Log file cleared...","appointments") ) ?>');
				}
			},'json');
		}
	});
});
})(jQuery);
</script>
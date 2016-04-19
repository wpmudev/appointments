<span class="description">
<?php _e( 'Appointments+ uses shortcodes to generate output on the front end. This gives you the flexibility to customize your appointment pages using the WordPress post editor without the need for php coding. There are several parameters of the shortcodes by which your customizations can be really custom. On the other hand, if you don\'t set these parameters, Appointments+ will still work properly. Thus, setting parameters is fully optional. <br /><b>Important:</b> You should temporarily turn off built in cache while making changes in the parameters or adding new shortcodes.', 'appointments' ); ?>
<br />
<?php _e( '<b>Note:</b> As default, all "title" parameters are wrapped with h3 tag. But of course you can override them by entering your own title text, with a different h tag, or without any tag. For example: <code>[app_monthly_schedule title="&lt;h4&gt;My schedule for START&lt;/h4&gt;"]</code>', 'appointments') ?>
</span>
<br />

<div id="app-shortcode-help">
	<?php do_action('app-shortcodes-shortcode_help'); ?>
</div>
<script>
(function ($) {
$(function () {
	var $items = $("#app-shortcode-help .postbox"),
		limit = Math.ceil($items.length/2)
	;
	$($items.slice(0, limit)).wrapAll('<div class="postbox-container app-postbox_container" />');
	$($items.slice(limit)).wrapAll('<div class="postbox-container app-postbox_container" />');
});
})(jQuery);
</script>

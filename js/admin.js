(function ($) {
$(function () {
	// --- Handle multiple breaks ---
	$(".app-add_break").on("click", function () {
		var me = $(this),
			tr = me.parents('tr').first(),
			new_tr = {}
		;

		tr.find("select").each(function () {
			var sel = $(this),
				name = sel.attr("name"),
				normalized_name = name.replace(/\[\d*\]$/, ''),
				others = $('[name^="' + normalized_name + '"]')
			;
			if (others.length) others.each(function () {
				$(this).attr("name", normalized_name + '[]');
			});
		});
		new_tr = tr.clone();

		new_tr
			.find("a.app-add_break").remove().end()
			.find("td:first").empty()
		;
		tr.after(new_tr);

		return false;
	});

	// --- Handle column meta toggles ---
	$(document).on('click', '.app-settings-column_meta_info-toggle', function () {
		var $me = $(this),
			$root = $me.parents(".app-settings-column_meta_info"),
			$content = $root.find(".app-settings-column_meta_info-content")
		;
		if ($content.is(":visible")) {
			$content.hide();
			$me.text($me.attr("data-off"));
		} else {
			$content.show();
			$me.text($me.attr("data-on"));
		}
		return false;
	});
});
})(jQuery);
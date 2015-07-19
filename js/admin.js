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
	// --- Drop repeated rows ---
	$(".app-working_hours-workhour_form tr.app-repeated").each(function () {
		var $me = $(this).find("td:last");
		$me.append('<a href="#remove-break" class="app-remove_break"></a>');
	});
	$(document).on("click", ".app-remove_break", function (e) {
		e.preventDefault();
		var $target = $(this).closest("tr.app-repeated");
		if (!$target.length) return false;
		$target.remove();
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


function export_to_gcal ($result) {
	$.post(_app_admin_data.ajax_url, {
		action: 'app-gcal-export_and_update'
	}, function (resp) {
		var remaining = parseInt(resp.remaining, 10),
			msg = resp.msg
		;
		$result.empty().append('<p>' + msg + '</p>');
		if (remaining && remaining > 0) export_to_gcal($result); // Recurse for another call.
	}, 'json');
}
$(function () {
	var $link = $(".app-gcal-export_and_update");
	if (!$link.length) return false;
	
	var $root = $link.closest("td"),
		$result = $root.find(".app-gcal-result")
	;
	$link.on("click", function (e) {
		e.preventDefault();
		e.stopPropagation();
		$result.empty().append('<p>' + _app_admin_data.strings.preparing_export + '</p>');
		export_to_gcal($result);
	});
});


// Hook up info trigger/target resolution
function toggle_target (e) {
	var $trigger = $(this),
		target = false,
		$target = false
	;
	if (e && e.preventDefault) e.preventDefault();
	if (e && e.stopPropagation) e.stopPropagation();

	target = $trigger.attr("data-target");
	if (!target) return false;

	$target = $(".app-info_target." + target);
	if (!$target.length) return false;

	if ($target.is(":visible")) $target.hide("fast");
	else $target.show("fast");

	return false;
}
$(function () {
	$(document).on("click", ".app-info_trigger", toggle_target);
});

})(jQuery);
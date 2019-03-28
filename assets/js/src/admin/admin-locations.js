(function ($) {


function create_new_location () {
	var $root = $("#app-locations-add_location"),
		$additive = $("#app-locations-new_location"),
		labels = _app_locations_data.model.labels,
		fields = _app_locations_data.model.fields,
		markup = ''
	;
	if ($additive.length) $additive.remove();

	markup += '<h3>' + labels.new_location + '</h3>';
	$.each(fields, function (field, label) {
		markup += '<label for="app-location-' + field + '">' +
			label +
			'&nbsp;' +
			'<input type="text" id="app-location-' + field + '" />' +
		'</label>';
	});

	markup += '<button class="button button-primary" type="button" id="app-locations-create_location">' +
		labels.add_location +
	'</button>';
	markup += '<button class="button button-secondary" type="button" id="app-locations-cancel_location">' +
		labels.cancel_editing +
	'</button>';

	$root.after(
		'<div id="app-locations-new_location">' + markup + '</div>'
	);

	$("#app-locations-create_location").on("click", function () {
		var location = {},
			$submit = $("#app-locations-save_locations"),
			tmp = $submit.before('<input type="hidden" name="locations[]" id="app-locations-added_location" value="" />'),
			$location = $("#app-locations-added_location")
		;
		$.each(fields, function (field, label) {
			location[field] = $("#app-location-" + field).val();
		});
		$location.val(JSON.stringify(location));
		$submit.trigger("click");
	});
	$("#app-locations-cancel_location").on("click", function () {
		$("#app-locations-new_location").remove();
		return false;
	});

	return false;
}

function edit_location () {
	var $me = $(this),
		$root = $("#app-locations-add_location"),
		$additive = $("#app-locations-new_location"),
		$data = $me.parents('li').find("input:hidden"),
		data = $data.val() ? JSON.parse($data.val()) : {},
		labels = _app_locations_data.model.labels,
		fields = _app_locations_data.model.fields,
		markup = ''
	;
	if ($additive.length) $additive.remove();

	markup += '<h3>' + labels.edit_location + '</h3>';
	$.each(fields, function (field, label) {
		markup += '<label for="app-location-' + field + '">' +
			label +
			'&nbsp;' +
			'<input type="text" id="app-location-' + field + '" value="' + data[field] + '" />' +
		'</label>';
	});

	markup += '<button class="button button-primary" type="button" id="app-locations-create_location">' +
		labels.save_location +
	'</button>';
	markup += '<button class="button button-secondary" type="button" id="app-locations-cancel_location">' +
		labels.cancel_editing +
	'</button>';

	$root.after(
		'<div id="app-locations-new_location">' + markup + '</div>'
	);

	$("#app-locations-create_location").on("click", function () {
		var location = data,
			$submit = $("#app-locations-save_locations")
		;
		$.each(fields, function (field, label) {
			location[field] = $("#app-location-" + field).val();
		});
		$data.val(JSON.stringify(location));
		$submit.trigger("click");
	});
	$("#app-locations-cancel_location").on("click", function () {
		$("#app-locations-new_location").remove();
		return false;
	});

	return false;
}

function delete_location () {
	var $me = $(this),
		$li = $me.parents('li')
	;
	$li.remove();
	return false;
}

function save_inline_appointment_data (e, data, $ctx) {
	$ctx = $ctx.length ? $ctx : $("body");
	var $location = $ctx.find('[name="location"]'),
		location_id = ($location.length ? $location.val() : '')
	;
	data['location'] = location_id;
	return false;
}

// Init
$(function () {
	if ("undefined" == typeof _app_locations_data) return false;
	$("#app-locations-add_location").on("click", create_new_location);
	$("#app-locations-list .app-locations-delete").on("click", delete_location);
	$("#app-locations-list .app-locations-edit").on("click", edit_location);

	// Inline save
	$(document).on('app-appointment-inline_edit-save_data', save_inline_appointment_data);

});
})(jQuery);
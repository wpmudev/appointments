(function ($) {

function load_service_description () {
	var sel = $(this),
		selected = sel.val()
	;
	if (!selected) return false;
	$(".app_service_excerpts")
		.find(".app_service_excerpt").hide().end()
		.find("#app_service_excerpt_" + selected).show()
	;
}

function load_worker_biography () {
	var sel = $(this),
		selected = sel.val()
	;
	if (!selected) return false;
	$(".app_worker_excerpts")
		.find(".app_worker_excerpt").hide().end()
		.find("#app_worker_excerpt_" + selected).show()
	;
}

function handle_submission () {
	var button = $(this),
		root = button.parents(".app_combo").first(),
		type = (button.is(".app_workers_button") ? 'times' : 'providers'),
		selection = button.parent().find("select"),
		selected = selection.length ? selection.val() : false
	;
	if ('providers' == type) {
		root.data('service_id', selected);
	} else {
		root.data('provider_id', selected);
	}
	if (!parseInt(selected,10) && 'providers' == type) return false;
	$.post(_appointments_data.ajax_url, {
		"action": "app_combo_list_" + type,
		"service_id": root.data('service_id'),
		"provider_id": root.data('provider_id')
	}).success(function (data) {
		root.html(data);
	});
	return false;
}

function handle_positive_response (root) {
	var delay = _app_ajax_shortcode.thank_you.delay ? _app_ajax_shortcode.thank_you.delay * 1000 : 0,
		location = (_app_ajax_shortcode.thank_you.refresh
			? window.location
			: false
		)
	;
	if (!location && _app_ajax_shortcode.thank_you.redirect) {
		location = _app_ajax_shortcode.thank_you.redirect;
	}

	// Show any thank you message
	if (_app_ajax_shortcode.thank_you.content) {
		root.empty().html(_app_ajax_shortcode.thank_you.content);
	}

	// Redirect, if requested
	if (location) {
		setTimeout(function () {
			window.location = location;
		}, delay);
	}
}

function handle_day_switch () {
	var selected_timetable = $(".app_timetable_"+$(this).find(".appointments_select_time").val());
	$(".app_timetable:not(selected_timetable)").hide();
	selected_timetable.show("slow");
	return false;
}

function handle_scheduling () {
	var td = $(this),
		is_wrapper = td.parents(".app_monthly_schedule_wrapper"),
		root = td.parents(".app_combo").first(),
		selection = td.find(":input"),
		timeframe = (selection.length ? selection.val() : false),
		login = root.find(".appointments-login")
	;
	if (is_wrapper.length) return false;
	if (!timeframe) return false;
	if (login.length) return login.show();
	
	$.post(_appointments_data.ajax_url, {
		"action": "pre_confirmation",
		"value": timeframe
	}, function () {}, "json").success(function (response) {
		if (response && response.error) alert(response.error);
		else {
			$(".appointments-confirmation-wrapper").show();
			$(".appointments-confirmation-service").html(response.service);
			if (response.worker){
				$(".appointments-confirmation-worker").html(response.worker).show();
			}
			$(".appointments-confirmation-start").html(response.start);
			$(".appointments-confirmation-end").html(response.end);
			$(".appointments-confirmation-price").html(response.price);
			if (response.price != "0"){
				$(".appointments-confirmation-price").show();
			}
			if (response.name =="ask"){
				$(".appointments-name-field").show();
			}
			if (response.email =="ask"){
				$(".appointments-email-field").show();
			}
			if (response.phone =="ask"){
				$(".appointments-phone-field").show();
			}
			if (response.address =="ask"){
				$(".appointments-address-field").show();
			}
			if (response.city =="ask"){
				$(".appointments-city-field").show();
			}
			if (response.note =="ask"){
				$(".appointments-note-field").show();
			}
			if (response.gcal =="ask"){
				$(".appointments-gcal-field").show();
			}
			if (response.additional =="ask"){
				$(".appointments-additional-field").show();
			}
			$(".appointments-confirmation-button").focus();
		}
	});

	root.find(".appointments-confirmation-cancel-button").off("click").one("click", function () {
		root.find(".appointments-confirmation-wrapper").hide();
		return false;
	});

	root.find(".appointments-confirmation-button").off("click").one("click", function () {
		$.post(_appointments_data.ajax_url, {
			"action": "post_confirmation",
			"value": timeframe,
			"app_name": $(".appointments-name-field-entry").val(),
			"app_email": $(".appointments-email-field-entry").val(),
			"app_phone": $(".appointments-phone-field-entry").val(),
			"app_address": $(".appointments-address-field-entry").val(),
			"app_city": $(".appointments-city-field-entry").val(),
			"app_note": $(".appointments-note-field-entry").val(),
			"app_gcal": ($(".appointments-gcal-field-entry").is(":checked") ? 1 : "")
		}, function () {}, "json").success(function (response) {
			if ( response && response.error ) {
				alert(response.error);
			}
			else if ( response && ( response.refresh=="1" || response.price==0 ) ) {
				if ( response.gcal_url != "" ) {
					if ( response.gcal_same_window ) {
						window.open(response.gcal_url,"_self");
					}
					else {
						window.open(response.gcal_url,"_blank");
						handle_positive_response(root);
					}
				}
				else {
					handle_positive_response(root);
				}
			} else if ( response ) {
				$(".appointments-paypal").find(".app_amount").val(response.price);
				$(".appointments-paypal").find(".app_custom").val(response.app_id);
				var old_val = $(".appointments-paypal").find(".app_submit_btn").val();
				if ( old_val ) {
					var new_val = old_val.replace("PRICE",response.price).replace("SERVICE",response.service_name);
					$(".appointments-paypal").find(".app_submit_btn").val(new_val);
					var old_val2 = $(".appointments-paypal").find(".app_item_name").val();
					var new_val2 = old_val2.replace("SERVICE",response.service_name);
					$(".appointments-paypal").find(".app_item_name").val(new_val2);
					$(".appointments-paypal .app_submit_btn").focus();
				}
				if ( response.gcal_url != "" ) {
					window.open(response.gcal_url,"_blank");
				}
				if ( response.mp == 1 ) {
					$(".mp_buy_form input[name='variation']").val(response.variation);
					$(".mp_buy_form").show();
				}
				else {
					$(".appointments-paypal").show();
				}
			}
			else{
				alert("A connection problem occurred. Please try again.");
			}
		})
		.always(function () {
			root.find(".appointments-confirmation-wrapper").hide();
		});
	});

	return false;
}

$(function () {
	$(document)
		.on("click", ".app_services_button, .app_workers_button", handle_submission)
		.on("click", ".appointments-list table td.free, .app_timetable div.free", handle_scheduling)
		.on("click", ".app_monthly_schedule_wrapper table td.free", handle_day_switch)
		.on("change", ".app_select_services", load_service_description)
		.on("change", ".app_select_workers", load_worker_biography)
	;
});

})(jQuery);

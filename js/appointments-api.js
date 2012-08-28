/* ----- Login with Fb/Tw ----- */
(function ($) {

function create_app_login_interface ($me) {
	if ($("#appointments-login_links-wrapper").length) {
		$("#appointments-login_links-wrapper").remove();
	}
	$me.parents('.appointments-login').after('<div id="appointments-login_links-wrapper" />');
	var $root = $("#appointments-login_links-wrapper");
	$root.html(
		'<ul class="appointments-login_links">' +
			'<li><a href="#" class="appointments-login_link appointments-login_link-facebook">' + l10nAppApi.facebook + '</a></li>' +
			'<li><a href="#" class="appointments-login_link appointments-login_link-twitter">' + l10nAppApi.twitter + '</a></li>' +
			'<li><a href="#" class="appointments-login_link appointments-login_link-wordpress">' + l10nAppApi.wordpress + '</a></li>' +
			'<li class="app_login_submit"><input type="text" class="app_username" value="Username" onfocus="app_checkclear(this)" /><input type="password" class="app_password" value="Password" onfocus="app_checkclear(this)" /><a href="javascript:void(0)" class="appointments-login_link appointments-login_link-submit">' + l10nAppApi.submit + '</a></li>' +
			'<li><a href="#" class="appointments-login_link appointments-login_link-cancel">' + l10nAppApi.cancel + '</a></li>' +
		'</ul>'
	);
	// $me.find(".not_loggedin").addClass("active");
	$root.find(".appointments-login_link").each(function () {
		var $lnk = $(this);
		var callback = false;
		if ($lnk.is(".appointments-login_link-facebook")) {
			// Facebook login
			callback = function () {
				FB.login(function (resp) {
					if (resp.authResponse && resp.authResponse.userID) {
						// change UI
						$root.html('<img src="' + _appointments_data.root_url + 'waiting.gif" /> ' + l10nAppApi.please_wait);
						$.post(_appointments_data.ajax_url, {
							"action": "app_facebook_login",
							"user_id": resp.authResponse.userID,
							"token": FB.getAccessToken()
						}, function (data) {
							var status = 0;
							try { status = parseInt(data.status); } catch (e) { status = 0; }
							if (!status) { // ... handle error
								$root.remove();
								return false;
							}
							if ( data.status && data.status==1 ) { 
								$(".appointments-login_inner").text(l10nAppApi.logged_in);
							}
							else {
								alert(l10nAppApi.error);
							}
						});
					}
				}, {scope: 'email'});
				return false;
			};
		} else if ($lnk.is(".appointments-login_link-twitter")) {
			callback = function () {
				var init_url = $.browser.opera ? '' : 'https://api.twitter.com/';
				var twLogin = window.open(init_url, "twitter_login", "scrollbars=no,resizable=no,toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,height=400,width=600");
				$.post(_appointments_data.ajax_url, {
					"action": "app_get_twitter_auth_url",
					"url": window.location.toString()
				}, function (data) {
					try {
						twLogin.location = data.url;
					} catch (e) { twLogin.location.replace(data.url); }
					var tTimer = setInterval(function () {
						try {
							if (twLogin.location.hostname == window.location.hostname) {
								// We're back!
								var location = twLogin.location;
								var search = '';
								try { search = location.search; } catch (e) { search = ''; }
								clearInterval(tTimer);
								twLogin.close();
								// change UI
								$root.html('<img src="' + _appointments_data.root_url + 'waiting.gif" /> ' + l10nAppApi.please_wait);
								$.post(_appointments_data.ajax_url, {
									"action": "app_twitter_login",
									"secret": data.secret,
									"data": search
								}, function (data) {
									var status = 0;
									try { status = parseInt(data.status); } catch (e) { status = 0; }
									if (!status) { // ... handle error
										$root.remove();
										return false;
									}
									if ( data.status && data.status==1 ) { 
										$(".appointments-login_inner").text(l10nAppApi.logged_in);
									}
									else {
										alert(l10nAppApi.error);
									}
								});
							}
						} catch (e) {}
					}, 300);
				})
				return false;
			};
		} else if ($lnk.is(".appointments-login_link-wordpress")) {
			// Pass on to wordpress login
			callback = function () {
				//window.location = $me.parents(".appointments_login").find(".app_login_hidden").attr("href");
				$(".app_login_submit").show();
				return false;
			};
		} else if ($lnk.is(".appointments-login_link-submit")) {
			callback = function () {
				$(".app_error").remove();
				$lnk.after('<div class="app_wait_img"><img src="' + _appointments_data.root_url + 'waiting.gif" /> ' + l10nAppApi.please_wait +'</div>');
				$.post(_appointments_data.ajax_url, {
						"action": "app_ajax_login",
						"log": $lnk.parents(".app_login_submit").find(".app_username").val(),
						"pwd": $lnk.parents(".app_login_submit").find(".app_password").val(),
						"rememberme": 1
					}, function (data) {
						$(".app_wait_img").remove();
						var status = 0;
						try { status = parseInt(data.status); } catch (e) { status = 0; }
						if (!status) { // ... handle error
							$lnk.after('<div class="app_error">'+data.error+'</div>');
							return false;
						}
						if ( data.status && data.status==1 ) { 
								$(".appointments-login_inner").text(l10nAppApi.logged_in);
							}
						else {
							alert(l10nAppApi.error);
						}
					}
				);
			};
		} else if ($lnk.is(".appointments-login_link-cancel")) {
			// Drop entire thing
			callback = function () {
				//$me.removeClass("active");
				$root.remove();
				return false;
			};
		}
		if (callback) $lnk
			.unbind('click')
			.bind('click', callback)
		;
	});
}

// Init
$(function () {
	$(".appointments-list table td.free").click(function () {
			create_app_login_interface($(".appointments-login_show_login"));
			return false;
		})
	;
});
$(function () {
	$(".appointments-login_show_login").click(function () {
			create_app_login_interface($(this));
			return false;
		})
	;
});
})(jQuery);
function app_checkclear(what){
	if(!what._haschanged){what.value=''};
	what._haschanged=true;
}
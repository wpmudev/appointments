/* ----- Login with Fb/Tw ----- */
(function ($) {

function is_available (service) {
	var all = l10nAppApi && l10nAppApi.show_login_button ? l10nAppApi.show_login_button : [];
	return all.indexOf(service) >= 0;
}

function create_app_login_interface ($me) {
	if ($("#appointments-login_links-wrapper").length) {
		$("#appointments-login_links-wrapper").remove();
	}
	$me.parents('.appointments-login').after('<div id="appointments-login_links-wrapper" />');
	var $root = $("#appointments-login_links-wrapper");
	$root.html(
		'<ul class="appointments-login_links">' +
			(is_available('facebook') ? '<li><a href="#" class="appointments-login_link appointments-login_link-facebook">' + l10nAppApi.facebook + '</a></li>' : '') +
			(is_available('twitter') ? '<li><a href="#" class="appointments-login_link appointments-login_link-twitter">' + l10nAppApi.twitter + '</a></li>' : '') +
			(is_available('google') ? (
				l10nAppApi.gg_client_id
					//? '<li><div id="app-gg-sign_in">' + l10nAppApi.google + '</div></li>'
					? '<li><span id="signinButton"> <span class="g-signin"data-callback="app_google_plus_login_callback"data-clientid="' + l10nAppApi.gg_client_id + '"data-cookiepolicy="single_host_origin"data-scope="profile email"> </span> </span></li>'
					: '<li><a href="#" class="appointments-login_link appointments-login_link-google">' + l10nAppApi.google + '</a></li>'
			) : '')  +
			(is_available('wordpress') ? '<li><a href="#" class="appointments-login_link appointments-login_link-wordpress">' + l10nAppApi.wordpress + '</a></li>' : '') +
			'<li class="app_login_submit"><input type="text" class="app_username" name="log" placeholder="Username" /><input type="password" name="pwd" class="app_password" placeholder="Password" /><a href="javascript:void(0)" class="appointments-login_link appointments-login_link-submit">' + l10nAppApi.submit + '</a></li>' +
			(l10nAppApi.registration_url ? '<li><a href="' + l10nAppApi.registration_url + '" class="appointments-login_link appointments-register_link-wordpress">' + l10nAppApi.register + '</a></li>' : '') +
			'<li><a href="#" class="appointments-login_link appointments-login_link-cancel">' + l10nAppApi.cancel + '</a></li>' +
		'</ul>'
	);
	// $me.find(".not_loggedin").addClass("active");
	$root.find(".appointments-login_link").each(function () {
		var $lnk = $(this);
		var callback = false;
		if ($lnk.is(".appointments-login_link-facebook")) {
			if ("undefined" == typeof FB) {
				$lnk.parents('li').hide();
				return true;
			}
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
							try { status = parseInt(data.status, 10); } catch (e) { status = 0; }
							if (!status) { // ... handle error
								$root.remove();
								return false;
							}
							if ( data.status && data.status==1 ) {
								$(".appointments-login_inner").text(l10nAppApi.logged_in);
								window.location.href = window.location.href;
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
			if (!l10nAppApi._can_use_twitter) {
				$lnk.parents('li').hide();
				return true;
			}
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
									try { status = parseInt(data.status, 10); } catch (e) { status = 0; }
									if (!status) { // ... handle error
										$root.remove();
										return false;
									}
									if ( data.status && data.status==1 ) {
										$(".appointments-login_inner").text(l10nAppApi.logged_in);
										window.location.href = window.location.href;
									}
									else {
										alert(l10nAppApi.error);
									}
								});
							}
						} catch (e) {}
					}, 300);
				});
				return false;
			};
		}
		else if ($lnk.is(".appointments-login_link-google")) {
			callback = function () {
				var googleLogin = window.open('https://www.google.com/accounts', "google_login", "scrollbars=no,resizable=no,toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,height=400,width=800");
				$.post(_appointments_data.ajax_url, {
					"action": "app_get_google_auth_url",
					"url": window.location.href
				}, function (data) {
					var href = data.url;
					googleLogin.location = href;
					var gTimer = setInterval(function () {
						try {
							if (googleLogin.location.hostname == window.location.hostname) {
								// We're back!
								clearInterval(gTimer);
								googleLogin.close();
								// change UI
								$root.html('<img src="' + _appointments_data.root_url + 'waiting.gif" /> ' + l10nAppApi.please_wait);
								$.post(_appointments_data.ajax_url, {
									"action": "app_google_login"
								}, function (data) {
									var status = 0;
									try { status = parseInt(data.status, 10); } catch (e) { status = 0; }
									if (!status) { // ... handle error
										$root.remove();
										$me.click();
										return false;
									}
									if ( data.status && data.status==1 ) {
										$(".appointments-login_inner").text(l10nAppApi.logged_in);
										window.location.href = window.location.href;
									}
									else {
										alert(l10nAppApi.error);
									}
								});
							}
						} catch (e) {}
					}, 300);
				});
				return false;
			};
		}
		else if ($lnk.is(".appointments-login_link-wordpress")) {
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
						try { status = parseInt(data.status, 10); } catch (e) { status = 0; }
						if (!status) { // ... handle error
							$lnk.after('<div class="app_error">'+data.error+'</div>');
							return false;
						}
						if ( data.status && data.status==1 ) {
								$(".appointments-login_inner").text(l10nAppApi.logged_in);
								window.location.href = window.location.href;
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
	if (l10nAppApi.gg_client_id && "undefined" !== typeof gapi && "undefined" !== typeof gapi.signin) gapi.signin.go();
}

function signinCallback(authResult) {
	if (authResult['status']['signed_in']) {
		$.post(_appointments_data.ajax_url, {
			"action": "app_google_plus_login",
			"token": authResult['access_token']
		}, function (data) {
			window.location.href = window.location.href;
		});
	}
}

// Init
$(function () {
	$(document).on("click", ".appointments-login_show_login", function () {
		create_app_login_interface($(this));
		return false;
	});
	if (l10nAppApi.gg_client_id) {
		window.app_google_plus_login_callback = signinCallback;
		(function() {
			var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
			po.src = 'https://apis.google.com/js/client:plusone.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
		})();
	}
});
})(jQuery);
function app_checkclear(what){
	if(!what._haschanged){what.value='';}
	what._haschanged=true;
}
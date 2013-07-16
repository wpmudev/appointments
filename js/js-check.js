/* http://odino.org/logging-javascript-errors/ */
window.MaximumErrorCount = 5;

window.onerror = function(errorMsg, file, lineNumber) {
  window.errorCount || (window.errorCount = 0);
  
  var frontend = false;
  if ( typeof ajaxurl == 'undefined' ) {
	frontend = true;
	ajaxurl = _appointments_data.ajax_url;
  }

  if (window.errorCount <= window.MaximumErrorCount) {
    jQuery.post(ajaxurl, {
		action:			'js_error',
        errorMessage:   errorMsg,
        file:           file,
        url:           window.location.href,
        lineNumber:     lineNumber,
        ua:             navigator.userAgent
    }, function(response){
		if(response && response.message) {
			if ( frontend) { jQuery('body').prepend(response.message);}
			else { jQuery('#wpbody-content').prepend(response.message);}
		}
	}, 'json'
	);
  }
}

// Remove # from url so that page can be refreshed
function app_location() {
	var loc = window.location.href;
	index = loc.indexOf("#");
	if (index > 0) {
	loc = loc.substring(0, index);
	}
	return loc;
}

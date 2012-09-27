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
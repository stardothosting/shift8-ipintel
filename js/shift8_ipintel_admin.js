jQuery(document).ready(function() {
	jQuery('#shift8-key-button').click(function(e) {
        e.preventDefault();
		var data = {
			action: 'shift8_ipintel_response',
			nonce: the_ajax_script.nonce,
		};
		// the_ajax_script.ajaxurl is a variable that will contain the url to the ajax processing file
	 	jQuery.post(the_ajax_script.ajaxurl, data, function(response) {
            //console.debug(response);
            //console.debug(data);
            //console.debug(the_ajax_script.ajaxurl);
			jQuery('#shift8-encryption-hexkey').text(response);
			jQuery('input[name=shift8_ipintel_encryptionkey]').val(response);
            alert('Note : Re-generating the encryption key will invalidate all previously set encrypted sessions');
	 	});
	 	return false;
    });

    jQuery("#action-301").click(function() {
        jQuery("#shift8-ipintel-action-row").show();
    });
    jQuery("#action-403").click(function() {
        jQuery("#shift8-ipintel-action-row").hide();
    });
    if(jQuery('#action-301').is(':checked')){ 
        jQuery("#shift8-ipintel-action-row").show();
    }
});


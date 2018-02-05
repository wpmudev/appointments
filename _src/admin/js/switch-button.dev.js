/**
 * Appointments
 * http://premium.wpmudev.org/
 *
 * Copyright (c) 2017 Incsub
 * Licensed under the GPLv2+ license.
 *
 * Switch button
 */

/* global window, jQuery, switch_button */

jQuery( window.document ).ready(function(){
    "use strict";
    if ( jQuery.fn.switchButton ) {
        var appointment_admin_check_slaves = function() {
            jQuery('.appointments-settings .switch-button' ).each( function() {
                var slave = jQuery(this).data('slave');
                if ( slave ) {
                    var slaves = jQuery( '.appointments-settings .'+slave );
                    if ( jQuery( '.switch-button-background', jQuery(this).closest('td') ).hasClass( 'checked' ) ) {
                        slaves.show();
                    } else {
                        slaves.hide();
                    }
                }
            });
        };
        jQuery('.appointments-settings .switch-button').each(function() {
            var options = {
                checked: jQuery(this).checked,
                on_label: jQuery(this).data('on') || switch_button.labels.label_on,
                off_label: jQuery(this).data('off') || switch_button.labels.label_off,
                on_callback: appointment_admin_check_slaves,
                off_callback: appointment_admin_check_slaves
            };
            jQuery(this).switchButton(options);
        });
    }
});


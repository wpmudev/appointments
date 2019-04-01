"use strict";

window.Appointments = window.Appointments || {};
Appointments.shortcodes = Appointments.shortcodes || {};


jQuery( document).ready( function( $ ) {
    Appointments.shortcodes.confirmation = {
        init: function() {
            this.strings = AppShortcodeConfirmation;
            this.waitImg = "<img class='wait_img' src='" + this.strings.waitingGif + "' />";
            this.$finalValue = $('.appointments-confirmation-final-value');

            this.$confirmationButton = $(".appointments-confirmation-button");

            this.$gcalEntry = $(".appointments-gcal-field-entry");

            this.selectedValue = '';

            this.bind();
        },

        bind: function() {
            var self = this;

            var $body = $('body');
            $body.on( 'click', '.app_timetable_cell.free, .app_week_timetable_cell.free', function() {
                self.preConfirmation.apply( self, [ $(this) ] );
            } );

            $body.on('click', '.appointments-confirmation-cancel-button', function(){
                if ( typeof app_cancel_location === 'function' ) {
                    window.location.href = app_cancel_location();
                }
                else {
                    window.location.reload();
                }
            });

            $body.on( 'click', '.appointments-confirmation-button', function() {
                self.confirmation.apply( self, [ $(this) ] );
            });

        },

        preConfirmation: function( $element ) {
            this.selectedValue = '';
            $element
                .css( "text-align","center" )
                .append( this.waitImg );

            var app_value = $element.find(".appointments_take_appointment").val();

            this.selectedValue = app_value;

            var pre_data = {
                action: "pre_confirmation",
                value: app_value,
                nonce: AppShortcodeConfirmation.nonce
            };

            var self = this;

            $.post( AppShortcodeConfirmation.ajaxurl, pre_data, function( response ) {
                $(".wait_img").remove();

                if ( response && response.error ) {
                    swal(AppShortcodeConfirmation.errorTitle, response.error, 'error');
                }
                else {
                    $(".appointments-confirmation-wrapper").show();
                    $(".appointments-confirmation-service").html(response.service);
                    /**
                     * Service location
                     */
                    if (response.service_location){
                        $(".appointments-confirmation-service_location").html(response.service_location).show();
                    }
                    if (response.worker){
                        $(".appointments-confirmation-worker").html(response.worker).show();
                    }
                    /**
                     * worker location
                     */
                    if (response.worker_location){
                        $(".appointments-confirmation-worker_location").html(response.worker_location).show();
                    }
                    $(".appointments-confirmation-start").html(response.start);
                    $(".appointments-confirmation-end").html(response.end);

                    var confirmationPrice = $(".appointments-confirmation-price");
                    confirmationPrice.html(response.price);
                    if (response.price != "0"){
                        confirmationPrice.show();
                    }
                    if ( self.strings.askName ){
                        $(".appointments-name-field").show();
                    }
                    if ( self.strings.askEmail ){
                        $(".appointments-email-field").show();
                    }
                    if ( self.strings.askPhone ){
                        $(".appointments-phone-field").show();
                    }
                    if ( self.strings.askAddress ){
                        $(".appointments-address-field").show();
                    }
                    if ( self.strings.askCity ){
                        $(".appointments-city-field").show();
                    }
                    if ( self.strings.askNote ){
                        $(".appointments-note-field").show();
                    }
                    if ( self.strings.askGCal ){
                        $(".appointments-gcal-field").show();
                    }

                    $(".appointments-confirmation-button").focus();
                    var offset = $(".appointments-confirmation-wrapper").offset();
                    if (offset && "top" in offset && offset.top) {
                        $(window).scrollTop(offset.top);
                    }
                }
            },"json");

        },

        confirmation: function() {
            $(".appointments-confirmation-cancel-button").after(this.waitImg);

            var final_value = this.selectedValue;
            var app_name = $(".appointments-name-field-entry").val();
            var app_email = $(".appointments-email-field-entry").val();
            var app_phone = $(".appointments-phone-field-entry").val();
            var app_address = $(".appointments-address-field-entry").val();
            var app_city = $(".appointments-city-field-entry").val();
            var app_note = $(".appointments-note-field-entry").val();
            var app_gcal = "";
            var app_warning_text = AppShortcodeConfirmation.warningText;
            var app_confirmation_text = AppShortcodeConfirmation.confirmationText;
            var app_gdpr = $('.appointments-gdpr-confirmation input[type=checkbox]');

            var self = this;

            if ( this.$gcalEntry.is( ":checked" ) ) {
                app_gcal = 1;
            }

            var post_data = {
                action: "post_confirmation",
                value: final_value,
                app_name: app_name,
                app_email: app_email,
                app_phone: app_phone,
                app_address: app_address,
                app_city: app_city,
                app_note: app_note,
                app_gcal: app_gcal,
                app_gdpr: app_gdpr.is(':checked'),
                nonce: AppShortcodeConfirmation.nonce
            };

            if ( AppShortcodeConfirmation.askName ) {
                if ($(".appointments-name-field-entry").val() == "") {
                    swal(AppShortcodeConfirmation.errorTitle, app_warning_text, 'error' );
                    $(".appointments-name-field-entry").focus();
                    $(".wait_img").remove();
                    return false;
                }
            }
            if ( AppShortcodeConfirmation.askEmail ) {
                if ($(".appointments-email-field-entry").val() == "") {
                    swal(AppShortcodeConfirmation.errorTitle, app_warning_text, 'error' );
                    $(".appointments-email-field-entry").focus();
                    $(".wait_img").remove();
                    return false;
                }
            }
            if ( AppShortcodeConfirmation.askPhone ) {
                if ($(".appointments-phone-field-entry").val() == "") {
                    swal(AppShortcodeConfirmation.errorTitle, app_warning_text, 'error' );
                    $(".appointments-phone-field-entry").focus();
                    $(".wait_img").remove();
                    return false;
                }
            }
            if ( AppShortcodeConfirmation.askAddress ) {
                if ($(".appointments-address-field-entry").val() == "") {
                    swal(AppShortcodeConfirmation.errorTitle, app_warning_text, 'error' );
                    $(".appointments-address-field-entry").focus();
                    $(".wait_img").remove();
                    return false;
                }
            }
            if ( AppShortcodeConfirmation.askCity ) {
                if ($(".appointments-city-field-entry").val() == "") {
                    swal(AppShortcodeConfirmation.errorTitle, app_warning_text, 'error' );
                    $(".appointments-city-field-entry").focus();
                    $(".wait_img").remove();
                    return false;
                }
            }
            if ( AppShortcodeConfirmation.askGDPR && ! app_gdpr.is(':checked')) {
                swal(AppShortcodeConfirmation.errorTitle, AppShortcodeConfirmation.GDPRmissingText, 'error' );
                app_gdpr.focus();
                $(".wait_img").remove();
                return false;
            }

            $.post(AppShortcodeConfirmation.ajaxurl, post_data, function(response) {
                $(".wait_img").hide();
                if ( response && response.error ) {
                    swal({
                        title:AppShortcodeConfirmation.errorTitle,
                        text: response.error,
                        type: 'error'
                    }, function() {
                        $(document).trigger("app-confirmation-response_received", [response]);
                    });
                }
                else if ( response && ( response.refresh=="1" || response.price==0 ) ) {
                    swal({
                        title: app_confirmation_text,
                        type: 'success'
                    }, function() {
                        if ( response.gcal_url != "" ) {
                            if ( response.gcal_same_window ) {
                                window.open(response.gcal_url,"_self");
                            }
                            else {
                                window.open(response.gcal_url,"_blank");
                                if ( typeof app_location === 'function' ) {
                                    window.location.href=app_location();
                                }
                                else {
                                    window.location.reload();
                                }
                            }
                        }
                        else {
                            if ( typeof app_location === 'function' ) {
                                window.location.href=app_location();
                            }
                            else {
                                window.location.reload();
                            }
                        }
                        $(document).trigger("app-confirmation-response_received", [response]);
                    });


                }
                else if ( response ) {
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
                        $(".mp_buy_form")
                            .find("[name='variation']").remove().end()
                            .append("<input type='hidden' name='variation' />")
                        ;
                        $(".mp_buy_form input[name='variation']").val(response.variation);
                        $(".mp_buy_form").show();
                    }
                    else {
                        $(".appointments-paypal").show();
                    }
                    $(document).trigger("app-confirmation-response_received", [response]);
                }
                else{
                    swal({
                        title:AppShortcodeConfirmation.errorTitle,
                        text: self.strings.connectionErrorText,
                        type: 'error'
                    }, function() {
                        $(document).trigger("app-confirmation-response_received", [response]);
                    });

                }

            },"json");
        }
    };

    Appointments.shortcodes.confirmation.init();
});

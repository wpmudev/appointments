"use strict";

window.Appointments = window.Appointments || {};
Appointments.shortcodes = Appointments.shortcodes || {};


jQuery( document).ready( function( $ ) {

    Appointments.shortcodes.confirmation = {
        init: function() {
            this.strings = AppShortcodeConfirmation;
            this.waitImg = "<img class='wait_img' src='" + this.strings.waitingGif + "' />";
            this.$scheduleFreeSlots = $(".appointments-list table td.free, .app_timetable div.free")
                .not(".app_monthly_schedule_wrapper table td.free");
            this.$finalValue = $('.appointments-confirmation-final-value');
            this.$confirmationWrapper = $(".appointments-confirmation-wrapper");

            this.$cancelButton = $(".appointments-confirmation-cancel-button");
            this.$confirmationButton = $(".appointments-confirmation-button");

            this.$gcalEntry = $(".appointments-gcal-field-entry");

            this.bind();
        },

        bind: function() {
            var self = this;

            this.$scheduleFreeSlots.click( function() {
                self.preConfirmation.apply( self, [ $(this) ] );
            });

            this.$cancelButton.click(function(){
                window.location.href = app_location();
            });

            this.$confirmationButton.click( function() {
                self.confirmation.apply( self, [ $(this) ] );
            });

        },

        preConfirmation: function( $element ) {
            $element
                .css( "text-align","center" )
                .append( this.waitImg );

            var app_value = $element.find(".appointments_take_appointment").val();

            this.$finalValue.val( app_value );

            var pre_data = {
                action: "pre_confirmation",
                value: app_value,
                nonce: AppShortcodeConfirmation.nonce
            };

            var self = this;

            $.post( AppShortcodeConfirmation.ajaxurl, pre_data, function( response ) {
                $(".wait_img").remove();

                if ( response && response.error ) {
                    alert(response.error);
                }
                else {
                    self.$confirmationWrapper.show();
                    $(".appointments-confirmation-service").html(response.service);
                    if (response.worker){
                        $(".appointments-confirmation-worker").html(response.worker).show();
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
            this.$cancelButton.after(this.waitImg);

            var final_value = $(".appointments-confirmation-final-value").val();
            var app_name = $(".appointments-name-field-entry").val();
            var app_email = $(".appointments-email-field-entry").val();
            var app_phone = $(".appointments-phone-field-entry").val();
            var app_address = $(".appointments-address-field-entry").val();
            var app_city = $(".appointments-city-field-entry").val();
            var app_note = $(".appointments-note-field-entry").val();
            var app_gcal = "";
            var app_warning_text = AppShortcodeConfirmation.warningText;
            var app_confirmation_text = AppShortcodeConfirmation.confirmationText;

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
                nonce: AppShortcodeConfirmation.nonce
            };

            if ( AppShortcodeConfirmation.askName ) {
                if ($(".appointments-name-field-entry").val() == "") {
                    alert(app_warning_text);
                    $(".appointments-name-field-entry").focus();
                    return false;
                }
            }
            if ( AppShortcodeConfirmation.askEmail ) {
                if ($(".appointments-email-field-entry").val() == "") {
                    alert(app_warning_text);
                    $(".appointments-email-field-entry").focus();
                    return false;
                }
            }
            if ( AppShortcodeConfirmation.askPhone ) {
                if ($(".appointments-phone-field-entry").val() == "") {
                    alert(app_warning_text);
                    $(".appointments-phone-field-entry").focus();
                    return false;
                }
            }
            if ( AppShortcodeConfirmation.askAddress ) {
                if ($(".appointments-address-field-entry").val() == "") {
                    alert(app_warning_text);
                    $(".appointments-address-field-entry").focus();
                    return false;
                }
            }
            if ( AppShortcodeConfirmation.askCity ) {
                if ($(".appointments-city-field-entry").val() == "") {
                    alert(app_warning_text);
                    $(".appointments-city-field-entry").focus();
                    return false;
                }
            }

            $.post(AppShortcodeConfirmation.ajaxurl, post_data, function(response) {
                $(".wait_img").remove();
                if ( response && response.error ) {
                    alert(response.error);
                }
                else if ( response && ( response.refresh=="1" || response.price==0 ) ) {
                    alert(app_confirmation_text);
                    if ( response.gcal_url != "" ) {
                        if ( response.gcal_same_window ) {
                            window.open(response.gcal_url,"_self");
                        }
                        else {
                            window.open(response.gcal_url,"_blank");
                            window.location.href=app_location();
                        }
                    }
                    else {
                        window.location.href=app_location();
                    }
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
                }
                else{
                    alert( self.strings.connectionErrorText );
                }
                $(document).trigger("app-confirmation-response_received", [response]);
            },"json");
        }
    };

    Appointments.shortcodes.confirmation.init();
});
Appointments = window.Appointments || {};

( function( global, strings, $ ) {
    "use strict";

    /**
     * Constructor
     *
     * @param options
     * @returns {MyAppointments}
     * @constructor
     */
    function MyAppointments( options ) {
        $(".app-my-appointments-cancel").change( function(e) {
            var $target = $( e.target );
            var cancelAppointment = this.cancelAppointment;
            if ( $target.is( ':checked' ) ) {
                swal({
                        title: strings.aysCancel,
                        text: "",
                        type: "warning",
                        showCancelButton: true,
                        cancelButtonText: strings.no,
                        confirmButtonText: strings.yes,
                        closeOnConfirm: true
                    },
                    function( isConfirm ){
                        if ( ! isConfirm ) {
                            $target.attr("checked", false);
                        }
                        else {
                            cancelAppointment( $target.data( 'app-id' ) );
                        }
                    });

            }
        }.bind( this ));
        return this;
    }

    MyAppointments.prototype.cancelAppointment = function( appId ) {
        var data = {
            action: "cancel_user_app",
            app_id: appId,
            cancel_nonce: strings.nonce
        };

        $.post(strings.ajaxurl, data, function(response) {
            var cancel_box = $('input#cancel-' + appId );
            if ( typeof response.success === 'undefined' ) {
                cancel_box.attr("disabled",true);
                swal( strings.connectionError, '', 'error' );
                return;
            }

            if ( response.success ) {
                swal( strings.cancelled, '', 'success' );
                cancel_box.closest("tr").css("opacity","0.3");
                cancel_box.attr("disabled",true);
            }
            else {
                swal(response.data);
            }
        }, "json");
    };



    /**
     * Helper constructor function
     *
     * @param options
     * @returns {MyAppointments}
     */
    global.myAppointments = function( options ) {
        return new MyAppointments( options );
    };

})( Appointments, appMyAppointmentsStrings, jQuery );
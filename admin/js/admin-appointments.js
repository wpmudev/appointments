!function( $ ) {

    "use strict";

    var Appointments_Admin_Appointments = function() {
        function Appointments_Admin_Appointments(element, options) {
            var _this = this;
            this.$element = element;
            this.options = $.extend({}, Appointments_Admin_Appointments.defaults, this.$element.data(), options);

            this.$statusDescription = $('#status-description');

            this.init = function() {
                $('#status-description-button').click( function( e ) {
                    e.preventDefault();
                    _this.toggleStatusDescription();
                });
            };

            this.toggleStatusDescription = function() {
                _this.$statusDescription.fadeToggle();
            };

            this.init();
        }

        return Appointments_Admin_Appointments;

    }();



    Appointments_Admin_Appointments.defaults = {};

    Appointments_Admin.module( Appointments_Admin_Appointments, 'Appointments_Admin_Appointments' );

}( jQuery );
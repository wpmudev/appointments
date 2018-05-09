var AppDatepicker = function( $element ) {
    var selectedDates = [];
    var rel;
    var $datepicker;

    function padNumber(number) {
        var ret = new String(number);
        if (ret.length == 1)
            ret = "0" + ret;
        return ret;
    }

    var datepicker = {
        init: function( $el ) {
            var self = this;

            var options = {dateFormat: 'yy-mm-dd', numberOfMonths: 2};

            options.onSelect = function( date ) {
                self.toggleDate( date );
            };

            options.beforeShowDay = function (date) {
                var year = date.getFullYear();
                // months and days are inserted into the array in the form, e.g "01/01/2009", but here the format is "1/1/2009"
                var month = padNumber(date.getMonth() + 1);
                var day = padNumber(date.getDate());
                // This depends on the datepicker's date format
                var dateString = year + "-" + month + "-" + day;

                var gotDate = jQuery.inArray(dateString, selectedDates);
                if (gotDate >= 0) {
                    // Enable date so it can be deselected. Set style to be highlighted
                    return [true, "ui-state-highlight"];
                }
                // Dates not in the array are left enabled, but with no extra style
                return [true, ""];
            };

            rel = jQuery($el.data( 'rel' ));

            var dates = rel.val().split(',');
            for ( i in dates ) {
                this.toggleDate( dates[i] );
            }

            $datepicker = $el.datepicker( options );
        },
        toggleDate: function( date ) {
            var index = jQuery.inArray(date, selectedDates);
            if (index > -1) {
                selectedDates.splice(index, 1);
            }
            else {
                selectedDates.push( date );
            }

            this.updateRel();

        },
        updateRel: function() {
            rel.val( this.getDates().join( ',' ) );
        },
        getDates: function() {
            return selectedDates;
        }
    };

    datepicker.init( $element );
    return datepicker;
};

window.AppDatepicker = AppDatepicker;

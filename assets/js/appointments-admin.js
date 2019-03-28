/*!  - v2.4.2
 * https://premium.wpmudev.org/project/appointments-plus/
 * Copyright (c) 2019; * Licensed GPLv2+ */
(function ($) {
$(function () {
	// --- Handle multiple breaks ---
	$(".app-add_break").on("click", function () {
		var me = $(this),
			tr = me.parents('tr').first(),
			new_tr = {}
		;

		tr.find("select").each(function () {
			var sel = $(this),
				name = sel.attr("name"),
				normalized_name = name.replace(/\[\d*\]$/, ''),
				others = $('[name^="' + normalized_name + '"]')
			;
			if (others.length) others.each(function () {
				$(this).attr("name", normalized_name + '[]');
			});
		});
		new_tr = tr.clone();

		new_tr
			.find("a.app-add_break").remove().end()
			.find("td:first").empty()
		;
		tr.after(new_tr);

		return false;
	});
	// --- Drop repeated rows ---
	$(".app-working_hours-workhour_form tr.app-repeated").each(function () {
		var $me = $(this).find("td:last");
		$me.append('<a href="#remove-break" class="app-remove_break"></a>');
	});
	$(document).on("click", ".app-remove_break", function (e) {
		e.preventDefault();
		var $target = $(this).closest("tr.app-repeated");
		if (!$target.length) return false;
		$target.remove();
		return false;
	});

	// --- Handle column meta toggles ---
	$(document).on('click', '.app-settings-column_meta_info-toggle', function () {
		var $me = $(this),
			$root = $me.parents(".app-settings-column_meta_info"),
			$content = $root.find(".app-settings-column_meta_info-content")
		;
		if ($content.is(":visible")) {
			$content.hide();
			$me.text($me.attr("data-off"));
		} else {
			$content.show();
			$me.text($me.attr("data-on"));
		}
		return false;
	});
});


function export_to_gcal ($result) {
	$.post(_app_admin_data.ajax_url, {
		action: 'app-gcal-export_and_update'
	}, function (resp) {
		var remaining = parseInt(resp.remaining, 10),
			msg = resp.msg
		;
		$result.empty().append('<p>' + msg + '</p>');
		if (remaining && remaining > 0) export_to_gcal($result); // Recurse for another call.
	}, 'json');
}
$(function () {
	var $link = $(".app-gcal-export_and_update");
	if (!$link.length) return false;
	
	var $root = $link.closest("td"),
		$result = $root.find(".app-gcal-result")
	;
	$link.on("click", function (e) {
		e.preventDefault();
		e.stopPropagation();
		$result.empty().append('<p>' + _app_admin_data.strings.preparing_export + '</p>');
		export_to_gcal($result);
	});
});


// Hook up info trigger/target resolution
function toggle_target (e) {
	var $trigger = $(this),
		target = false,
		$target = false
	;
	if (e && e.preventDefault) e.preventDefault();
	if (e && e.stopPropagation) e.stopPropagation();

	target = $trigger.attr("data-target");
	if (!target) return false;

	$target = $(".app-info_target." + target);
	if (!$target.length) return false;

	if ($target.is(":visible")) $target.hide("fast");
	else $target.show("fast");

	return false;
}
$(function () {
	$(document).on("click", ".app-info_trigger", toggle_target);
});

/**
 * GDPR admin notice after change number of days
 *
 * @since 2.3.0
 */
$(document).on('click', '.notice-app-gdpr a, .notice-app-gdpr button', function() {
	var parent = $(this).closest( '.notice');
	var data = {
		action: parent.attr('id'),
		url: ajaxurl,
		user_id: parent.data('user_id'),
		nonce: parent.data('nonce')
	};
	$.post( ajaxurl, data );
	return true;
});

})(jQuery);

AppointmentsAdmin = window.AppointmentsAdmin || {};
( function( global, strings, $ ) {
    "use strict";

    /**
     * Constructor
     *
     * @param options
     * @returns {AppointmentsList}
     * @constructor
     */
    function AppointmentsList( options ) {
        // Set default options array
        var defaults = {
            servicesPrice: []
        };
        this.options = $.extend( {}, defaults, options );

        this.isEditing = false;

        // Export button
        this.$exportButton = $('.app-export_trigger');

        // Add new appointment button
        this.$addNew = $(".add-new-h2");
        this.$addNewSpinner = $(".add-new-waiting");

        // Edit single appointment
        this.$editAppointment = $(".app-inline-edit");

        // App list table
        this.$table = $('table.appointments');

        // Confirm removal of selected appointments
        $("#delete_removed").click( function(e) {
            return confirm(strings.deleteRecordsConfirm);
        });

        // Show inline editor when add new button is clicked
        this.$addNew.click( function() {
            this.$addNewSpinner.show();
            this.removeActiveEditorForms();
            this.showEditorForm( 0, function( result ) {
                this.$table.prepend(result);
                this.$addNewSpinner.hide();
            });
        }.bind( this ) );

        // Show inline editor when editing a single appointment
        this.$editAppointment.click(function(e) {
            var appId = $(e.target).data( 'app-id' );
            var row = $('#app-' + appId );
            var $spinner = row.find(".waiting");

            $spinner.show();

            this.removeActiveEditorForms();

            this.showEditorForm( appId, function( result ) {
                $spinner.hide();
                row.hide();
                row.after( result );
            });
        }.bind(this));

        // Cancel edition in inline editor
        this.$table.on( 'click', '.cancel', this.removeActiveEditorForms.bind(this) );

        // Change service price as selection changes
        this.$table.on( 'change', 'select[name="service"]', function(e) {
            var target = $(e.target);
            var value = target.val();
            if ( this.options.servicesPrice[ value ] ) {
                target.parents(".inline-edit-col")
                    .find( 'input[name="price"]' )
                    .val( this.options.servicesPrice[ value ] );
            }
        }.bind(this));

        this.$table.on( 'click', '.save', this.saveEditor.bind(this) );
        
        this.$table.on( 'change', 'select[name=worker]', this.fetchWorkerHours.bind(this) );

        // @TODO Refactor
        this.$exportButton.click( function(e) {
            var button = $(e.target),
                $form = button.closest("form"),
                checkedApps = $(".column-delete.app-check-column :checked"),
                type = $form.find("#app-export_type");

            if (button.is("#app-export-selected") && checkedApps.length) {
                checkedApps.each(function () {
                    $form.append("<input type='hidden' name='app[]' value='" + $(this).val() + "' />");
                });
                type.val("selected");
                return true;
            } else if (button.is("#app-export-type")) {
                $form.append("<input type='hidden' name='status' value='" + button.attr("data-type") + "' />");
                type.val("type");
                return true;
            } else if (button.is("#app-export-all")) {
                type.val("all");
                return true;
            }
            return false;
        });

        $(".app-change-status-btn").click(function(e){
            var button = $(this);
            var selection = $("th.app-check-column input:checkbox:checked");
            // var data = { 'app[]' : []};
            selection.each(function() {
                // data['app[]'].push($(this).val());
                button.after('<input type="hidden" name="app[]" value="'+$(this).val()+'"/>');
            });

            return true;
        });

        $(".info-button").click(function(){
            $(".status-description").toggle('fast');
        });

        function toggle_selected_export () {
            var $sel = $("#the-list .check-column :checked");
            if ($sel.length) $("#app-export-selected").show();
            else $("#app-export-selected").hide();
        }

        $(document).on("change", ".check-column input, .app-column-cb input", toggle_selected_export);
        $(toggle_selected_export);


        return this;
    }

    /**
     * Hide all active inline editors and show all rows in the table
     */
    AppointmentsList.prototype.removeActiveEditorForms = function() {
        this.$table.find( '.inline-edit-row' ).hide();
        $(".app-tr").show();
    };


    /**
     * Show the inline editor form
     *
     * @param app_id Appointment ID
     * @param callback Callback function to execute after success
     */
    AppointmentsList.prototype.showEditorForm = function( app_id, callback ) {
        var data = {
            action: 'inline_edit',
            col_len: this.options.columns,
            app_id: app_id,
            nonce: this.options.nonces.addNew,
            columns: this.options.columns
        };

        return $.post(ajaxurl, data, function (response) {
                if ( response && response.error ) {
                    alert(response.error);
                }
                else if (response && typeof callback === 'function' ) {
                    callback.call( this, [ response.result ] );
                    this.initDatepicker();
                }
                else {
                    alert( strings.unexpectedError );
                }
            }.bind(this),
            'json'
        );
    };

    /**
     * Initializes datepickers
     */
    AppointmentsList.prototype.initDatepicker = function() {
        $( '.datepicker' ).datepicker({
            dateFormat: 'yy-mm-dd',
            firstDay: AppointmentsDateSettings.weekStart
        });
    };
    
    /**
    * Fetches the working hours of the selected provider with ajax
    */
    AppointmentsList.prototype.fetchWorkerHours = function(e) {
        
        var $select             = $( e.target ),
            $worker_id          = $select.val(),            
            $parent             = $select.parents( ".inline-edit-row" ),
            $app_id             = $parent.find( "select[name=service]" ).val(),
            $location           = $parent.find( "select[name=location]" ),
            $slots_list         = $parent.find( "select[name=time]" ),
            $selected_slot      = $slots_list.val(),
            $unknown_slot       = $slots_list.children('option:first'),
            $spinner            = $parent.find( '.waiting' ),
            data                = {};        

        $spinner.show();

        /**
        * Set up ajax data
        */
        data.nonce          = this.options.nonces.editApp;
        data.action         = 'inline_fetch_worker_slots';
        data.worker_id      = $worker_id;
        data.app_id         = $app_id;
        data.selected_slot  = $selected_slot;

        // @TODO: Check if date is set and include that too in ajax data

        if ( typeof( $location ) != "undefined" ) {
            data.location_id         = $location.val();
        }

        /**
        * Handle ajax response
        */
        $.post(ajaxurl, data, function(response) {

            $spinner.hide();

            // When receiving an error message
            if ( response && response.error ){
                
                var error_msg = $('<div />',{
                    'class' : 'error'
                }).html( response.error );

                $select.after( error_msg );
                error_msg.delay(10000).fadeOut('slow');

                return;

            } else if (response) {
                // Received the new time slots for worker
                 var slots = JSON.parse( response.message );

                // Empty the old slots list and add the unknown option
                $slots_list.empty().append( '<option value="' + $unknown_slot.val() + '">' + $unknown_slot.text() + '</option>' );

                // Add the new timeslots
                for ( var key in slots ) {
                    $slots_list.append( slots[key] );
                }
            } else {
                alert( strings.unexpectedError );
                return;
            }


        }.bind(this),'json');
    }

    AppointmentsList.prototype.saveEditor = function(e) {
        var $button = $(e.target);
        var target = $button.parents(".inline-edit-row");
        var $spinner = target.find(".waiting");

        $spinner.show();

        var fields = [
            'user',
            'cname',
            'email',
            'phone',
            'address',
            'city',
            'service',
            'worker',
            'price',
            'date',
            'time',
            'note',
            'status'
        ];

        var data = {};
        $.map( fields, function( fieldName ) {
            if ( 'cname' === fieldName ) {
                data.name = target.find( '[name="' + fieldName + '"]').val();
            }
            else {
                data[fieldName] = target.find( '[name="' + fieldName + '"]').val();
            }
        });

        var app_id = $button.data('app-id');
        var cancel_button = target.find('.cancel');
        data.app_id = app_id;
        data.resend = target.find('input[name="resend"]').is(':checked') ? 1 : 0;
        data.nonce = this.options.nonces.editApp;
        data.action = 'inline_edit_save';
        $(document).trigger('app-appointment-inline_edit-save_data', [data, target]);

        $.post(ajaxurl, data, function(response) {
            $spinner.hide();
            if ( response && response.error ){
                target.find(".error").html(response.error).show().delay(10000).fadeOut('slow');
                return;
            } else if (response) {
                target.find(".error").html(response.message).show().delay(10000).fadeOut('slow');
            } else {
                alert( strings.unexpectedError );
                return;
            }

            if (!(app_id && parseInt(app_id, 10)) && response && response.app_id) {
                location.reload();
            }
            else {
                // this.removeActiveEditorForms();
            }

            if ( response.reload ){
                location.reload();
            }
            cancel_button[0].innerHTML = 'Close';
        }.bind(this),'json');
    };

    /**
     * Helper constructor function
     *
     * @param options
     * @returns {AppointmentsList}
     */
    global.appointmentsList = function( options ) {
        return new AppointmentsList( options );
    };

})( AppointmentsAdmin, _app_admin_data, jQuery );

jQuery( document ).ready( function( $ ) {
    $('.gcal-slider').unslider({
        nav: false
    });
}( jQuery ));
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

"use strict";
( function () {

    tinymce.PluginManager.add( 'appointments_shortcodes', function ( editor ) {

        var datepickers = [];

        /**
         * Generate the content for a shortcode popup
         *
         * @param shortcode
         * @returns {{text: *, onclick: onclick}}
         */
        function appointments_shortcode_item( shortcode ) {
            var body = [],
                field;

            for ( var i in shortcode.defaults ) {
                field = appointments_shortcode_field( i, shortcode.defaults[i] );
                if ( field ) {
                    body.push( field );
                }
            }

            return {
                text: shortcode.name,
                onclick: function () {
                    editor.windowManager.open({
                        title: shortcode.name,
                        body: body,
                        onsubmit: appointments_shortcode_on_submit( shortcode )
                    });
                }
            };
        }

        /**
         * Generates a single field for Editor popup
         *
         * @param id
         * @param definition
         * @returns {boolean}
         */
        function appointments_shortcode_field( id, definition ) {
            var field = {};
            switch ( definition.type ) {
                case 'text': {
                    field = {
                        type: 'textbox',
                        name: id,
                        label: definition.name,
                        value: definition.value
                    };
                    break;
                }
                case 'datepicker': {
                    field = {
                        type: 'textbox',
                        name: id,
                        label: definition.name,
                        value: definition.value,
                        onclick: function() {
                            var id = this._id;
                            var element = jQuery( '#' + id );
                            if ( datepickers.indexOf( id ) < 0 ) {
                                // Initialize Datepicker
                                datepickers.push( id );
                                if ( ! element.length || typeof element.datepicker === 'undefined' ) {
                                    return;
                                }

                                element.datepicker();
                                element.datepicker( "option", "dateFormat", 'yy-mm-dd' );
                                element.datepicker( "option", "firstDay", AppointmentsDateSettings.weekStart );
                            }

                            if ( typeof element.datepicker === 'function' ) {
                                element.datepicker( 'show' );
                            }

                        }
                    };
                    break;
                }
                case 'select': {
                    field = {
                        type: 'listbox',
                        name: id,
                        label: definition.name,
                        values: definition.options
                    };
                    break;
                }
                case 'checkbox': {
                    field = {
                        type: 'checkbox',
                        name: id,
                        label: definition.name,
                        value: 1
                    };

                    if ( definition.value ) {
                        field.checked = true;
                    }
                    break;
                }
            }

            if ( definition.help ) {
                field.tooltip = definition.help
            }

            return field;
        }

        /**
         * Generates the onSubmit action for a group of fields
         *
         * @param shortcode
         * @returns {Function}
         */
        function appointments_shortcode_on_submit( shortcode ) {
            return function( e ) {
                var atts = '';
                var value;
                for ( var i in shortcode.defaults ) {
                    if ( typeof e.data[ i ] !== 'undefined' && shortcode.defaults[i].value != e.data[i] ) {
                        value = e.data[i];
                        if ( 'checkbox' === shortcode.defaults[i].type ) {
                            value = value ? 1 : 0;
                        }
                        atts += ' ' + i + '="' + value + '"';
                    }
                }
                editor.insertContent( '[' + shortcode.shortcode + atts + ']' );
            }
        }

        var ed = tinymce.activeEditor,
            shortcode,
            registeredShortcodes;

        var app_menu = [];



        registeredShortcodes = ed.getLang( 'appointments_shortcodes.shortcodes' );

        for ( var i in registeredShortcodes ) {
            shortcode = registeredShortcodes[i];

            app_menu.push((function (short) {
                return appointments_shortcode_item(short);
            })(shortcode));
        }



        editor.addButton( 'appointments_shortcodes', {
            text: ed.getLang( 'appointments_shortcodes.label' ),
            type: 'menubutton',
            menu: app_menu
        });
    });
})();
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


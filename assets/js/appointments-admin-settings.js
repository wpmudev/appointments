/*!  - v2.4.2
 * https://premium.wpmudev.org/project/appointments-plus/
 * Copyright (c) 2019; * Licensed GPLv2+ */
jQuery(document).ready( function( $ ) {
    var sections = [];
    var currentSection = '';
    var sectionsSize = 0;
    function activateSection( section ) {
        if ( sections[ section ] ) {
            if ( sections[ currentSection ] ) {
                sections[ currentSection ].$el.removeClass( 'current' );
                sections[ currentSection ].$section.removeClass( 'current' );
            }
            sections[ section ].$el.addClass( 'current' );
            sections[ section ].$section.addClass( 'current' );
            currentSection = section;
        }
    }
    $('ul.subsubsub a').each( function() {
        sectionsSize++;
        var $el = $(this);
        var sectionStub = $el.data( 'section' );
        sections[ sectionStub ] = {
            $el: $el,
            $section: $('#app-settings-' + sectionStub )
        };
        $el.click( function( e ) {
            activateSection( sectionStub );
        });
    });
    if ( sectionsSize ) {
        var hash = window.location.hash;
        if ( ! hash ) {
            hash = Object.keys(sections)[0];
        } else {
            hash = hash.substr( 1 );
        }
        activateSection( hash );
    }

    /**
     * handle edit services
     *
     * @since 2.3.0
     */
    $('.wp-list-table.services a.edit').on( 'click', function() {
        var parent = $(this).closest('tr');
        var sectionStub = 'section-edit-service';
        var section = $('#app-settings-' + sectionStub );
        var data = {
            action: 'appointment_get_service',
            id: $(this).data('id'),
            _wpnonce: $(this).data('nonce')
        };
        sections[ sectionStub ] = {
            $el: $(this),
            $section: section
        };
        $.post( ajaxurl, data, function(response) {
            if ( response.success ) {
                $('#service-capacity', section ).val( response.data.capacity );
                $('#service-duration', section ).val( response.data.duration );
                $('#service-id',       section ).val( response.data.ID );
                $('#service-name',     section ).val( response.data.name );
                $('#service-page',     section ).val( response.data.page );
                $('#service-price',    section ).val( response.data.price );
                /**
                 * udpate slider
                 *
                 * @since 2.3.2
                 */
                if ( $.fn.slider ) {
                    $('#service-capacity-slider-edit').slider( 'option', 'value', response.data.capacity );
                }
                /**
                 * PRO: service_padding
                 */
                if ( 'undefined' !== typeof response.data.service_padding ) {
                    if ( 'undefined' !== typeof response.data.service_padding.before ) {
                        $('#service_padding_before', section ).val( response.data.service_padding.before );
                    }
                    if ( 'undefined' !== typeof response.data.service_padding.after ) {
                        $('#service_padding_after', section ).val( response.data.service_padding.after );
                    }
                }
                /**
                 * PRO: service_location
                 */
                if ( 'undefined' !== typeof response.data.service_location ) {
                    $('#service_location', section ).val( response.data.service_location );
                }
                /**
                 * PRO: shared_resources
                 */
                if ( 'undefined' !== typeof response.data.shared_resources ) {
                    $('.app-shared_service label').show();
                    $('.app-shared_service input[type=checkbox]').each( function() {
                        value = parseInt( $(this).val() );
                        /**
                         * reset
                         */
                        $(this).prop( 'checked', false ).prop( 'disabled', false );
                        /**
                         * disable itself
                         */
                        if ( parseInt( response.data.ID ) === value ) {
                            $(this).prop( 'checked', false ).prop( 'disabled', true );
                            $(this).closest('label').hide();
                        } else {
                            if ( 'undefined' !== typeof response.data.shared_resources.shared_ids ) {
                                checked = response.data.shared_resources.shared_ids.indexOf( value );
                                $(this).prop( 'checked', -1 < checked );
                            }
                            if ( 'undefined' !== typeof response.data.shared_resources.direct_ids ) {
                                checked = response.data.shared_resources.direct_ids.indexOf( value );
                                $(this).prop( 'disabled', -1 < checked );
                            }
                        }
                    });
                }
                /**
                 * show tab
                 */
                activateSection( sectionStub );
            } else {
                window.alert( response.data.message );
            }
        });
    });

    /**
     * handle edit workers
     *
     * @since 2.3.0
     */
    $('.wp-list-table.workers a.edit').on( 'click', function() {
        var parent = $(this).closest('tr');
        var sectionStub = 'section-edit-worker';
        var section = $('#app-settings-' + sectionStub );
        var data = {
            action: 'appointment_get_worker',
            id: $(this).data('id'),
            _wpnonce: $(this).data('nonce')
        };
        sections[ sectionStub ] = {
            $el: $(this),
            $section: section
        };
        $.post( ajaxurl, data, function(response) {
            if ( response.success ) {
                $('#worker-dummy', section ).prop( 'checked', response.data.dummy );
                $('#worker-page',  section ).val( response.data.page );
                $('#worker-price', section ).val( response.data.price );
                $('#worker-user',  section ).val( response.data.ID );
                $('#worker-user-display-name',  section ).html( response.data.display_name );
                /**
                 * services_provided
                 */
                $('input[name=multiselect_services_provided]', section ).prop( 'checked', false );
                $('#services_provided option', section ).prop( 'selected', false );
                $.each( response.data.services_provided, function( index, val ) {
                    $('#services_provided option[value="'+val+'"]', section ).prop( 'selected', true );
                });
                /**
                 * update multiselect
                 */
                $('.add_worker_multiple').multiselect( 'refresh' );
                /**
                 * PRO: worker_padding
                 */
                if ( 'undefined' !== typeof response.data.worker_padding ) {
                    if ( 'undefined' !== typeof response.data.worker_padding.before ) {
                        $('#worker_padding_before', section ).val( response.data.worker_padding.before );
                    }
                    if ( 'undefined' !== typeof response.data.worker_padding.after ) {
                        $('#worker_padding_after', section ).val( response.data.worker_padding.after );
                    }
                }
                /**
                 * PRO: worker_location
                 */
                if ( 'undefined' !== typeof response.data.worker_location ) {
                    $('#worker_location', section ).val( response.data.worker_location );
                }
                /**
                 * show tab
                 */
                activateSection( sectionStub );
            } else {
                window.alert( response.data.message );
            }
        });
    });

});

jQuery( document ).ready( function( $ ) {
    // COLOR SCHEMES (Display)
    var customColorRow = $(".custom_color_row");
    var presetSamples = $(".preset_samples");
    var _class, k, i;

    $('select[name="color_set"]').change(function () {
        var n = $(this).val();
        if (n == 0) {
            customColorRow.show();
            presetSamples.hide();
        }
        else {
            customColorRow.hide();
            presetSamples.show();
            for ( i in app_i10n.classes ) {
                if ( app_i10n.classes.hasOwnProperty( i ) ) {
                    _class = [];
                    for ( k = 1; k <= 3; k++ ) {
                        _class[ k ] = app_i10n.presets[i][k];
                    }
                    presetSamples.find("a." + i).css("background-color", "#" + _class[n]);
                }
            }
        }
    });

    $('.colorpicker_input').wpColorPicker();

    /**
     * Check Services Provided on Appointments Settings page
     */
    $('#app-settings-section-new-worker form.add-new-service-provider').on( "submit", function() {
        var form = $(this);
        if( null == $("#services_provided", form).val()) {
            alert( app_i10n.messages.select_service_provider);
            return false;
        }
    });
    /**
     * Create a page
     */
    $('.appointment-create-page a.button').on( 'click', function() {
        var value = $('select', $(this).closest('td') ).val();
        var data = {
            action: $(this).data('action'),
            _wpnonce: $(this).data('nonce'),
            app_page_type: value
        };
        $.post(ajaxurl, data, function(response) {
            var html = '<div class="notice '+(response.success? 'updated':'error')+'"><p>'+response.data.message+'</p></div>';
            $('.appointments-settings h1').after(html);
        });
        return false;
    });

    /**
     * Delete helper
     *
     * @since 2.3.0
     */
    function appointments_delete_helper( data, parent ) {
        $.post( ajaxurl, data, function( response ) {
            if ( response.success ) {
                parent.detach();
            }
            var html = '<div class="notice notice-'+(response.success? 'success':'error')+' is-dismissible"><p>'+response.data.message+'</p></div>';
            $('.appointments-settings h1').after(html);
        });
    }
    /**
     * handle delete services
     *
     * @since 2.2.6
     */
    $(document).on('click', '.wp-list-table.services .delete a', function() {
        if ( window.confirm( app_i10n.messages.service.delete_confirmation ) ) {
            var parent = $(this).closest('tr');
            var data = {
                'action': 'delete_service',
                'nonce': $(this).data('nonce'),
                'id': $(this).data('id')
            };
            appointments_delete_helper( data, parent );
        }
    });

    /**
     * handle bulk action Services
     *
     * @since 2.3.0
     */
    $(document).on('click', '#app-settings-section-services input.action', function() {
        var parent = $(this).closest('form');
        var list = $('.check-column input:checked');
        var action = $('select', $(this).parent() ).val();
        if ( 0 === list.length ) {
            window.alert( app_i10n.messages.bulk_actions.no_items );
            return false;
        }
        if ( '-1' === action ) {
            window.alert( app_i10n.messages.bulk_actions.no_action );
            return false;
        }
        if ( !window.confirm( app_i10n.messages.services.delete_confirmation ) ) {
            return false;
        }
    });

    /**
     * handle delete service provider
     *
     * @since 2.2.6
     */
    $(document).on('click', '.wp-list-table.workers .delete a', function() {
        if ( window.confirm( app_i10n.messages.workers.delete_confirmation ) ) {
            var parent = $(this).closest('tr');
            var data = {
                'action': 'delete_worker',
                'nonce': $(this).data('nonce'),
                'id': $(this).data('id')
            };
            appointments_delete_helper( data, parent );
        }
    });

    /**
     * handle bulk action workers
     *
     * @since 2.3.0
     */
    $(document).on('click', '#app-settings-section-workers input.action', function() {
        var parent = $(this).closest('form');
        var list = $('.check-column input:checked');
        var action = $('select', $(this).parent() ).val();
        if ( 0 === list.length ) {
            window.alert( app_i10n.messages.bulk_actions.no_items );
            return false;
        }
        if ( '-1' === action ) {
            window.alert( app_i10n.messages.bulk_actions.no_action );
            return false;
        }
        if ( !window.confirm( app_i10n.messages.workers.delete_confirmation ) ) {
            return false;
        }
    });

    /**
     * Slider widget
     *
     * @since 2.3.2
     */
    if ( $.fn.slider ) {
        $('div.app-ui-slider').each( function() {
            var id = $(this).data('target-id');
            if ( id ) {
                var target = $('#'+id);
                var value = target.val();
                var max = $(this).data('max') || 100;
                var min = $(this).data('min') || 0;
                $(this).slider({
                    value: value,
                    min: min,
                    max: max,
                    slide: function( event, ui ) {
                        target.val( ui.value );
                    }
                });
            }
        });
    }

    /**
     * add tab to request "hidden-columns".
     *
     * @since 2.4.0
     */
    columns.saveManageColumnsState = function() {
        var hidden = this.hidden();
        $.post(ajaxurl, {
            action: 'hidden-columns',
            hidden: hidden,
            screenoptionnonce: $('#screenoptionnonce').val(),
            page: pagenow,
            tab: $('input[name=app-current-tab]').val()
        });
    };

});

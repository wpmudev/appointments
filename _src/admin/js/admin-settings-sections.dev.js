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

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
     * @since 2.2.8
     */
    $('.wp-list-table.services a.edit').on( 'click', function() {
        var parent = $(this).closest('tr');
        var sectionStub = 'section-edit-service';
        var section = $('#app-settings-' + sectionStub );
        var id = $(this).data('id');
        sections[ sectionStub ] = {
            $el: $(this),
            $section: section
        };
        page = $('.column-page span', parent ).data('id');
        if ( 'undefined' === typeof page ) {
            page = 0;
        }
        $('#service-id', section ).val( id );
        $('#service-name', section ).val( $('.column-name strong a', parent ).html() );
        $('#service-capacity', section ).val( $('.column-capacity', parent ).html() );
        $('#service-duration', section ).val( $('.column-duration span', parent ).data('duration') );
        $('#service-page', section ).val( page );
        $('#service-price', section ).val( $('.column-price', parent ).html() );
        activateSection( sectionStub );
    });

    /**
     * handle edit workers
     *
     * @since 2.2.8
     */
    $('.wp-list-table.workers a.edit').on( 'click', function() {
        var parent = $(this).closest('tr');
        var sectionStub = 'section-edit-worker';
        var section = $('#app-settings-' + sectionStub );
        var id = $(this).data('id');
        sections[ sectionStub ] = {
            $el: $(this),
            $section: section
        };
        page = $('.column-page span', parent ).data('id');
        if ( 'undefined' === typeof page ) {
            page = 0;
        }
        $('#worker-user option[value='+id+']', section ).prop( 'selected', true );
        $('#worker-dummy', section ).prop( 'checked', 1 === $('.column-dummy span', parent ).data('state') );
        $('#worker-page', section ).val( page );
        $('#worker-price', section ).val( $('.column-price', parent ).html() );
        /**
         * services_provided
         */
        $('input[name=multiselect_services_provided]', section ).prop( 'checked', false );
        $('#services_provided option', section ).prop( 'selected', false );
        value = $('.column-services_provided ul', parent ).data('services').toString();
        value = value.split(',');
        $.each( value, function( index, val ) {
            $('#services_provided option[value="'+val+'"]', section ).prop( 'selected', true );
        });
        /**
         * update multiselect
         */
        $('.add_worker_multiple').multiselect( 'refresh' );
        /**
         * switch tab
         */
        activateSection( sectionStub );
    });

});

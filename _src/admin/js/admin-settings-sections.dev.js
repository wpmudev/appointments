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
        }
        else {
            hash = hash.substr( 1 );
        }

        activateSection( hash );
    }

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
        $('#service-duration', section ).val( $('.column-duration', parent ).html() );
        $('#service-page', section ).val( page );
        $('#service-price', section ).val( $('.column-price', parent ).html() );
        activateSection( sectionStub );
    });


});

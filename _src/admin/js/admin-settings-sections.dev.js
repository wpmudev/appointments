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

});

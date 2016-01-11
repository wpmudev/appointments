module.exports = function(grunt) {
    grunt.initConfig({
        pot: {
            options: {
                text_domain: 'appointments', //Your text domain. Produces my-text-domain.pot
                dest: 'languages/', //directory to place the pot file
                keywords: [ //WordPress localisation functions
                    '__:1',
                    '_e:1',
                    '_x:1,2c',
                    'esc_html__:1',
                    'esc_html_e:1',
                    'esc_html_x:1,2c',
                    'esc_attr__:1',
                    'esc_attr_e:1',
                    'esc_attr_x:1,2c',
                    '_ex:1,2c',
                    '_n:1,2',
                    '_nx:1,2,4c',
                    '_n_noop:1,2',
                    '_nx_noop:1,2,3c',
                    '__ngettext:1,2',
                    '__ngettext_noop:1,2'
                ]
            },
            files: {
                src: ['**/*.php'], //Parse all php files
                expand: true
            }
        },
        checktextdomain: {
            options:{
                text_domain: 'my-plugin',
                correct_domain: true, //Will correct missing/variable domains
                keywords: [ //WordPress localisation functions
                    '__:1,2d',
                    '_e:1,2d',
                    '_x:1,2c,3d',
                    'esc_html__:1,2d',
                    'esc_html_e:1,2d',
                    'esc_html_x:1,2c,3d',
                    'esc_attr__:1,2d',
                    'esc_attr_e:1,2d',
                    'esc_attr_x:1,2c,3d',
                    '_ex:1,2c,3d',
                    '_n:1,2,4d',
                    '_nx:1,2,4c,5d'
                ]
            },
            files: {
                src:  [ '**/*.php', ], //All php files
                expand: true,
            }
        }
    });

    grunt.loadNpmTasks('grunt-pot');
    grunt.loadNpmTasks('grunt-checktextdomain')

    grunt.registerTask('default', ['pot']);
};
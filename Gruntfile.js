module.exports = function(grunt) {
    require('load-grunt-tasks')(grunt);

    var excludeCopyFiles = [
        '**',
        '!npm-debug.log',
        '!node_modules/**',
        '!bower.json',
        '!build/**',
        '!bin/**',
        '!.git/**',
        '!Gruntfile.js',
        '!package.json',
        '!.gitignore',
        '!.gitmodules',
        '!sourceMap.map',
        '!phpunit.xml.dist',
        '!travis.yml',
        '!tests/**',
        '!**/Gruntfile.js',
        '!**/package.json',
        '!**/README.md',
        '!lite-vs-pro.txt',
        '!composer.json',
        '!vendor/**',
        '!tmp/**',
        '!**/*~',
        '.distignore',
        '!README.md',
        '!webpack.conf.js'
    ];

    var excludeCopyFilesWPorg = [
        '**',
        '!npm-debug.log',
        '!node_modules/**',
        '!bower.json',
        '!build/**',
        '!bin/**',
        '!.git/**',
        '!Gruntfile.js',
        '!package.json',
        '!.gitignore',
        '!.gitmodules',
        '!sourceMap.map',
        '!phpunit.xml.dist',
        '!travis.yml',
        '!tests/**',
        '!**/Gruntfile.js',
        '!**/package.json',
        '!**/README.md',
        '!lite-vs-pro.txt',
        '!composer.json',
        '!vendor/**',
        '!tmp/**',
        '!**/*~',
        '.distignore',
        '!README.md',
        '!webpack.conf.js',
        '!includes/pro'
    ];

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        checktextdomain: {
            options:{
                report_missing: false,
                text_domain: 'appointments',
                keywords: [
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
                    '_nx:1,2,4c,5d',
                    '_n_noop:1,2,3d',
                    '_nx_noop:1,2,3c,4d'
                ]
            },
            files: {
                src:  [
                    '**/*.php', // Include all files
                    '!node_modules/**', // Exclude node_modules/
                    '!tests/**', // Exclude tests/
                    '!admin/assets/shared-ui/**', // Exclude WPMU DEV Shared UI
                    '!includes/external/**'
                ],
                expand: true
            }
        },

        copy: {
            main: {
                src:  excludeCopyFiles,
                dest: 'build/<%= pkg.name %>/'
            },
            wporg: {
                src:  excludeCopyFilesWPorg,
                dest: 'build/<%= pkg.name %>-wporg/'
            }
        },

        // Generate POT files.
        makepot: {
            options: {
                type: 'wp-plugin',
                domainPath: 'languages',
                potHeaders: {
                    'report-msgid-bugs-to': 'https://wpmudev.org',
                    'language-team': 'LANGUAGE <EMAIL@ADDRESS>'
                }
            },
            dist: {
                options: {
                    potFilename: 'appointments.pot',
                    exclude: [
                        'tests/.*',
                        'node_modules/.*',
                        'includes/external/*',
                        'build/*'
                    ]
                }
            }
        },

        clean: {
            main: ['build/*']
        },

        compress: {
            main: {
                options: {
                    mode: 'zip',
                    archive: './build/<%= pkg.name %>-<%= pkg.version %>.zip'
                },
                expand: true,
                cwd: 'build/<%= pkg.name %>/',
                src: ['**/*'],
                dest: '<%= pkg.name %>/'
            },
            wporg: {
                options: {
                    mode: 'zip',
                    archive: './build/<%= pkg.name %>-<%= pkg.version %>-wporg.zip'
                },
                expand: true,
                cwd: 'build/<%= pkg.name %>-wporg/',
                src: ['**/*'],
                dest: '<%= pkg.name %>-wporg/'
            }
        },

        search: {
            files: {
                src: ['<%= pkg.main %>']
            },
            options: {
                logFile: 'tmp/log-search.log',
                searchString: /^[ \t\/*#@]*Version:(.*)$/mig,
                onMatch: function(match) {
                    var regExp = /^[ \t\/*#@]*Version:(.*)$/mig;
                    var groupedMatches = regExp.exec( match.match );
                    var versionFound = groupedMatches[1].trim();
                    if ( versionFound != grunt.file.readJSON('package.json').version ) {
                        grunt.fail.fatal("Plugin version does not match with package.json version. Please, fix.");
                    }
                },
                onComplete: function( matches ) {
                    if ( ! matches.numMatches ) {
                        if ( ! grunt.file.readJSON('package.json').main ) {
                            grunt.fail.fatal("main field is not defined in package.json. Please, add the plugin main file on that field.");
                        }
                        else {
                            grunt.fail.fatal("Version Plugin header not found in " + grunt.file.readJSON('package.json').main + " file or the file does not exist" );
                        }

                    }
                }
            }
        },

        open: {
            dev : {
                path: '<%= pkg.projectEditUrl %>',
                app: 'Google Chrome'
            }
        },
        replace: {
            dist: {
                options: {
                    patterns: [
                        {
                            match: /Plugin Name\: Appointments\+/g,
                            replacement: 'Plugin Name: Appointments'
                        }
                    ]
                },
                files: [
                    {expand: true, flatten: true, src: ['./build/appointments-wporg/appointments.php'], dest: 'build/appointments-wporg'}
                ]
            }
        }
    });

    grunt.loadNpmTasks('grunt-search');

    grunt.registerTask('version-compare', [ 'search' ] );

    grunt.registerTask( 'finish', function() {
        var json = grunt.file.readJSON('package.json');
        var file = './build/' + json.name + '-' + json.version + '.zip';
        grunt.log.writeln( 'Process finished. Browse now to: ' + json.projectEditUrl['green'].bold );
        grunt.log.writeln( 'And upload the zip file under: ' + file['green'].bold);
        grunt.log.writeln('----------');
        grunt.log.writeln('');
        grunt.log.writeln( 'Remember to tag this new version:' );
        
        var tagMessage = 'git tag -a ' + json.version + ' -m "$CHANGELOG"';
        var pushMessage = 'git push -u origin ' + json.version;
        grunt.log.writeln( tagMessage['green'] );
        grunt.log.writeln( pushMessage['green'] );
        grunt.log.writeln('----------');
    });

    grunt.registerTask('build', [
        'version-compare',
        'checktextdomain',
        'makepot',
        'copy:main',
        'compress:main'
    ]);

    grunt.registerTask('build:wporg', [
        'version-compare',
        'checktextdomain',
        'makepot',
        'copy:wporg',
        'replace',
        'compress:wporg'
    ]);
};
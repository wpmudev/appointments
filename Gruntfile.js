module.exports = function(grunt) {
    require('load-grunt-tasks')(grunt);

    var conf = {
        js_files_concat: {
            'assets/js/appointments-admin.js': [
                'assets/js/src/admin/admin.js',
                'assets/js/src/admin/admin-appointments-list.js',
                'assets/js/src/admin/admin-gcal.js',
                'assets/js/src/admin/admin-multidatepicker.js',
                'assets/js/src/admin/editor-shortcodes.js',
                'assets/js/src/admin/switch-button.js'
            ],
            'assets/js/appointments-admin-settings.js': [
                'assets/js/src/admin/admin-settings-sections.js',
                'assets/js/src/admin/admin-settings.js'
            ],
            'assets/js/appointments-api.js': [
                'assets/js/src/front-end/appointments-api.js',
            ]
        },

        css_files_compile: {
			'assets/css/admin/common.css': 'assets/sass/admin/common.scss',
			'assets/css/front-end/appointments.css': 'assets/sass/front-end/appointments.scss',
			'assets/css/front-end/locations.css': 'assets/sass/front-end/locations.scss'
        },

        plugin_dir: '',
        plugin_file: 'appointments.php',

        // Regex patterns to exclude from transation.
        translation: {
            ignore_files: [
                '.git*',
                'node_modules/.*',
                '(^.php)',		 // Ignore non-php files.
                'release/.*',	  // Temp release files.
                '.sass-cache/.*',
                'tests/.*',		// Unit testing.
            ],
            pot_dir: 'languages/', // With trailing slash.
            textdomain: 'appointments',
        }
    };

    var excludeCopyFiles = [
        '**',
        '!**/*~',
        '!bin/**',
        '!bitbucket-pipelines.yml',
        '!bower_components/**',
        '!bower.json',
        '!build/**',
        '!composer.json',
        '!.distignore',
        '!.git/**',
        '!.gitignore',
        '!.gitmodules',
        '!**/Gruntfile.js',
        '!Gruntfile.js',
        '!lite-vs-pro.txt',
        '!log.log',
        '!node_modules/**',
        '!npm-debug.log',
        '!**/package.json',
        '!package.json',
        '!phpunit.xml.dist',
        '!**/README.md',
        '!README.md',
        '!sourceMap.map',
        '!tests/**',
        '!tools/**',
        '!tmp/**',
        '!travis.yml',
        '!vendor/**',
        '!webpack.config.js',
        '!wporg-assets/**'
    ];

    var excludeCopyFilesDEV = excludeCopyFiles.slice(0).concat( [
        '!readme.txt'
    ] );

    var excludeCopyFilesWPorg = excludeCopyFiles.slice(0).concat( [
        '!includes/pro/**',
        '!includes/external/wpmudev-dash/**',
        '!languages/*.po',
        '!languages/*.mo',
    ] );

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
                    '!includes/external/**',
                    '!build/**'
                ],
                expand: true
            }
        },

        copy: {
            main: {
                src:  excludeCopyFilesDEV,
                dest: 'build/<%= pkg.name %>/'
            },
            wporg: {
                src:  excludeCopyFilesWPorg,
                dest: 'build/<%= pkg.name %>-wporg/'
            },
            wporgAssets: {
                src:  './wporg-assets/**',
                dest: 'build/'
            }
        },

        // Generate POT files.
        makepot: {
            options: {
                type: 'wp-plugin',
                domainPath: 'languages',
                potHeaders: {
                    'report-msgid-bugs-to': 'https://wpmudev.org',
                    'language-team': 'WPMU DEV <support@wpmudev.org>'
                },
                type: 'wp-plugin',
                updateTimestamp: true,
                updatePoFiles: true
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
        potomo: {
            dist: {
                options: {
                    poDel: false
                },
                files: [{
                    expand: true,
                    cwd: 'languages',
                    src: ['*.po'],
                    dest: 'languages',
                    ext: '.mo',
                    nonull: true
                }]
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
                    archive: './build/<%= pkg.name %>-wporg-<%= pkg.version %>.zip'
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
            wpid: {
                options: {
                    patterns: [
                        { match: /WDP ID\: 679841/g, replacement: '' }
                    ]
                },
                files: [
                    {expand: true, flatten: true, src: ['./build/appointments-wporg/appointments.php'], dest: './build/appointments-wporg'}
                ]
            },
            pluginName: {
                options: {
                    patterns: [
                        { match: /Plugin Name\: Appointments\+/g, replacement: 'Plugin Name: Appointments' },
                        { match: /PLUGIN_VERSION/g, replace: '<%= pkg.version %>' },
                    ]
                },
                files: [
                    {expand: true, flatten: true, src: ['./build/appointments-wporg/appointments.php'], dest: 'build/appointments-wporg'}
                ]
            },
            pluginVersion: {
                options: {
                    patterns: [
                        { match: /PLUGIN_VERSION/g, replace: '<%= pkg.version %>' },
                    ]
                },
                files: [
                    {expand: true, flatten: true, src: ['./build/appointments/appointments.php'], dest: 'build/appointments'}
                ]
            }
        },
        watch:  {
            sass: {
                files: [
                    'assets/sass/**/*.scss'
                ],
                tasks: [ 'sass', 'cssmin' ],
                options: {
                    debounceDelay: 500
                }
            },
            scripts: {
                files: ['assets/js/src/**/*.js'],
                // tasks: ['jshint', 'concat', 'uglify'],
                tasks: ['concat', 'uglify'],
                options: {
                    debounceDelay: 500
                }
            }
        },
        jshint: {
            all: [
                'Gruntfile.js',
                'assets/js/src/**/*.js',
                'assets/js/test/**/*.js'
            ],
            options: {
                curly:   true,
                eqeqeq:  true,
                immed:   true,
                latedef: true,
                newcap:  true,
                noarg:   true,
                sub:	 true,
                undef:   true,
                boss:	true,
                eqnull:  true,
                globals: {
                    exports: true,
                    module:  false
                }
            }
        },

        concat: {
            options: {
                stripBanners: true,
                banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
                ' * <%= pkg.homepage %>\n' +
                ' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
                ' * Licensed GPLv2+' +
                ' */\n'
            },
            scripts: {
                files: conf.js_files_concat
            }
        },

        uglify: {
            all: {
                files: [{
                    expand: true,
                    src: ['*.js', '!*.min.js', '!shared*' ],
                    cwd: 'assets/js/',
                    dest: 'assets/js/',
                    ext: '.min.js',
                    extDot: 'last'
                }],
                options: {
                    banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
                        ' * <%= pkg.homepage %>\n' +
                        ' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
                        ' * Licensed GPLv2+' +
                        ' */\n',
                    mangle: {
                        reserved: ['jQuery']
                    }
                }
            }
        },

		sass:   {
			all: {
				options: {
					'sourcemap=none': true, // 'sourcemap': 'none' does not work...
					unixNewlines: true,
					style: 'expanded'
				},
				files: conf.css_files_compile
			}
		},

		cssmin: {
			options: {
				banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
				' * <%= pkg.homepage %>\n' +
				' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
				' * Licensed GPLv2+' +
				' */\n',
				mergeIntoShorthands: false
			},
			target: {
				sourceMap: true,
				expand: true,
				files: {
					'assets/css/appointments-admin.min.css': [
	    				'assets/css/admin/*.css'
					],
					'assets/css/appointments.min.css': [
	    				'assets/css/front-end/*.css'
					]
				},
			},
		},
    });

    grunt.loadNpmTasks('grunt-search');

    grunt.registerTask('version-compare', [ 'search' ] );
    grunt.registerTask( 'i18n', [ 'checktextdomain', 'makepot', 'potomo' ] );

    grunt.registerTask( 'finish', function() {
        var json = grunt.file.readJSON('package.json');
        var file = './build/' + json.name + '-' + json.version + '.zip';
        grunt.log.writeln( 'Process finished. Browse now to: ' + json.projectEditUrl['green'].bold );
        grunt.log.writeln( 'And upload the zip file under: ' + file['green'].bold);
        grunt.log.writeln( 'To make wp.org release, execute ' + 'npm run deploy-svn'['green'].bold + ' and follow instructions');
        grunt.log.writeln('----------');
        grunt.log.writeln('');
        grunt.log.writeln( 'Remember to tag this new version:' );

        var tagMessage = 'git tag -a ' + json.version + ' -m "$CHANGELOG"';
        var pushMessage = 'git push -u origin ' + json.version;
        grunt.log.writeln( tagMessage['green'] );
        grunt.log.writeln( pushMessage['green'] );
        grunt.log.writeln('----------');
    });

    grunt.registerTask( 'default', ['clean', 'concat', 'uglify', 'sass', 'cssmin', 'replace' ] );
    grunt.registerTask( 'js', [ 'concat', 'uglify' ] );
    grunt.registerTask( 'css', [ 'sass', 'cssmin' ] );
    grunt.registerTask( 'i18n', [ 'checktextdomain', 'makepot', 'potomo' ] );

    grunt.registerTask('build', [
        'checktextdomain',
        'makepot',
        'copy:main',
        'replace:pluginVersion',
        'compress:main',
    ]);

    grunt.registerTask('build:wporg', [
        'checktextdomain',
        'makepot',
        'copy:wporg',
        'replace:wpid',
        'replace:pluginName',
    ]);
};

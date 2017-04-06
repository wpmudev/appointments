var path = require( 'path' );
var ExtractTextPlugin = require('extract-text-webpack-plugin');

var shortcodesConfig = Object.assign( {}, {
    name: "shortcodes",
    entry: {
        'app-confirmation': './_src/shortcodes/js/app-confirmation.dev.js',
        'app-services': './_src/shortcodes/js/app-services.dev.js',
        'my-appointments': './_src/shortcodes/js/my-appointments.dev.js'
    },
    output: {
        filename: "[name].js",
        path: path.resolve( __dirname, 'includes/shortcodes/js' )
    },
    devtool: 'source-map' // Generates source Maps for these files
} );

var adminConfig = Object.assign( {}, {
    name: "admin",
    entry: {
        'admin': './_src/admin/js/admin.dev.js',
        'admin-appointments-list': './_src/admin/js/admin-appointments-list.dev.js',
        'admin-gcal': './_src/admin/js/admin-gcal.dev.js',
        'admin-multidatepicker': './_src/admin/js/admin-multidatepicker.dev.js',
        'editor-shortcodes': './_src/admin/js/editor-shortcodes.dev.js'
    },
    output: {
        filename: "[name].js",
        path: path.resolve( __dirname, 'admin/js' )
    },
    devtool: 'source-map' // Generates source Maps for these files
} );

// This bundle will merge these two files into one
var adminSettingsConfig = Object.assign( {}, {
    name: "admin-settings",
    entry: [
        './_src/admin/js/admin-settings-sections.dev.js',
        './_src/admin/js/admin-settings.dev.js'
    ],
    output: {
        filename: "admin-settings.js",
        path: path.resolve( __dirname, 'admin/js' )
    },
    devtool: 'source-map' // Generates source Maps for these files
} );


// Unslider Config
var unsliderConfig = Object.assign( {}, {
    name: "unslider",
    entry: [
        // Webpack just understands JS. That's why we need to load a JS file that imports a CSS file...
        './_src/admin/css/unslider.js'
    ],
    output: {
        filename: "unslider.css",
        path: path.resolve( __dirname, 'admin/css' )
    },
    module: {
        // ...But Webpack won't understand that JS file that imports CSS unless we indicate this loader that understand CSS for Webpack...
        rules: [{
            test: /\.css$/,
            use: ExtractTextPlugin.extract( {
                use: 'css-loader'
            } )
        }]
    },
    plugins: [
        // ... But even when Webpack understand CSS, it will export that CSS to a JS by using eval([CSS-CODE-HERE]),
        // which is horrible. ExtractTextPlugin helps to extract that CSS to a real CSS file
        new ExtractTextPlugin('unslider.css')
    ],
    devtool: 'source-map' // Generates source Maps for these files
} );

module.exports = [ shortcodesConfig, adminConfig, adminSettingsConfig, unsliderConfig ];
var path = require( 'path' );

// Development variables. These are passed by using --env argument when calling webpack. See package.json
var shortcodesConfig = Object.assign( {}, {
    name: "admin",
    entry: {
        'app-confirmation': './includes/shortcodes/js/app-confirmation.dev.js',
        'app-services': './includes/shortcodes/js/app-services.dev.js',
        'my-appointments': './includes/shortcodes/js/my-appointments.dev.js'
    },
    output: {
        filename: "[name].js",
        path: path.resolve( __dirname, 'includes/shortcodes/js' )
    },
    devtool: 'source-map' // Generates source Maps for these files
} );

module.exports = [ shortcodesConfig ];
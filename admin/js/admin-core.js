!function( $ ) {

    "use strict";

    var Appointments_Admin = {
        /**
         * Stores Appointments modules
         */
        _modules: {},

        module: function( module, name ) {
            // Object key to use when storing the plugin, also used to create the identifying data attribute for the plugin
            // Examples: data-reveal, data-off-canvas
            var attrName  = hyphenate(name);

            // Add to the Foundation object and the plugins list (for reflowing)
            this._modules[attrName] = this[name] = module;
        },

        init: function( elem ) {
            var modules = Object.keys(this._modules);
            var _this = this;
            $.each( modules, function ( i, name ) {
                var $el = $(this);
                var module = _this._modules[ name ];
                $el.data( 'app-module', new module( $el ) );
            });
        }
    };

    // Convert PascalCase to kebab-case
    // Thank you: http://stackoverflow.com/a/8955580
    function hyphenate(str) {
        return str.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
    }

    // Polyfill to get the name of a function in IE9
    function functionName(fn) {
        if (Function.prototype.name === undefined) {
            var funcNameRegex = /function\s([^(]{1,})\(/;
            var results = (funcNameRegex).exec((fn).toString());
            return (results && results.length > 1) ? results[1].trim() : "";
        }
        else if (fn.prototype === undefined) {
            return fn.constructor.name;
        }
        else {
            return fn.prototype.constructor.name;
        }
    }

    /**
     * The Appointments Admin jQuery method.
     * @param {String|Array} method - An action to perform on the current jQuery object.
     */
    var appointments_admin = function() {
        Appointments_Admin.init(this);
        return this;
    };



    window.Appointments_Admin = Appointments_Admin;
    $.fn.appointments_admin = appointments_admin;

}( jQuery );
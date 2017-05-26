jQuery( document ).ready( function( $ ) {
    // COLOR SCHEMES (Display)
    var customColorRow = $(".custom_color_row");
    var presetSamples = $(".preset_samples");
    var _class, k, i;

    $('select[name="color_set"]').change(function () {
        var n = $(this).val();
        if (n == 0) {
            customColorRow.show();
            presetSamples.hide();
        }
        else {
            customColorRow.hide();
            presetSamples.show();
            for ( i in app_i10n.classes ) {
                if ( app_i10n.classes.hasOwnProperty( i ) ) {
                    _class = [];
                    for ( k = 1; k <= 3; k++ ) {
                        _class[ k ] = app_i10n.presets[i][k];

                    }
                    presetSamples.find("a." + i).css("background-color", "#" + _class[n]);
                }
            }
        }
    });

    var colorpicker = $('.colorpicker_input');
    colorpicker.each(function () {
        var id = this.id;
        $('#' + id).ColorPicker({
                onSubmit: function (hsb, hex, rgb, el) {
                    $(el).val(hex);
                    $(el).ColorPickerHide();
                },
                onBeforeShow: function () {
                    $(this).ColorPickerSetColor(this.value);
                },
                onChange: function (hsb, hex, rgb) {
                    var element = $('#' + id);
                    element.val(hex);
                    element.parent().find('a.pickcolor').css('background-color', '#' + hex);
                }
            })
            .bind('keyup', function () {
                $(this).ColorPickerSetColor(this.value);
            });
    });
    colorpicker.keyup(function () {
        var a = $(this).val();
        a = a.replace(/[^a-fA-F0-9]/, '');
        if (a.length === 3 || a.length === 6)
            $(this).parent().find('a.pickcolor').css('background-color', '#' + a);
    });

    // LOGIN REQUIRED SETTING (Accesibility)
    var apiDetail = $(".api_detail");
    console.log(apiDetail);
    var loginRequiredSelect = $('select[name="login_required"]');
    function toggleApiDetail() {
        if (loginRequiredSelect.val() == 'yes') {
            apiDetail.show();
        }
        else {
            apiDetail.hide();
        }
    }
    toggleApiDetail();
    loginRequiredSelect.change( function() {
        toggleApiDetail();
    } );

    // PAYMENT REQUIRED (Payments)
    jQuery(document).ready(function ($) {
        $('select[name="payment_required"]').change(function () {
            if ($('select[name="payment_required"]').val() == "yes") {
                $(".payment_row").show();
            }
            else {
                $(".payment_row").hide();
            }
        });
    });

    /**
     * Check Services Provided on Appointments Settings page
     */
    jQuery('#app-settings-section-new-worker form.add-new-service-provider').on( "submit", function() {
        var form = jQuery(this);
        if( null == jQuery("#services_provided", form).val()) {
            alert( app_i10n.messages.select_service_provider);
            return false;
        }
    });

});

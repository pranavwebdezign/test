/* WooBooking Pro – Admin JS */
(function ($) {
    'use strict';

    // Safe access — wbp_admin may be undeclared if enqueue failed
    var cfg     = (typeof wbp_admin !== 'undefined') ? wbp_admin : {};
    var ajaxurl = cfg.ajaxurl || window.ajaxurl || '';
    var nonce   = cfg.nonce   || '';

    /* ── Shared AJAX save helper ────────────────── */
    function wbpAjaxSave( action, formData, $btn, $msg ) {
        var label = $btn.data('label') || $btn.text();
        $btn.data('label', label).prop('disabled', true).text('Saving…');
        $msg.css('color','#666').text('');

        formData.append('action', action);
        formData.append('nonce',  nonce);

        $.ajax({
            url        : ajaxurl,
            type       : 'POST',
            data       : formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if ( res && res.success ) {
                    $msg.css('color','#1a7a1a').html('✅ ' + ( res.data || 'Saved!' ));
                    setTimeout(function(){
                        $msg.fadeOut(600, function(){ $(this).show().text(''); });
                    }, 3000);
                } else {
                    $msg.css('color','#c0392b').html('❌ ' + ( (res && res.data) || 'Save failed. Check console.' ));
                }
            },
            error: function (xhr) {
                $msg.css('color','#c0392b').text('❌ Request error (' + xhr.status + '). Please try again.');
            },
            complete: function () {
                $btn.prop('disabled', false).text( $btn.data('label') );
            }
        });
    }

    /* ── Save General Settings ──────────────────── */
    $(document).on('click', '#wbp-save-general', function () {
        wbpAjaxSave( 'wbp_save_general',
            new FormData( document.getElementById('wbp-general-form') ),
            $(this), $('#wbp-general-msg') );
    });

    /* ── Save Countries ─────────────────────────── */
    $(document).on('click', '#wbp-save-countries', function () {
        wbpAjaxSave( 'wbp_save_countries',
            new FormData( document.getElementById('wbp-countries-form') ),
            $(this), $('#wbp-countries-msg') );
    });

    /* ── Save Zones ─────────────────────────────── */
    $(document).on('click', '#wbp-save-zones', function () {
        wbpAjaxSave( 'wbp_save_zones',
            new FormData( document.getElementById('wbp-zones-form') ),
            $(this), $('#wbp-zones-msg') );
    });

    /* ── Banner image uploader ──────────────────── */
    var bannerFrame;
    $(document).on('click', '#wbp-upload-banner', function (e) {
        e.preventDefault();
        if ( bannerFrame ) { bannerFrame.open(); return; }
        bannerFrame = wp.media({
            title   : 'Select Banner Image',
            button  : { text: 'Use as Banner' },
            multiple: false
        });
        bannerFrame.on('select', function () {
            var att = bannerFrame.state().get('selection').first().toJSON();
            $('#wbp_banner_url').val( att.url );
            $('#wbp-banner-preview').attr('src', att.url).show();
            $('#wbp-remove-banner').show();
        });
        bannerFrame.open();
    });

    $(document).on('click', '#wbp-remove-banner', function (e) {
        e.preventDefault();
        $('#wbp_banner_url').val('');
        $('#wbp-banner-preview').attr('src','').hide();
        $(this).hide();
    });

    /* ── Step icon uploaders ────────────────────── */
    $(document).on('click', '.wbp-upload-icon', function (e) {
        e.preventDefault();
        var targetId = $(this).data('target');
        var frame = wp.media({
            title   : 'Select Step Icon',
            button  : { text: 'Use this Image' },
            multiple: false
        });
        frame.on('select', function () {
            var att = frame.state().get('selection').first().toJSON();
            $('#' + targetId).val( att.url );
        });
        frame.open();
    });

})(jQuery);

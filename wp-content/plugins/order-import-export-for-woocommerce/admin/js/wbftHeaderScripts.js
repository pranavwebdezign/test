(function ($) {
    'use strict';

    $(function () {
        if ($('.wbtf_order_top_header').length) {
            window.wt_oiew_closeTopHeader = function () {
                jQuery.ajax({
                    url: wt_iew_basic_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wt_oiew_top_header_loaded',
                        _wpnonce: wt_iew_basic_params.nonces.main,
                    },
                    success: function (response) {
                        if (response.success) {
                            $('.wbtf_order_top_header').remove();
                            $('.wbte_oimpexp_header').css('top', '0');
                            $('#wpbody-content').css('margin-top', '80px');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Ajax Error:', error);
                        if (xhr.responseJSON && xhr.responseJSON.data) {
                            console.log('Server Response:', xhr.responseJSON.data.message);
                        }
                    }
                });
            }
        }
    });

})(jQuery);

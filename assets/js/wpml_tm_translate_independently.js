/*! WPML TM Translate Independently - v0.1.0
 * http://www.onthegosystems.com/
 */
/*globals alert:false,jQuery:false,window:false,console:false,ajaxurl:false,wpml_tm_translate_independently:false,document:false*/
(function ($, window) {
    'use strict';
    $(function () {
        var duplicated = document.getElementById('icl_duplicate_post_in_basket'),
            button = $('.button-primary'),
            nonce = document.getElementById('icl_disconnect_nonce');
        if (duplicated !== null) {
            alert(wpml_tm_translate_independently.message);
            button.on('click', function () {
                $.ajax({
                    method: "POST",
                    url: ajaxurl,
                    data: {
                        action: 'icl_disconnect_posts',
                        nonce: nonce.value,
                        posts: duplicated.value
                    }
                }).success(function (resp) {
                    if (resp.success !== true) {
                        alert(resp.data);
                    }
                });
            });
        }
    });
})(jQuery, window);
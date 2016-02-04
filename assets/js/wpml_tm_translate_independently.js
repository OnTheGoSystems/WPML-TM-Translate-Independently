/*! WPML TM Translate Independently - v0.1.0
 * http://www.onthegosystems.com/
 */
/*globals alert:false,jQuery:false,window:false,console:false,ajaxurl:false,wpml_tm_translate_independently:false*/
(function ($, window) {
    'use strict';
    $(function () {
        $('#icl_tm_dashboard_form').on('submit', function (e) {
            var form  = this,
                posts = [];
            e.preventDefault();
            $.each($(form).serializeArray(), function (i, field) {
                if (-1 !== field.name.indexOf("checked")) {
                    posts.push(field.value);
                }
            });
            $.ajax({
                method: "POST",
                url: ajaxurl,
                data: {
                    action: 'icl_check_duplicates',
                    posts: posts
                }
            }).success(function (resp) {
                if (resp.data.found_posts !== 0) {
                    var answer = window.confirm(wpml_tm_translate_independently.confirm_message);
                    if (answer === true) {
                        $.ajax({
                            method: "POST",
                            url: ajaxurl,
                            data: {
                                action: 'icl_disconnect_posts',
                                posts: posts
                            }
                        });
                    }
                }
                form.submit();
            });
        });
    });
})(jQuery, window);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    function processReviews(url, fromPages) {
        $.ajax({
            url: url,
            cache: true,
            dataType: 'html'
        }).done(function (data) {
            $('#vendor-review-container').html(data);
            $('[data-role="vendor-review"] .pages a').each(function (index, element) {
                $(element).click(function (event) {
                    processReviews($(element).attr('href'), true);
                    event.preventDefault();
                });
            });
        }).complete(function () {
            if (fromPages == true) {
                $('html, body').animate({
                    scrollTop: $('#reviews').offset().top - 50
                }, 300);
            }
        });
    }

    return function (config, element) {
        processReviews(config.vendorReviewUrl);
        $(function () {
            $('.vendor-info-main .reviews-actions a').click(function (event) {
                event.preventDefault();
                var acnchor = $(this).attr('href').replace(/^.*?(#|$)/, '');
                $(".vendor.data.items [data-role='content']").each(function(index){
                    if (this.id == 'reviews') {
                        $('.vendor.data.items').tabs('activate', index);
                        $('html, body').animate({
                            scrollTop: $('#' + acnchor).offset().top - 50
                        }, 300);
                    }
                });
            });
        });
    };
});

/**
 * Cookie Data Provider Logic
 */
define([
    'jquery',
    'mage/url',
], function ($, urlBuilder) {
    'use strict';

    urlBuilder.setBaseUrl(window.BASE_URL);

    return {
        cookieData: [],
        cookieFetchUrl: urlBuilder.build('gdprcookie/cookie/cookies'),

        getCookieData: function () {
            if (this.cookieData.length === 0) {
                return this.updateCookieData();
            }

            return $.Deferred().resolve(this.cookieData);
        },

        updateCookieData: function () {
            var result = $.Deferred();

            $.ajax({
                url: this.cookieFetchUrl,
                type: 'GET',
                cache: true,
                dataType: 'json',
                data: {
                    allowed: $.cookie('amcookie_allowed')
                },
                success: function (cookieData) {
                    this.cookieData = cookieData;
                    result.resolve(this.cookieData);
                }.bind(this)
            });

            return result;
        }
    }
});

/**
 * Cookie Model
 */

define([
    'jquery',
    'mage/url',
    'underscore',
    'Amasty_GdprCookie/js/model/cookie-data-provider',
    'mage/cookies',
], function ($, urlBuilder, _, cookieDataProvider) {
    'use strict';

    $.widget('mage.cookieSettings', {
        options: {
            allowLink: '/',
            websiteInteraction: '0',
            firstShowProcess: '0',
            essentialCookies: [],
            formContainer: '[data-amcookie-js="form-cookie"]',
            toggleFieldSelector: '.amgdprcookie-input',
        },

        allowAllCookies: function () {
            var url = urlBuilder.build('gdprcookie/cookie/allow');

            return $.ajax({
                showLoader: true,
                method: 'POST',
                url: url,
                success: function () {
                    cookieDataProvider.updateCookieData();
                }.bind(this)
            });
        },

        saveCookie: function (element) {
            var url = urlBuilder.build('gdprcookie/cookie/savegroups'),
                disabledFields = $(this.options.toggleFieldSelector + ':disabled'),
                form = $(element).closest(this.options.formContainer);

            disabledFields.removeAttr('disabled');

            return $.ajax({
                showLoader: true,
                method: 'POST',
                url: url,
                data: form.serialize(),
                success: function () {
                    disabledFields.attr('disabled', true);
                    this.triggerSave();
                    cookieDataProvider.updateCookieData();
                }.bind(this)
            });
        },

        isShowNotificationBar: function (isNotice, websiteInteraction, firstShowProcess) {
            if (isNotice === 0 ||
                $.mage.cookies.get('amcookie_allowed') !== null ||
                !this.isNeedFirstShow(firstShowProcess)
            ) {
                return false;
            }

            this.blockInteraction(websiteInteraction);

            return true;
        },

        getModalData: function () {
            return cookieDataProvider.getCookieData();
        },

        blockInteraction: function (websiteInteraction) {
            var cookie = $.mage.cookies.get('amcookie_allowed');

            if (cookie === null && websiteInteraction == 1) {
                $('.page-wrapper').css({
                    'pointer-events': 'none',
                    '-webkit-user-select': 'none',
                    '-moz-user-select': 'none',
                    '-ms-user-select': 'none',
                    'user-select': 'none',
                    'height': '100%',
                    'overflow': 'hidden',
                    'opacity': '0.1'
                });
            }
        },

        restoreInteraction: function () {
            $('.page-wrapper').removeAttr('style');
        },

        isNeedFirstShow: function (firstShowProcess) {
            if (firstShowProcess === '0') {
                return true;
            }

            if (!localStorage.amCookieBarFirstShow) {
                localStorage.amCookieBarFirstShow = 1;

                return true;
            }

            return false;
        },

        analyticsCookie: function () {
            var self = this,
                disallowedCookie = $.mage.cookies.get('amcookie_disallowed');

            if (!disallowedCookie) {
                return;
            }

            disallowedCookie.split(',').forEach(function (name) {
                self.deleteCookie(name);
            });
        },

        isCookieAllowed: function (cookieName) {
            var allowedGroups = $.mage.cookies.get('amcookie_allowed'),
                disalowedCookie = $.mage.cookies.get('amcookie_disallowed');

            if (this.isEssentialCookie(cookieName)) {
                return true;
            }

            if (disalowedCookie) {
                disalowedCookie = disalowedCookie.split(',');

                if (disalowedCookie.indexOf(cookieName) !== -1) {
                    return false;
                }
            } else if (!allowedGroups && !disalowedCookie) {
                return false;
            }

            return true;
        },

        isEssentialCookie: function (cookieName) {
            return this.options.essentialCookies.indexOf(cookieName) !== -1;
        },

        setCookie: function (name, value, options) {
            var updatedCookie = encodeURIComponent(name) + '=' + encodeURIComponent(value),
                optionKey,
                optionValue;

            if (typeof options.expires === 'number') {
                options.expires = new Date(Date.now() + options.expires * 864e5);
            }

            if (options.expires) {
                options.expires = options.expires.toUTCString();
            }


            for (optionKey in options) {
                updatedCookie += '; ' + optionKey;
                optionValue = options[optionKey];

                if (optionValue !== true) {
                    updatedCookie += '=' + optionValue;
                }
            }

            document.cookie = updatedCookie;
        },

        deleteCookie: function (name) {
            if (this.options.essentialCookies.indexOf(name) === -1) {
                this.setCookie(name, '', {
                    'max-age': -1,
                    'path': '/',
                    'expires': -1
                });
            }
        },

        getEssentialCookies: function (groups) {
            if (!this.options.essentialCookies.length) {
                _.each(groups, function (group) {
                    if (group.isEssential) {
                        this.setEssentialCookie(group.cookies);
                    }
                }.bind(this));
            }
        },

        setEssentialCookie: function (cookies) {
            cookies.forEach(function (item) {
                this.options.essentialCookies.push(item.name);
            }.bind(this));
        },

        triggerSave: function () {
            $('body').trigger('amcookie_save');
        },

        triggerAllow: function () {
            $('body').trigger('amcookie_allow');
        }
    });

    return $.mage.cookieSettings;
});

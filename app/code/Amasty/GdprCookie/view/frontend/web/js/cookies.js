/**
 * Cookie bar logic
 */

define([
    'uiCollection',
    'jquery',
    'uiRegistry',
    'underscore',
    'mage/url',
    'mage/translate',
    'Amasty_GdprCookie/js/model/cookie',
    'Magento_Ui/js/modal/modal',
    'Amasty_GdprCookie/js/action/create-modal',
    'Amasty_GdprCookie/js/action/information-modal',
    'Amasty_GdprCookie/js/model/cookie-data-provider',
], function (Collection, $, registry, _, urlBuilder, $t,
    cookieModel, modal, createModal, informationModal, cookieDataProvider) {
    'use strict';

    return Collection.extend({
        defaults: {
            isNotice: 0,
            template: 'Amasty_GdprCookie/cookiebar',
            allowLink: '/',
            websiteInteraction: null,
            firstShowProcess: '0',
            cookiesName: [],
            domainName: '',
            barSelector: '[data-amcookie-js="bar"]',
            settingsFooterLink: '[data-amcookie-js="footer-link"]',
            setupModalTitle: $t('Please select and accept your Cookies Group'),
            isScrollBottom: false,
            isPopup: false,
            barLocation: null,
            names: {
                setupModal: '.setup-modal',
                cookieTable: '.cookie-table'
            },
            popup: {
                cssClass: 'amgdprcookie-groups-modal'
            },
            setupModal: null,
        },

        initialize: function () {
            this._super();

            cookieDataProvider.getCookieData().done(function (cookieData) {
                cookieModel().getEssentialCookies(cookieData);
            });
            cookieModel().analyticsCookie();
            cookieModel().blockInteraction();
            this.bindEvents();

            return this;
        },

        initObservable: function () {
            this._super()
                .observe({
                    isScrollBottom: false
                });

            return this;
        },

        /**
         * Create click event on footer button
         */
        bindEvents: function () {
            var elem = $(this.settingsFooterLink);

            $(elem).on('click', function (event) {
                event.preventDefault();
                this.openModal();
            }.bind(this));
        },

        saveCookie: function (element, modalContext) {
            cookieModel().saveCookie(element).done(function () {
                modalContext.closeModal();
            });

            $(this.barSelector).remove();
            if (this.websiteInteraction == 1) {
                cookieModel().restoreInteraction();
            }
        },

        /**
         * Open Setup Cookie Modal
         */
        openModal: function () {
            if (!this.setupModal) {
                this.getModalData();

                return;
            }

            this.setupModal.openModal();
        },

        /**
         * Get Setup Modal Data
         */
        getModalData: function () {
            cookieDataProvider.getCookieData().done(function (cookieData) {
                this.initModal(cookieData);
            }.bind(this));
        },

        /**
         * Create Setup Modal Component
         */
        initModal: function (data) {
            createModal.call(
                this,
                data,
                '',
                this.popup.cssClass,
                false,
                'Amasty_GdprCookie/cookie-settings',
                this.name + this.names.setupModal,
                this.setupModalTitle
            );

            registry.async(this.name + this.names.setupModal)(function (modal) {
                this.setupModal = modal;
            }.bind(this));
        },

        /**
         * Create/Open Information Modal Component.
         */
        getInformationModal: function (data) {
            informationModal.call(this, this.names.cookieTable, data, this.popup.cssClass);
        },

        allowCookies: function () {
            cookieModel().allowAllCookies().done(function () {
                $(this.barSelector).remove();
                cookieModel().triggerAllow();
                if (this.websiteInteraction == 1) {
                    cookieModel().restoreInteraction();
                }
            }.bind(this));
        },

        detectScroll: function () {
            if (this.barLocation == 1 || this.isPopup) {
                return;
            }

            this.elementBar = $(this.barSelector);
            $(window).on('scroll', _.throttle(this.scrollBottom, 200).bind(this));
        },

        scrollBottom: function () {
            var scrollHeight = window.innerHeight + window.pageYOffset,
                pageHeight = document.documentElement.scrollHeight;

            if (scrollHeight >= pageHeight - this.elementBar.innerHeight()) {
                this.isScrollBottom(true);
                return;
            }

            this.isScrollBottom(false);
        },

        isShowNotificationBar: function () {
            return cookieModel()
                .isShowNotificationBar(
                    this.isNotice, this.websiteInteraction, this.firstShowProcess
                );
        }
    });
});

/**
 * Cookie modal logic
 */
define([
    'uiCollection',
    'uiRegistry',
    'jquery',
    'underscore',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'Amasty_GdprCookie/js/model/cookie',
    'Amasty_GdprCookie/js/action/information-modal',
    'Amasty_GdprCookie/js/action/create-modal',
    'Amasty_GdprCookie/js/model/cookie-data-provider',
], function (Collection, registry, $, _, $t, modal, cookieModel,
    informationModal, createModal, cookieDataProvider) {
    'use strict';

    return Collection.extend({
        defaults: {
            template: {
                name: 'Amasty_GdprCookie/modal'
            },
            timeout: null,
            isNotice: null,
            groups: [],
            cookieModal: null,
            websiteInteraction: '',
            firstShowProcess: '',
            isShowModal: false,
            element: {
                modal: '[data-amgdpr-js="modal"]',
                form: '[data-amcookie-js="form-cookie"]',
                container: '[data-role="gdpr-cookie-container"]',
                field: '[data-amcookie-js="field"]',
                groups: '[data-amcookie-js="groups"]',
                policy: '[data-amcookie-js="policy"]',
                settingsFooterLink: '[data-amcookie-js="footer-link"]'
            },
            setupModalTitle: $t('Please select and accept your Cookies Group'),
            names: {
                cookieTable: '.cookie-table',
                setupModal: '.setup-modal',
            },
            popup: {
                cssClass: 'amgdprcookie-groups-modal'
            }
        },

        initialize: function () {
            this._super();

            cookieDataProvider.getCookieData().done(function (cookieData) {
                this.groups = cookieData;
                cookieModel().getEssentialCookies(cookieData);

                if (cookieModel().isShowNotificationBar(
                    this.isNotice, this.websiteInteraction, this.firstShowProcess
                )) {
                    this.initModal();
                }

                cookieModel().analyticsCookie();
                this.bindEvents();
            }.bind(this));

            return this;
        },

        initObservable: function () {
            this._super()
                .observe(['isShowModal']);

            return this;
        },

        bindEvents: function () {
            var elem = $(this.element.settingsFooterLink);

            $(elem).on('click', this.getSettingsModal.bind(this));
        },

        getSettingsModal: function (e) {
            e.preventDefault();

            if (this.setupModal) {
                this.setupModal.openModal();

                return;
            }

            cookieModel().getModalData().done(function (data) {
                this.initSetupModal(data);
            }.bind(this));
        },

        initSetupModal: function (data) {
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

        closeModal: function () {
            if (!this.cookieModal) {
                return;
            }

            this.cookieModal.closeModal();

            if (this.websiteInteraction != 1) {
                return;
            }

            cookieModel().restoreInteraction();
        },

        allowCookies: function () {
            cookieModel().allowAllCookies().done(function () {
                this.closeModal();
                cookieModel().triggerAllow();
            }.bind(this));
        },

        saveCookie: function (element, modalContext) {
            cookieModel().saveCookie(element).done(function () {
                modalContext.closeModal();
            });
        },

        initModal: function () {
            var options = {
                type: 'popup',
                responsive: true,
                modalClass: 'amgdprcookie-modal-container',
                buttons: []
            };

            this.isShowModal(true);

            if (this.websiteInteraction == 1) {
                options.clickableOverlay = false;
                options.keyEventHandlers = {
                    escapeKey: function () { }
                };

                options.opened = function () {
                    $('.modal-header button.action-close').hide();
                };
            }

            this.cookieModal = modal(options, this.element.modal);

            this.cookieModal.element.html($(this.element.container));
            this.addResizeEvent();
            this.setModalHeight();
            this.cookieModal.openModal().on('modalclosed', function () {
                $(window).off('resize', this.resizeFunc);
            }.bind(this));
        },

        addResizeEvent: function () {
            this.resizeFunc = _.throttle(this.setModalHeight, 150).bind(this);
            $(window).on('resize', this.resizeFunc);
        },

        setModalHeight: function () {
            var policyHeight = $(this.element.policy).innerHeight(),
                windowHeight = window.innerHeight,
                groupsContainer = $(this.element.groups);

            if (policyHeight / windowHeight > 0.6) {
                policyHeight /= 2;
            }

            groupsContainer.height(windowHeight - policyHeight + 'px');
        },

        getInformationModal: function (data) {
            informationModal.call(this, this.names.cookieTable, data, this.popup.cssClass);
        }
    });
});

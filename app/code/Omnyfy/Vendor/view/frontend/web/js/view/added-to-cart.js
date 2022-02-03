define([
    'jquery',
    'uiComponent',
    'underscore',
    'ko',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/modal/modal'
], function ($, Component, _, ko, customerData, modal) {
    'use strict';

    return Component.extend({

        defaults: {
            template: 'Omnyfy_Vendor/added-to-cart',
            tracks: {
                cartMessage: true,
                productId: true,
                productName: true,
                productMedia: true,
                productPrice: true,
                productQty: true

            }
        },

        initialize: function (config) {
            this._super();

            this.popupBlock = config.popupBlock;
            this.checkoutUrl = config.checkoutUrl;
            this.ajaxUrl = config.ajaxUrl
            this.createPopUp();
            this.bindEvents();
            this.shouldNotShowPopup = document.referrer.indexOf('customer/account/login') !== -1;
        },

        bindEvents: function () {
            var self = this;

            var cartData = customerData.get('cart'),
                prevCartItemsObs = customerData.get('prev-cart-items'),
                products;

            setTimeout(function() {
                if (!prevCartItemsObs().length && cartData().items.length) {
                    customerData.set('prev-cart-items', cartData().items);
                }
            }, 4000);

            cartData.subscribe(function() {

                if (self.shouldNotShowPopup === true) {
                    self.shouldNotShowPopup = false;
                } else {
                    var prevCartItems = Object.values(customerData.get('prev-cart-items')());

                    if (!_.isEqual(cartData().items, prevCartItems)) {

                        if (prevCartItems === undefined) {
                            products = cartData().items;
                        } else {
                            products = cartData().items.filter(self.comparer(prevCartItems));
                        }

                        if (products && products.length) {
                            self.productName = $('<div>' +products[0].product_name + '</div>').text(); // removing escaped html entities; _.unescape() didn't work
                            self.productMedia = products[0].product_image.src;
                            self.productPrice = products[0].product_price;
                            self.productQty = products[0].qty;

                            self.showModal();
                        }
                    }
                }

                customerData.set('prev-cart-items', cartData().items);
            })
        },

        comparer: function (otherArray) {
            return function(current){
                return otherArray.filter(function(other){
                    return other.product_id === current.product_id && other.qty === current.qty
                }).length === 0;
            }
        },

        createPopUp: function() {
            var self = this;
            var options = {
                'type': 'popup',
                title: $.mage.__('Product successfully added to cart'),
                autoOpen: false,
                modalClass: 'added-to-cart-modal',
                buttons: [
                    {
                        text: $.mage.__('Continue Shopping'),
                        attr: {
                            'data-action': 'confirm'
                        },
                        'class': 'action secondary',
                        click: function () {
                            this.closeModal();
                        }
                    },
                    {
                        text: $.mage.__('View Cart & Checkout'),
                        attr: {
                            'data-action': 'cancel'
                        },
                        'class': 'action primary',
                        click: function () {
                            window.location.href = self.checkoutUrl;
                        }
                    }
                ]
            };

            modal(options, $(this.popupBlock));
        },

        showModal: function () {
            $(this.popupBlock).modal('openModal');
        }
    })
});
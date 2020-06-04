/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'ko',
        'Evalent_EcsterPay/js/model/quote',
        'Evalent_EcsterPay/js/model/config',
        'mage/url',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'Magento_Checkout/js/model/full-screen-loader',
        'ecsterpayjs'
    ],
    function (
        $,
        ko,
        quote,
        ecsterConfig,
        urlBuilder,
        messageList,
        $t,
        fullScreenLoader
    ) {

        'use strict';

        var isUpdateCountry, checkoutUpdating = false;
        var updateShippingOnSuccess = null;

        return {
            key: quote.getEcsterCartKey(),

            ecsterStart: function () {
                EcsterPay.start({
                    cartKey: this.key,
                    shopTermsUrl: ecsterConfig.shopTermsUrl,
                    showCart: ecsterConfig.showCart,
                    showPaymentResult: false,
                    showDelivery: ecsterConfig.showDelivery,
                    onCheckoutStartInit: $.proxy(function () {
                        this.onCheckoutStartInit();
                    }, this),
                    onCheckoutStartSuccess: $.proxy(function () {
                        this.onCheckoutStartSuccess();
                    }, this),
                    onCheckoutStartFailure: $.proxy(function (response) {
                        this.onCheckoutStartFailure(response);
                    }, this),
                    initUpdateCart: $.proxy(function () {
                        this.onCheckoutInitUpdateCart();
                    }, this),
                    finishUpdateCart: $.proxy(function () {
                        this.onCheckoutFinishUpdateCart();
                    }, this),
                    onCheckoutUpdateInit: $.proxy(function () {
                        this.onCheckoutUpdateInit();
                    }, this),
                    onCheckoutUpdateSuccess: $.proxy(function () {
                        this.onCheckoutUpdateSuccess();
                    }, this),
                    onCustomerAuthenticated: $.proxy(function (response) {
                        this.onCustomerAuthenticated(response);
                    }, this),
                    onChangedDeliveryAddress: $.proxy(function (response) {
                        this.onChangedDeliveryAddress(response);
                    }, this),
                    onPaymentSuccess: $.proxy(function (response) {
                        this.onPaymentSuccess(response);
                    }, this),
                    onPaymentFailure: $.proxy(function (response) {
                        this.onPaymentFailure(response);
                    }, this),
                    onPaymentDenied: $.proxy(function (response) {
                        this.onPaymentDenied(response);
                    }, this),
                });
            },
            onCheckoutStartInit: function (response) {
                fullScreenLoader.startLoader();
                // console.log("onCheckoutStartInit");
            },
            onCheckoutStartSuccess: function (response) {
                checkoutUpdating = false;
                fullScreenLoader.stopLoader();
                // console.log("onCheckoutStartSuccess");
            },
            onCheckoutStartFailure: function (response) {
                checkoutUpdating = false;
                fullScreenLoader.stopLoader();
                // console.log("onCheckoutStartFailure");
            },
            onCheckoutUpdateInit: function (response) {
                checkoutUpdating = true;
                fullScreenLoader.startLoader();
                // console.log("onCheckoutUpdateInit");
            },
            onCheckoutInitUpdateCart: function (response) {
                checkoutUpdating = false;
                fullScreenLoader.startLoader();
                // console.log("onCheckoutInitUpdateCart");
            },
            onCheckoutFinishUpdateCart: function (response) {
                checkoutUpdating = false;
                fullScreenLoader.stopLoader();
                // console.log("onCheckoutFinishUpdateCart");
            },
            onCheckoutUpdateSuccess: function (response) {
                fullScreenLoader.stopLoader();
                checkoutUpdating = false;
                // console.log("onCheckoutUpdateSuccess");
            },
            onCustomerAuthenticated: function (response) {
                // console.log("onCustomerAuthenticated");
            },
            onChangedContactInfo: function (response) {
                // console.log('onChangedContactInfo');
            },
            onChangedDeliveryAddress: function (response) {
                // console.log('onChangedDeliveryAddress');
            },
            onPaymentSuccess: function (response) {
                fullScreenLoader.startLoader();
                window.location.href = ecsterConfig.successUrl + 'ecster-reference/' + response.internalReference;
            },
            onPaymentFailure: function (response) {
                // console.log('onPaymentFailure');
            },
            onPaymentDenied: function (response) {
                // console.log("onPaymentDenied");
            },
            initEcsterDiv: function () {
                $('#ecster-pay-ctr').html('');
            },
            isCheckoutUpdating: function () {
                return checkoutUpdating
            },
            updateShippingMethodOnSuccess: function () {

            },
            isUpdateCountry: function () {
                return isUpdateCountry;
            },
            setUpdateCountry: function () {
                isUpdateCountry = true;
            },
            init: function () {
                this.ecsterStart();
            },
            updateInitCart: function (currentCartKey) {
                return EcsterPay.initUpdateCart(currentCartKey);
            },
            updateCart: function (type) {
                let success = true;
                if (type != ecsterConfig.preselectedPurchaseType) { // Make sure that not useless calls being made
                    $.ajax({ // Update the cart with the correct purchase type before updating the checkout
                        url: urlBuilder.build('ecsterpay/checkout/updatecart'),
                        type: 'get',
                        async: false,
                        dataType: 'json',
                        context: this,
                        data: {
                            'type': type
                        },

                        /**
                         * @param {Object} response
                         */
                        success: function (response) {
                            if (response.error) {
                                messageList.addErrorMessage({ message: response.message });
                                success = false;
                                return;
                            }
                            this.key = response.ecster_key
                            ecsterConfig.preselectedPurchaseType = type;
                            this.initEcsterDiv()
                            this.ecsterStart()
                            success = true;
                        },
                        error: function (reponse) {
                            messageList.addErrorMessage({ message: $t('Something went wrong. Try again and if the problem persists please contact the support for more information') });
                            success = false;
                        }
                    });
                }
                return success;
            }
        };
    }
);

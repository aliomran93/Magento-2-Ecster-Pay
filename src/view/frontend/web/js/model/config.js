/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'ko'
    ],
    function (ko) {
    
        'use strict';

        var isActive = window.checkoutConfig.payment.ecsterpay.active;
        var mode = window.checkoutConfig.payment.ecsterpay.mode;
        var testModeMessage = window.checkoutConfig.payment.ecsterpay.testModeMessage;
        var shopTermsUrl = window.checkoutConfig.payment.ecsterpay.shopTermsUrl;
        var successUrl = window.checkoutConfig.payment.ecsterpay.successUrl;
        var showCart = window.checkoutConfig.payment.ecsterpay.showCart;
        var showDiscount = window.checkoutConfig.payment.ecsterpay.showDiscount;
        var cartUrl = window.checkoutConfig.payment.ecsterpay.cartUrl;
        var showDelivery = window.checkoutConfig.payment.ecsterpay.showDelivery;
        var showPaymentResult = window.checkoutConfig.payment.ecsterpay.showPaymentResult;
        var isMultipleCountry = window.checkoutConfig.payment.ecsterpay.isMultipleCountry;
        var defaultCountry = window.checkoutConfig.payment.ecsterpay.defaultCountry;
        var defaultShippingMethod = window.checkoutConfig.payment.ecsterpay.defaultShippingMethod;
        var singleShippingMethod = window.checkoutConfig.payment.ecsterpay.singleShippingMethod;
        var purchaseType = window.checkoutConfig.payment.ecsterpay.purchaseType;
        var preselectedPurchaseType = window.checkoutConfig.payment.ecsterpay.preselectedPurchaseType;

        return {
            isActive: isActive,
            mode: mode,
            testModeMessage: testModeMessage,
            shopTermsUrl: shopTermsUrl,
            successUrl: successUrl,
            showCart: showCart,
            showDiscount: showDiscount,
            cartUrl: cartUrl,
            showDelivery: showDelivery,
            showPaymentResult: showPaymentResult,
            isMultipleCountry: isMultipleCountry,
            defaultCountry: defaultCountry,
            defaultShippingMethod: defaultShippingMethod,
            singleShippingMethod: singleShippingMethod,
            purchaseType: purchaseType,
            preselectedPurchaseType: preselectedPurchaseType
        };
    }
);